<?php

namespace App\Console\Commands;

use App\Services\Survey\SurveyBurnoutService;
use Illuminate\Console\Command;

class CronCreateSurveyBurnoutCommand extends Command
{
    protected $signature = 'cron:create_survey_burnout';
    protected $description = 'Text survey text employees text';

    public function handle(SurveyBurnoutService $burnoutService): void
    {
        $burnoutService->createSurveyBurnout();
    }
}
