<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('cron:check_survey_deadlines')->runInBackground()->dailyAt('00:00');
        $schedule->command('cron:notify_day_before_survey')->runInBackground()->dailyAt('8:00');
        $schedule->command('cron:send-offboarding-survey-to-user')->runInBackground()->daily()->at('10:00');
        $schedule->command('cron:create-new-pulse-survey')->runInBackground()->quarterlyOn(time: '9:00');
        $schedule->command('cron:create_survey_burnout')->cron('0 0 1 1,5,9 *'); // first text January, May, September
    }
}
