<?php

namespace Database\Seeders;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyPage;
use Illuminate\Database\Seeder;

class SurveyPageSeeder extends Seeder
{
    public function run(): void
    {
        Survey::all()->each(function (Survey $survey) {
            if ($survey->pages()->exists()) {
                return;
            }

            SurveyPage::create([
                'survey_id' => $survey->id,
                'number' => 1,
                'name' => null,
                'description' => null,
            ]);
        });
    }
}
