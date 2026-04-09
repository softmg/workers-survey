<?php

namespace Feature\Services\Survey;

use App\Console\Commands\CronNotifyDayBeforeSurvey;
use App\Enums\SurveyStatusEnum;
use App\Enums\WorkerStatusEnum;
use App\Jobs\MattermostSendUserJob;
use App\Models\ProductionCalendar\ProductionCalendarDay;
use App\Models\ProductionCalendar\ProductionCalendarMonth;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use App\Models\User\User;
use App\Models\Worker\Worker;
use App\Models\Worker\WorkerStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\Feature\FeatureTestCase;

class NotifySurveyWorkerServiceTest extends FeatureTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_notifies_active_workers_until_next_working_day_and_skips_deleted(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-05-16'));

        $this->markDayOff(Carbon::parse('2025-05-19'));

        $workingStatusId = WorkerStatus::where('code', WorkerStatusEnum::Working->value)->value('id');

        $activeWorker = $this->createWorkerWithStatus($workingStatusId);
        $deletedWorker = $this->createWorkerWithStatus($workingStatusId);
        $deletedWorker->delete();

        $datesInRange = [
            Carbon::parse('2025-05-17'),
            Carbon::parse('2025-05-18'),
            Carbon::parse('2025-05-19'),
            Carbon::parse('2025-05-20'),
        ];

        $surveys = [];
        foreach ($datesInRange as $date) {
            $survey = $this->createSurvey($date);
            $this->createCompletion($survey, $activeWorker);
            $surveys[$date->toDateString()] = $survey;
        }

        $this->createCompletion($surveys['2025-05-19'], $deletedWorker);

        $outOfRangeSurvey = $this->createSurvey(Carbon::parse('2025-05-21'));
        $this->createCompletion($outOfRangeSurvey, $activeWorker);

        $this->artisan(CronNotifyDayBeforeSurvey::class)->assertSuccessful();

        Bus::assertDispatchedTimes(MattermostSendUserJob::class, 4);
    }

    public function test_does_not_notify_on_weekends(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-05-17'));

        $worker = $this->createWorkerWithStatus(
            WorkerStatus::where('code', WorkerStatusEnum::Working->value)->value('id')
        );

        $survey = $this->createSurvey(Carbon::parse('2025-05-18'));
        $this->createCompletion($survey, $worker);

        $this->artisan(CronNotifyDayBeforeSurvey::class)->assertSuccessful();

        Bus::assertNothingDispatched();
    }

    public function test_does_not_notify_on_holidays(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-05-20'));

        $this->markDayOff(Carbon::parse('2025-05-20'));

        $worker = $this->createWorkerWithStatus(
            WorkerStatus::where('code', WorkerStatusEnum::Working->value)->value('id')
        );

        $survey = $this->createSurvey(Carbon::parse('2025-05-21'));
        $this->createCompletion($survey, $worker);

        $this->artisan(CronNotifyDayBeforeSurvey::class)->assertSuccessful();

        Bus::assertNothingDispatched();
    }

    private function createWorkerWithStatus(?int $statusId): Worker
    {
        $worker = User::factory()->create()->worker;

        if ($statusId !== null) {
            $worker->statusId = $statusId;
            $worker->save();
        }

        return $worker;
    }

    private function createSurvey(Carbon $dateEnd): Survey
    {
        return Survey::factory()->create([
            'status' => SurveyStatusEnum::Active->value,
            'date_end' => $dateEnd->toDateString(),
        ]);
    }

    private function createCompletion(Survey $survey, Worker $worker): void
    {
        SurveyCompletion::factory()->create([
            'worker_id' => $worker->id,
            'survey_id' => $survey->id,
            'completed' => false,
        ]);
    }

    private function markDayOff(Carbon $date): void
    {
        $month = ProductionCalendarMonth::query()->firstOrCreate(
            ['year' => $date->year, 'month' => $date->month],
            ['working_hours' => 0]
        );

        ProductionCalendarDay::query()->updateOrCreate(
            ['date' => $date->toDateString()],
            [
                'month_id' => $month->id,
                'day' => $date->day,
                'is_off' => true,
            ]
        );
    }
}
