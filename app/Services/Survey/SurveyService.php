<?php

namespace App\Services\Survey;

use App\Enums\ChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SurveyAnonymityEnum;
use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Enums\WorkerStatusEnum;
use App\Events\SurveyChanged;
use App\Exceptions\Survey\CompletedSurveyException;
use App\Exceptions\Survey\SurveyNotAssignedToWorkerException;
use App\Facade\Notifier;
use App\Filters\SurveyAnswersFilter;
use App\Filters\SurveyFilter;
use App\Filters\SurveyTemplatesFilter;
use App\Filters\WorkerSurveyFilter;
use App\Http\Requests\Survey\StoreSurveyAnswersRequest;
use App\Http\Requests\Survey\SurveyIndexRequest;
use App\Http\Requests\Survey\SurveyRequest;
use App\Http\Requests\Survey\SurveyTemplatesFilterRequest;
use App\Http\Requests\Survey\WorkerCompletedOffboardingSurveyRequest;
use App\Http\Requests\Survey\WorkerCompletedSurveyRequest;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyAnswerVariants;
use App\Models\Survey\SurveyCompletion;
use App\Models\User\User;
use App\Models\Worker\Worker;
use App\Services\CalendarService;
use App\Services\DTO\Filters\OffboardingSurveyFilterDTO;
use App\Services\DTO\Filters\SurveyFilterDTO;
use App\Services\DTO\Filters\WorkerSurveyFilterDTO;
use App\Services\DTO\Notification\Action\ActionOptionDTO;
use App\Services\Notification\NotificationData;
use App\Services\NotifyService;
use App\Services\EnpsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class SurveyService
{
    public function __construct(
        private NotifyService $notifyService,
        private HtmlConverter $htmlConverter,
        private readonly EnpsService   $enpsService,
    ) {
    }

    public function cronCheckSurveyDeadlinesHandle(): void
    {
        $deadline = Carbon::now()->startOfDay()->subSecond()->format('Y-m-d');
        $surveys = Survey::whereDate('date_end', $deadline)->get();
        foreach ($surveys as $survey) {
            $survey->status = SurveyStatusEnum::Closed;
            $survey->save();
        }
    }

    public function getWorkerSurveysWithPaginate(SurveyIndexRequest $request, User $user): LengthAwarePaginator
    {
        $surveys = Survey::query()
            ->with([
                'completions' => function ($q) use ($user) {
                    $q->where('worker_id', $user->worker->id);
                },
            ])
            ->whereIn('status', [SurveyStatusEnum::Active, SurveyStatusEnum::Closed])
            ->whereHas('completions', function ($q) use ($user) {
                $q->where('worker_id', $user->worker->id);
            })
            ->orderByRaw("(status = ?) DESC", [SurveyStatusEnum::Active])
            ->orderBy('date_end', 'asc');

        return $surveys->paginateFilter($request->get('perPage'));
    }

    public function save(StoreSurveyAnswersRequest $request, Survey $survey): SurveyCompletion
    {
        $worker = Auth::user()->worker;
        $workerCompletedSurvey = $worker->surveysCompletion->where('survey_id', $survey->id)->first();

        if (!$workerCompletedSurvey) {
            throw new SurveyNotAssignedToWorkerException();
        }

        if ($workerCompletedSurvey->completed) {
            throw new CompletedSurveyException();
        }

        $pages = $request->safe()->pages;

        DB::transaction(function () use ($worker, $pages, $workerCompletedSurvey, $survey) {
            $now = Carbon::now();
            $variantsToInsert = [];

            foreach ($pages as $pageData) {
                foreach ($pageData['answers'] as $item) {
                    $answer = SurveyAnswer::create([
                        'worker_id' => $worker->id,
                        'question_id' => $item['question_id'],
                        'answer_text' => $item['answer'] ?? null,
                        'answer_int' => $item['integer'] ?? null,
                    ]);

                    if (!empty($item['variants'])) {
                        foreach ($item['variants'] as $variant) {
                            $variantsToInsert[] = [
                                'survey_answer_id' => $answer->id,
                                'variant_id' => $variant['id'],
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }
            }

            if ($survey->type->code === SurveyTypeEnum::Impulse) {
                $this->notifyHrAboutPassingImpulse($survey, $workerCompletedSurvey);
                $this->enpsService->createENPSRecord($workerCompletedSurvey);
            }

            if (!empty($variantsToInsert)) {
                SurveyAnswerVariants::insert($variantsToInsert);
            }

            $workerCompletedSurvey->update(['completed' => true, 'completion_date' => $now]);
        });

        return $workerCompletedSurvey;
    }

    public function getAllSurveysWithPaginate(SurveyRequest $request): LengthAwarePaginator
    {
        $surveys = Survey::filter($request->all(), SurveyFilter::class)->withCount('questions');

        return $surveys->paginateFilter($request->get('perPage'));
    }

    public function getSurveysWithTemplatesFilter(SurveyTemplatesFilterRequest $request): Collection
    {
        return Survey::filter($request->all(), SurveyTemplatesFilter::class)->get();
    }

    public function getSurveyWorkers(WorkerCompletedSurveyRequest $request, Survey $survey): LengthAwarePaginator
    {
        $surveyWorkers = $survey
            ->completions()
            ->filter($request->all(), WorkerSurveyFilter::class);

        if (Gate::allows('viewWorkers', $survey)) {
            $surveyWorkers->with('worker');
        }

        return $surveyWorkers->paginateFilter($request->get('perPage'));
    }

    public function getSurveyAnswers(WorkerCompletedSurveyRequest $request, Survey $survey): LengthAwarePaginator
    {
        $query = $survey->questions()
            ->with([
                'variants',
                'answerType',
                'answers' => function ($query) use ($request) {
                    $this->applyAnswerFilters($query, $request);
                },
            ])
            ->orderBy('question_number')
            ->filter($request->all(), SurveyAnswersFilter::class);

        return $query->paginateFilter($request->get('perPage'));
    }

    public function getFilters(): object
    {
        $surveys = Survey::query()
            ->whereIn('status', [SurveyStatusEnum::Active, SurveyStatusEnum::Closed])
            ->get();

        return (new SurveyFilterDTO($surveys))();
    }

    public function getWorkerFilters(Survey $survey): object
    {
        $surveyWorkers = $survey
            ->completions()
            ->get();

        return (new WorkerSurveyFilterDTO($surveyWorkers))();
    }

    private function applyAnswerFilters(HasMany $query, WorkerCompletedSurveyRequest|WorkerCompletedOffboardingSurveyRequest $request): void // todo: text type $request
    {
        if ($request->has('workers') && !empty($request->get('workers'))) {
            $query->whereIn('worker_id', $request->get('workers'));
        }

        if ($request->has('departments') && !empty($request->get('departments'))) {
            $query->whereHas('worker', function (Builder $q) use ($request) {
                $q->whereIn('department_id', $request->get('departments'));
            });
        }

        if ($request->has('chiefs') && !empty($request->get('chiefs'))) {
            $query->whereHas('worker', function (Builder $q) use ($request) {
                $q->whereIn('chief_id', $request->get('chiefs'));
            });
        }
    }

    public function onActive(SurveyChanged $surveyChanged): void
    {
        $survey = $surveyChanged->survey;
        $survey->load([
            'workers',
        ]);

        $this->notifyWorkerDayBeforeSurvey($survey);
    }

    public function notifySurveyWorker(): void
    {
        $start = Carbon::tomorrow();
        $end   = CalendarService::getNextWorkingDay($start);
        $surveys = Survey::query()
            ->whereBetween('date_end', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->where('status', SurveyStatusEnum::Active->value)
            ->get();
        foreach ($surveys as $survey) {
            $this->notifyWorkerDayBeforeSurvey($survey);
        }
    }

    /**
     * @param mixed $survey
     * @return void
     */
    public function notifyWorkerDayBeforeSurvey(Survey $survey): void
    {
        $surveyCompletions = $survey->workerNotCompletedSurveyCompletions()
            ->whereHas('worker', fn ($query) => $query->withStatus(
                [
                    WorkerStatusEnum::ProbationPeriod->value,
                    WorkerStatusEnum::Working->value
                ]
            ))
            ->get();
        /** @var SurveyCompletion $surveyCompletion */
        foreach ($surveyCompletions as $surveyCompletion) {
            $worker = $surveyCompletion->worker()->first();

            if ($worker === null) {
                continue;
            }

            $user = $worker->user()->first();

            if ($user === null) {
                continue;
            }

            $messageText = __('cron.notify_worker_survey_active', [
                $worker->fio,
                Config::get('app.url') . '/survey/'. $survey->id,
                $survey->name,
            ]);
            if ($survey->approximate_time) {
                $messageText .= ' ' . __(
                    'cron.notify_worker_survey_approximate_time',
                    [
                            $survey->approximate_time
                        ]
                );
            }
            if ($survey->date_end) {
                $messageText .= ' ' . __(
                    'cron.notify_worker_survey_deadline_tail',
                    [
                            Carbon::parse($survey->date_end)->format('d-m-Y')
                        ]
                );
            }
            if (in_array($survey->anonymity, [SurveyAnonymityEnum::Anonymous, SurveyAnonymityEnum::HrOnly], true)) {
                $messageText .= "\n" . __('cron.notify_worker_survey_anonymous_survey');
            }
            $this->notifyService->notifyUser( // todo: text text Notifier::notify
                $messageText,
                $user,
            );
        }
    }

    /**
     * Text text survey text text
     */
    public function getSurveyPagesWithQuestions(Survey $survey): Survey
    {
        return $survey->load([
            'pages' => function ($query) {
                $query->orderBy('number');
            },
            'pages.questions' => function ($query) {
                $query->orderBy('question_number');
            },
            'pages.questions.variants',
            'pages.questions.answerType',
            'type',
            'workers.department',
        ]);
    }

    /**
     * Text text text notifications text workers text text
     *
     * @param Survey $survey
     * @param Collection $workers Text workers text notifications
     * @return void
     */
    private function notifyWorkers(Survey $survey, Collection $workers): void
    {
        if ($workers->isNotEmpty() && !$workers->first()->relationLoaded('user')) {
            $workers->load('user');
        }

        foreach ($workers as $worker) {
            $user = $worker->user;

            if ($user === null) {
                continue;
            }

            Notifier::notify(
                new NotificationData(
                    type: NotificationTypeEnum::InformUserAboutSurvey,
                    user: $user,
                    actionOptionDto: new ActionOptionDTO($survey->id),
                    viewName: 'inform-user-about-survey',
                    viewData: ['worker' => $worker, 'survey' => $survey]
                ),
            );
        }
    }

    public function cronSendOffboardingSurveyToUser(): void
    {
        $offboardingSurvey = Survey::query()
            ->withType(SurveyTypeEnum::Offboarding)
            ->first();

        if (! $offboardingSurvey) {
            Log::error('Text text notifications cron:send-offboarding-survey-to-user text text text survey text text offboarding ');
            return;
        }

        $workers = Worker::query()
            ->with(['user'])
            ->whereHas('dismissal', function (Builder $query) {
                $query->whereDate('last_day', '=', today()->format('Y-m-d'));
            })
            ->get();

        foreach ($workers as $worker) {
            $offboardingSurvey->completions()->create(['worker_id' => $worker->id]);

            $this->notifyService->notifyUser(
                implode(PHP_EOL, [
                    'Text Text',
                    'Text text text text text text text text text text text text text.'
                ]),
                $worker->user
            );
        }
    }

    public function sendHrNotificationOfCompletedSurvey(SurveyCompletion $surveyCompletion): void
    {
        $surveyCompletion->loadMissing('worker');

        $worker = $surveyCompletion->worker;
        $completionTime = now()->format('H:i');

        $view = view('notify.survey.completed-survey', compact('worker', 'completionTime'))->render();
        $message = $this->htmlConverter->convert($view);

        $this->notifyService->notifyChannel($message, ChannelEnum::HrNotifications->value);
    }

    public function getOffboardingSurveyWorkers(WorkerCompletedOffboardingSurveyRequest $request): LengthAwarePaginator
    {
        $offboardingSurvey = Survey::query()
            ->withType(SurveyTypeEnum::Offboarding)
            ->first();

        $query = $offboardingSurvey
            ->completions()
            ->filter($request->all(), WorkerSurveyFilter::class);

        return $query->paginateFilter($request->get('perPage'));
    }

    public function getOffboardingSurveyAnswers(WorkerCompletedOffboardingSurveyRequest $request): LengthAwarePaginator
    {
        $offboardingSurvey = Survey::query()
            ->withType(SurveyTypeEnum::Offboarding)
            ->first();

        $query = $offboardingSurvey->questions()
            ->with([
                'variants',
                'answers' => function ($query) use ($request) {
                    $this->applyAnswerFilters($query, $request);
                },
            ])
            ->orderBy('question_number')
            ->filter($request->all(), SurveyAnswersFilter::class);

        return $query->paginateFilter($request->get('perPage'));
    }

    public function getOffboardingFilters(): object
    {
        $offboardingSurvey = Survey::query()
            ->withType(SurveyTypeEnum::Offboarding)
            ->first();

        $surveyWorkers = $offboardingSurvey
            ->completions()
            ->get();

        return (new OffboardingSurveyFilterDTO($surveyWorkers))();
    }

    private function notifyHrAboutPassingImpulse(Survey $survey, SurveyCompletion $workerCompletedSurvey): void
    {
        $worker = $workerCompletedSurvey->worker;

        $hr = $worker->information?->hr;

        if ($hr) {
            $this->notifyService->notifyUser(
                __(
                    'cron.notify_hr_about_impulse',
                    [$worker->fio, $survey->name, $workerCompletedSurvey->completion_date->format('Y-m-d H:i')]
                ),
                $hr->user,
            );
        }
    }
}
