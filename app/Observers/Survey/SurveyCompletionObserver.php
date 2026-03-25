<?php

namespace App\Observers\Survey;

use App\Models\Survey\SurveyCompletion;
use App\Services\Survey\SurveyService;

readonly class SurveyCompletionObserver
{
    public function __construct(
        private SurveyService $surveyService,
    ) {
    }

    public function updated(SurveyCompletion $surveyCompletion): void
    {
        if ($surveyCompletion->isDirty('completion_date')) {
            $this->surveyService->sendHrNotificationOfCompletedSurvey($surveyCompletion);
        }
    }
}
