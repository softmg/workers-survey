<?php

namespace App\Services\Survey;

use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Enums\WorkerDismissalStatusEnum;
use App\Models\ProductionCalendar\ProductionCalendarMonth;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use App\Models\Worker\Worker;
use App\Services\CalendarService;
use App\Services\NotifyService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class ImpulseService
{
    public function __construct(
        private CalendarService $calendarService,
        private NotifyService $notifyService,
    ) {
    }

    private const IMPULSE_NAME_TEMPLATE = 'Impulse text %s';

    private const WORKING_DAY_DATE_END_NUMBER = 5;

    public function createImpulseSurvey(int $monthSub): void
    {
        if ($this->hasImpulseInNMonth($monthSub)) {
            return;
        }

        $survey = $this->create($monthSub);
        $this->notifyWorkersAboutSurvey($survey);
    }

    private function getImpulseTemplate(): Survey
    {
        return Survey::query()
            ->where('is_template', 1)
            ->whereHas('type', fn (Builder $q) => $q->where('code', SurveyTypeEnum::Impulse))
            ->first();
    }

    private function hasImpulseInNMonth(int $monthSub): bool
    {
        $currentMonth = now()->subMonths($monthSub)->month;

        return Survey::query()
            ->whereHas('type', fn (Builder $q) => $q->where('code', SurveyTypeEnum::Impulse))
            ->whereMonth('created_at', $currentMonth)
            ->where('is_template', 0)
            ->exists();
    }

    private function create(int $monthSub): Survey
    {
        try {
            DB::beginTransaction();
            $template = $this->getImpulseTemplate();
            $impulse = $template->replicate();

            $impulse->name = sprintf(self::IMPULSE_NAME_TEMPLATE, ProductionCalendarMonth::$months[now()->subMonths($monthSub)->month]);
            $impulse->date_end = $this->calculateDateEnd() ?? now()->addDays(5);
            $impulse->is_template = 0;
            $impulse->status = SurveyStatusEnum::Active;
            $impulse->save();

            $workers = $this->getWorkersToComplete();
            foreach ($workers as $worker) {
                $impulse->workerCompletedSurveys()
                    ->create(['worker_id' => $worker->id, 'survey_id' => $impulse->id]);
            }

            foreach ($template->questions as $question) {
                $qCopy = $question->replicate();
                $qCopy->survey_id = $impulse->id;
                $qCopy->push();

                foreach ($question->variants as $variant) {
                    $vCopy = $variant->replicate();
                    $vCopy->question_id = $qCopy->id;
                    $vCopy->push();
                }
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        return $impulse;
    }

    private function calculateDateEnd(): ?Carbon
    {
        return $this->calendarService->getNthWorkingDayOfMonth(self::WORKING_DAY_DATE_END_NUMBER, now());
    }

    private function getWorkersToComplete(): Collection
    {
        return Worker::query()
            ->whereDate('hiring_day', '<', now()->addMonth())
            ->whereDoesntHave('dismissals', function (Builder $q) {
                $q->where('status', WorkerDismissalStatusEnum::Worked);
            })
            ->get();
    }

    private function notifyWorkersAboutSurvey(Survey $survey): void
    {
        $survey
            ->workerCompletedSurveys()
            ->each(function (SurveyCompletion $surveyCompletion) {
                $this->notifyService->notifyUser(__('cron.notify_workers_about_impulse'), $surveyCompletion->worker->user);
            });
    }
}
