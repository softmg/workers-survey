<?php

namespace Tests\Feature\Api;

use App\Enums\UserRoleEnum;
use App\Exports\SurveysResultsExport;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use App\Models\Worker\Worker;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\FeatureTestCase;

class ExportSurveysResultTest extends FeatureTestCase
{
    public function test_export_surveys_result_includes_only_completed_workers(): void
    {
        Excel::fake();

        $survey = Survey::factory()->create();
        $workers = Worker::query()->limit(2)->get();
        $this->assertCount(2, $workers, 'Seed must contain at least 2 workers.');

        $completedWorker = $workers->first();
        $notCompletedWorker = $workers->last();

        SurveyCompletion::query()->create([
            'worker_id' => $completedWorker->id,
            'survey_id' => $survey->id,
            'completed' => true,
            'completion_date' => now()->toDateString(),
        ]);

        SurveyCompletion::query()->create([
            'worker_id' => $notCompletedWorker->id,
            'survey_id' => $survey->id,
            'completed' => false,
            'completion_date' => null,
        ]);

        $this->getJson(
            route('api.export.surveys.result', ['ids' => [$survey->id]]),
            $this->headers(UserRoleEnum::Admin)
        )->assertOk();

        Excel::assertDownloaded('surveys_result.xlsx', function ($export) use ($completedWorker, $notCompletedWorker) {
            if (!$export instanceof SurveysResultsExport) {
                return false;
            }

            $sheets = $export->sheets();
            $this->assertCount(1, $sheets);

            $rows = $sheets[0]->collection();
            $this->assertCount(1, $rows);
            $this->assertSame($completedWorker->id, $rows->first()->id);
            $this->assertNotEquals($notCompletedWorker->id, $rows->first()->id);

            return true;
        });
    }
}
