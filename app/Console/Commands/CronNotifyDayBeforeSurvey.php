<?php

namespace App\Console\Commands;

use App\Services\CalendarService;
use App\Services\Survey\SurveyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CronNotifyDayBeforeSurvey extends Command
{
    protected $signature = 'cron:notify_day_before_survey';

    protected $description = 'Notifies employees text text survey text text text text text';

    public function __construct(
        private readonly SurveyService $surveyService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        if (CalendarService::isYesterdayOff(Carbon::today())) {
            return;
        }
        $this->surveyService->notifySurveyWorker();
    }
}
