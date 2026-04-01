<?php

namespace App\Services\Survey;

use App\Enums\SurveyBurnoutScaleTypeEnum;
use App\Enums\SurveyStatusEnum;
use App\Enums\WorkerStatusEnum;
use App\Http\Requests\Survey\SurveyBurnoutAnswerRequest;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyType;
use App\Models\Worker\Worker;
use App\Support\Surveys\Templates\ProfessionalBurnoutQuestionsTemplate;
use Carbon\Carbon;
use RuntimeException;

class SurveyBurnoutService
{
    public function __construct(
        private SurveyService $surveyService
    ) {
    }

    public function createSurveyBurnout(): void
    {
        $now = Carbon::now();

        $currentMonthName = $now->translatedFormat('F');
        $monthPlusFourName = $now->copy()
            ->addMonthsNoOverflow(3)
            ->translatedFormat('F');

        $currentYear = $now->year;
        $yearPlusThree = $now->copy()
            ->addMonthsNoOverflow(3)
            ->year;

        $dateEndPlusThreeMonths = $now->copy()
            ->addMonthsNoOverflow(3)
            ->endOfMonth()
            ->toDateString();

        $surveyType = SurveyType::query()
            ->where('code', 'professional burnout')
            ->first();

        $surveyAnswerType = SurveyAnswerType::query()
            ->where('title', 'Text text, text 0-6')
            ->first();

        if (!$surveyAnswerType) {
            throw new RuntimeException("SurveyAnswerType text text title 'Text text, text 0-6' text text");
        }

        if (!$surveyType) {
            throw new RuntimeException("SurveyType text text code 'professional burnout' text text");
        }

        $questions = ProfessionalBurnoutQuestionsTemplate::questions();

        $survey = Survey::query()->create([
            'name' => "Text text text text text {$currentMonthName} {$currentYear} text {$monthPlusFourName} {$yearPlusThree}",
            'description' => 'Text text text text text text text, text text text text text text text text text. Text text text text text 22 text text text text text, text text text. Text, text text text text text text, text text text text text text. Text text text text text text text text, text text text answers text text 0 – "text". Text text text text text text, text, text text text text text, text 7 – "text".',
            'survey_type_id' => $surveyType->id,
            'status' => SurveyStatusEnum::Created->value,
            'date_end' => $dateEndPlusThreeMonths,
            'is_template' => false,
        ]);

        $questionsData = [];

        $i = 0;
        foreach ($questions as $code => $row) {
            $i++;
            $questionsData[] = [
                'question_number' => $i,
                'survey_id' => $survey->id,
                'question' => $row['title'],
                'answer_type_id' => $surveyAnswerType->id,
                'code' => $code,
            ];
        }

        $survey->questions()->createMany($questionsData);

        $workerIds = Worker::query()
            ->whereRelation('status', function ($query) {
                $query->whereIn('code', [
                    WorkerStatusEnum::Working,
                    WorkerStatusEnum::ProbationPeriod,
                ]);
            })
            ->pluck('id')
            ->all();

        $survey->workers()->attach($workerIds);

        $this->surveyService->activate($survey);
    }

    public function getBurnoutAnswers(SurveyBurnoutAnswerRequest $request): array
    {
        $requestData = $request->validated();

        $fromDate = $requestData['date_from'];
        $toDate   = Carbon::parse($requestData['date_to'])->endOfDay();

        $templateQuestions = ProfessionalBurnoutQuestionsTemplate::questions();

        $surveyType = SurveyAnswerType::query()
            ->where('title', 'Text text, text 0-6')
            ->first();

        if (! $surveyType) {
            throw new RuntimeException("SurveyAnswerType text title 'Text text, text 0-6' text text");
        }

        $maxValue = (int) $surveyType->max;

        $answersByWorkerQuery = SurveyAnswer::query()
            ->select([
                'surveys.id as survey_id',
                'survey_answers.worker_id',
                'survey_answers.id',
                'survey_answers.question_id',
                'survey_answers.answer_int',
                'survey_answers.created_at as answer_created_at',
                'survey_questions.code',
                'surveys.created_at as survey_created_at',
            ])
            ->join('survey_questions', 'survey_questions.id', '=', 'survey_answers.question_id')
            ->join('surveys', 'surveys.id', '=', 'survey_questions.survey_id')
            ->join('survey_types', 'survey_types.id', '=', 'surveys.survey_type_id')
            ->where('surveys.created_at', '<=', $toDate)
            ->where('surveys.date_end', '>=', $fromDate)
            ->where('survey_types.code', 'professional burnout')
            ->with([
                'worker.department',
                'worker.chief',
                'worker.information.hrUser',
                'worker.projects',
                'worker.position',
            ]);

        if (!empty($requestData['worker'])) {
            $answersByWorkerQuery->whereIn('survey_answers.worker_id', $requestData['worker']);
        }

        if (!empty($requestData['chief'])) {
            $chiefIds = $requestData['chief'];
            $answersByWorkerQuery->whereHas('worker.chief', function ($q) use ($chiefIds) {
                $q->whereKey($chiefIds);
            });
        }

        if (!empty($requestData['hr'])) {
            $hrIds = $requestData['hr'];
            $answersByWorkerQuery->whereHas('worker.information.hrUser', function ($q) use ($hrIds) {
                $q->whereKey($hrIds);
            });
        }

        if (!empty($requestData['department'])) {
            $departmentIds = $requestData['department'];
            $answersByWorkerQuery->whereHas('worker.department', function ($q) use ($departmentIds) {
                $q->whereKey($departmentIds);
            });
        }

        if (!empty($requestData['project'])) {
            $projectIds = $requestData['project'];
            $answersByWorkerQuery->whereHas('worker.projects', function ($q) use ($projectIds) {
                $q->whereKey($projectIds);
            });
        }

        $answersByWorker = $answersByWorkerQuery->orderBy('surveys.id')
            ->orderBy('survey_answers.worker_id')
            ->get()
            ->groupBy('worker_id');

        $workerResults = collect();

        foreach ($answersByWorker as $answers) {
            /** @var SurveyAnswer $first */
            $first  = $answers->first();
            $worker = $first->worker;

            $exhaustion        = 0;
            $depersonalization = 0;
            $reduction         = 0;

            foreach ($answers as $answer) {
                $templateCode = $answer->code;

                if (! isset($templateQuestions[$templateCode])) {
                    continue;
                }

                $question = $templateQuestions[$templateCode];

                $value = (int) $answer->answer_int;
                if (! empty($question['reverseValues'])) {
                    $value = $maxValue - $value;
                }

                switch ($question['burnoutScale']) {
                    case SurveyBurnoutScaleTypeEnum::EXHAUSTION:
                        $exhaustion += $value;
                        break;
                    case SurveyBurnoutScaleTypeEnum::REDUCTION:
                        $reduction += $value;
                        break;
                    case SurveyBurnoutScaleTypeEnum::DEPERSONALIZATION:
                        $depersonalization += $value;
                        break;
                }
            }

            $indexBurnout = $this->calculateBurnoutIndex(
                $exhaustion,
                $depersonalization,
                $reduction
            );

            $dateSurveyCreate = $answers->min('survey_created_at');
            $dateSurveyAnswer = $answers->max('answer_created_at');
            $hrUser = $worker->information?->hr?->user;

            $workerResults->push([
                'worker'                 => $worker,
                'department'             => $worker->department,
                'chief'                  => $worker->chief,
                'hr'                     => $hrUser,
                'projects'               => $worker->projects,

                'emotional_burnout'      => $exhaustion,
                'depersonalization'      => $depersonalization,
                'reduction_achievements' => $reduction,
                'index_burnout'          => $indexBurnout,

                'date_survey_create'     => $dateSurveyCreate
                    ? Carbon::parse($dateSurveyCreate)
                    : null,
                'date_survey_answer'     => $dateSurveyAnswer
                    ? Carbon::parse($dateSurveyAnswer)
                    : null,
            ]);
        }

        $summary = [
            'emotional_burnout'      => $workerResults->avg('emotional_burnout') ?? 0,
            'depersonalization'      => $workerResults->avg('depersonalization') ?? 0,
            'reduction_achievements' => $workerResults->avg('reduction_achievements') ?? 0,
            'index_burnout'          => $workerResults->avg('index_burnout') ?? 0,
        ];

        return [
            'summary' => $summary,
            'workers' => $workerResults,
        ];
    }

    /**
     * @param int $exhaustion
     * @param int $depersonalization
     * @param int $reduction
     * @return float
     */
    public function calculateBurnoutIndex(int $exhaustion, int $depersonalization, int $reduction): float
    {
        $EXHAUSTION_MAX        = 54;
        $DEPERSONALIZATION_MAX = 30;
        $REDUCTION_MAX         = 48;

        $exhaustionNorm        = (float) $exhaustion / $EXHAUSTION_MAX;
        $depersonalizationNorm = (float) $depersonalization / $DEPERSONALIZATION_MAX;
        $reductionNormInverted = 1 - (float) $reduction / $REDUCTION_MAX;

        $burnoutIndex = sqrt(
            (
                $exhaustionNorm ** 2 +
                $depersonalizationNorm ** 2 +
                $reductionNormInverted ** 2
            ) / 3
        );

        return $burnoutIndex;
    }
}
