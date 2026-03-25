<?php

namespace App\Listeners;

use App\Enums\SurveyStatusEnum;
use App\Events\SurveyChanged;
use App\Services\Survey\SurveyService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class HandleSurveyChangedListener implements ShouldQueueAfterCommit
{
    public function __construct(
        private readonly SurveyService $surveyService,
    ) {
    }

    public function handle(SurveyChanged $event): void
    {
        $from = $event->oldStatus;
        $to = $event->newStatus;

        if ($from === SurveyStatusEnum::Created && $to === SurveyStatusEnum::Active) {
            $this->surveyService->onActive($event);
        }
    }
}
