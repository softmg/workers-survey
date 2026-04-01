<?php

namespace App\Services\Survey;

use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Exceptions\Survey\CannotSetTemplateException;
use App\Exceptions\Survey\CannotUpdateSurveyException;
use App\Exceptions\Survey\CannotUpdateSurveyStructureWithAnswersException;
use App\Exceptions\Survey\DefaultSurveyTypeNotFoundException;
use App\Exceptions\Survey\InvalidStatusTransitionException;
use App\Exceptions\Survey\InvalidSurveyDateException;
use App\Http\Requests\Survey\CreateSurveyAggregateRequest;
use App\Http\Requests\Survey\UpdateSurveyAggregateRequest;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveyAggregateService
{
    public function __construct(
        private SurveyPageService $pageService,
        private SurveyQuestionService $questionService,
        private SurveyVariantService $variantService,
        private SurveyCompletionService $completionService,
        private SurveyRecipientsService $surveyRecipientsService,
    ) {
    }

    private function getDefaultSurveyTypeId(): int
    {
        $surveyType = SurveyType::firstWhere('code', SurveyTypeEnum::default());

        if (!$surveyType || !$surveyType->id) {
            throw new DefaultSurveyTypeNotFoundException();
        }

        return $surveyType->id;
    }

    /**
     * Text text text survey - text text text text text
     */
    private function validateDateEnd(?string $dateEnd): void
    {
        if (!empty($dateEnd)) {
            $date = Carbon::parse($dateEnd);
            $today = Carbon::today();

            if ($date->lte($today)) {
                throw new InvalidSurveyDateException();
            }
        }
    }

    private function validateStatusForTemplate(bool $isTemplate, SurveyStatusEnum $status): void
    {
        if ($isTemplate && $status !== SurveyStatusEnum::Created) {
            throw new CannotSetTemplateException();
        }
    }

    private function validateBeforeTransaction(array $validated, ?Survey $survey = null): void
    {
        $this->validateDateEnd($validated['survey']['date_end'] ?? null);

        $isTemplate = $validated['survey']['is_template'] ?? false;
        $status = $survey
            ? $survey->status
            : SurveyStatusEnum::from($validated['survey']['status']);

        $this->validateStatusForTemplate($isTemplate, $status);
    }

    private function validateUpdateBeforeTransaction(array $validated, Survey $survey): void
    {
        if (!empty($validated['survey']['survey_pages'])) {
            $hasAnswers = $survey->answers()->exists();

            if ($hasAnswers) {
                throw new CannotUpdateSurveyStructureWithAnswersException();
            }
        }
    }

    /**
     * Text survey text immutable text (text approximate_time, status, date_end)
     */
    private function updateImmutableSurvey(Survey $survey, array $validated): Survey
    {
        $updateData = [];

        if (isset($validated['survey']['approximate_time'])) {
            $updateData['approximate_time'] = $validated['survey']['approximate_time'];
        }

        if (isset($validated['survey']['status'])) {
            $newStatus = SurveyStatusEnum::from($validated['survey']['status']);
            $allowedTransitions = $survey->status->transitions();

            if (!array_key_exists($newStatus->value, $allowedTransitions)) {
                throw new InvalidStatusTransitionException();
            }

            $updateData['status'] = $newStatus;
        }

        if (isset($validated['survey']['date_end'])) {
            $updateData['date_end'] = $validated['survey']['date_end'];
        }

        $surveyData = $validated['survey'] ?? [];

        $forbiddenFields = array_diff_key($surveyData, array_flip(Survey::IMMUTABLE_UPDATE_ALLOWED_FIELDS));

        if (!empty($forbiddenFields)) {
            throw new CannotUpdateSurveyException();
        }

        if (!empty($updateData)) {
            $survey->update($updateData);
        }

        return $survey;
    }

    /**
     * Text ID workers text workers_id text departments_id text text text
     */
    private function getWorkerIds(array $validated): Collection
    {
        return $this->surveyRecipientsService->resolveWorkerIds(
            $validated['workers_id'] ?? [],
            $validated['departments_id'] ?? []
        );
    }

    private function ensureRecipientsExist(Collection $workerIds): void
    {
        if ($workerIds->isNotEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'workers_id' => __('validation.survey_should_have_recipients'),
        ]);
    }

    /**
     * Text text questions text text text
     */
    private function recalculateQuestionNumbers(Survey $survey): void
    {
        $questions = $survey->questions()
            ->orderBy('question_number')
            ->get();

        $questionNumber = 1;
        $updates = [];

        foreach ($questions as $question) {
            if ($question->question_number !== $questionNumber) {
                $updates[] = [
                    'id' => $question->id,
                    'question_number' => $questionNumber,
                ];
            }
            $questionNumber++;
        }

        if (!empty($updates)) {
            $ids = collect($updates)->pluck('id')->toArray();
            $caseStatements = collect($updates)->map(function ($update) {
                return "WHEN {$update['id']} THEN {$update['question_number']}";
            })->implode(' ');

            DB::table('survey_questions')
                ->whereIn('id', $ids)
                ->update([
                    'question_number' => DB::raw("CASE id {$caseStatements} END"),
                ]);
        }
    }

    public function save(CreateSurveyAggregateRequest $request): Survey
    {
        $validated = $request->safe();

        $this->validateBeforeTransaction($validated->all());

        $isTemplate = $validated['survey']['is_template'] ?? false;

        return DB::transaction(function () use ($validated, $isTemplate) {
            $surveyTypeId = $validated['survey']['survey_type_id'] ?? $this->getDefaultSurveyTypeId();

            $survey = Survey::create([
                'name' => $validated['survey']['name'],
                'description' => $validated['survey']['description'] ?? null,
                'survey_type_id' => $surveyTypeId,
                'date_end' => $validated['survey']['date_end'] ?? null,
                'approximate_time' => $validated['survey']['approximate_time'] ?? null,
                'anonymity' => $validated['survey']['anonymity'],
                'status' => SurveyStatusEnum::Created,
                'is_template' => $isTemplate,
            ]);

            if (!empty($validated['survey']['survey_pages'])) {
                $answerTypes = $this->questionService->collectAnswerTypes($validated->all());
                $pageMapping = $this->pageService->createPages($survey, $validated['survey']['survey_pages']);

                $questionNumber = 1;
                $result = $this->questionService->createQuestions(
                    $survey,
                    $validated->all(),
                    $pageMapping,
                    $answerTypes,
                    $questionNumber
                );

                if (!empty($result['variants'])) {
                    $this->variantService->createVariants($result['variants'], $result['questions'], now());
                }
            } else {
                $this->pageService->createDefaultPage($survey);
            }

            $workerIds = $this->getWorkerIds($validated->all());
            if (!$isTemplate) {
                $this->ensureRecipientsExist($workerIds);
                $this->completionService->syncCompletions($survey, $workerIds);
            }

            if (isset($validated['survey']['status']) &&
                SurveyStatusEnum::from($validated['survey']['status']) === SurveyStatusEnum::Active &&
                !$isTemplate) {
                $survey->update(['status' => SurveyStatusEnum::Active]);
            }

            $survey->load(['pages.questions.variants', 'type']);

            return $survey;
        });
    }

    public function update(UpdateSurveyAggregateRequest $request, Survey $survey): Survey
    {
        $validated = $request->safe();

        $this->validateBeforeTransaction($validated->all(), $survey);
        $this->validateUpdateBeforeTransaction($validated->all(), $survey);

        $isTemplate = $validated['survey']['is_template'] ?? false;
        $isImmutable = $survey->isImmutable();

        return DB::transaction(function () use ($validated, $survey, $isTemplate, $isImmutable) {
            if ($isImmutable) {
                $survey = $this->updateImmutableSurvey($survey, $validated->all());
                $survey->load(['pages.questions.variants', 'type']);
                return $survey;
            }

            $surveyTypeId = $validated['survey']['survey_type_id'] ?? $this->getDefaultSurveyTypeId();

            $updateData = [
                'name' => $validated['survey']['name'],
                'description' => $validated['survey']['description'] ?? null,
                'survey_type_id' => $surveyTypeId,
                'date_end' => $validated['survey']['date_end'] ?? null,
                'approximate_time' => $validated['survey']['approximate_time'] ?? null,
                'anonymity' => $validated['survey']['anonymity'],
                'is_template' => $isTemplate,
            ];

            if (isset($validated['survey']['status'])) {
                $newStatus = SurveyStatusEnum::from($validated['survey']['status']);
                $allowedTransitions = $survey->status->transitions();

                if (!array_key_exists($newStatus->value, $allowedTransitions)) {
                    throw new InvalidStatusTransitionException();
                }

                $updateData['status'] = $newStatus;
            }

            $survey->update($updateData);

            $existingPages = $survey->pages()
                ->with('questions.variants')
                ->get()
                ->keyBy('id');

            $pageMapping = $this->pageService->syncPages($survey, $validated->all(), $existingPages);

            $existingQuestions = $survey->questions()
                ->with('variants')
                ->get()
                ->keyBy('id');

            if (!empty($validated['survey']['survey_pages'])) {
                $answerTypes = $this->questionService->collectAnswerTypes($validated->all());
                $questionNumber = 1;
                $result = $this->questionService->syncQuestions(
                    $survey,
                    $validated->all(),
                    $existingQuestions,
                    $pageMapping,
                    $answerTypes,
                    $questionNumber
                );

                if (!empty($result['variants'])) {
                    $this->variantService->syncVariants(
                        $result['variants'],
                        $existingQuestions,
                        $result['questions'],
                        $answerTypes,
                        now()
                    );
                }
            }

            $this->recalculateQuestionNumbers($survey);

            $workerIds = $this->getWorkerIds($validated->all());
            $this->ensureRecipientsExist($workerIds);
            $this->completionService->syncCompletions($survey, $workerIds);

            $survey->load(['pages.questions.variants', 'type']);

            return $survey;
        });
    }
}
