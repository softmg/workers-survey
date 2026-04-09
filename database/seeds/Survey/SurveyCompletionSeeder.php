<?php

namespace Database\Seeders;

use App\Enums\SurveyStatusEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use App\Models\Worker\Worker;
use Illuminate\Database\Seeder;

class SurveyCompletionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Survey::all() as $survey) {
            if ($survey->workers()->count() > 0 || $survey->is_template) {
                continue;
            }
            foreach (Worker::all() as $worker) {
                $date = $survey->status === SurveyStatusEnum::Closed ? now() : null;

                if ($survey->status === SurveyStatusEnum::Active) {
                    SurveyCompletion::factory()
                        ->create([
                            'worker_id' => $worker->id,
                            'survey_id' => $survey->id,
                        ]);
                } else {
                    SurveyCompletion::factory()
                        ->create([
                            'worker_id' => $worker->id,
                            'survey_id' => $survey->id,
                            'completed' => (bool)$date,
                            'completion_date' => $date
                        ]);
                }
            }
        }
    }
}
