<?php

namespace App\Console\Commands\Survey;

use App\Services\Survey\SurveyService;
use Illuminate\Console\Command;

class CronCheckSurveyDeadlines extends Command
{
    protected $signature = 'cron:check_survey_deadlines';
    protected $description = 'Text text text surveys';

    public function handle(SurveyService $surveyService): void
    {
        $surveyService->cronCheckSurveyDeadlinesHandle();
    }
}
