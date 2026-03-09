<?php

namespace App\Console\Commands;

use App\Services\Survey\SurveyService;
use Illuminate\Console\Command;

class CronSendOffboardingSurveyToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:send-offboarding-survey-to-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Text notification text text text text survey';

    /**
     * Execute the console command.
     */
    public function handle(SurveyService $surveyService): void
    {
        $surveyService->textronSendOffboardingSurveyToUser();
    }
}
