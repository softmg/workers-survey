<?php

namespace Database\Seeders;

use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyType;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        $surveys = [
            [
                'type'     => SurveyTypeEnum::Default,
                'status'   => SurveyStatusEnum::Active,
                'date_end' => now()->addMonths(2),
                'name'     => 'Text survey',
                'description' => 'Text text survey',
            ],
            [
                'type'     => SurveyTypeEnum::Default,
                'status'   => SurveyStatusEnum::Closed,
                'date_end' => now()->subDays(5),
                'name'     => 'Text survey',
                'description' => 'Text text survey',
            ],
            [
                'type'     => SurveyTypeEnum::Onboarding,
                'status'   => SurveyStatusEnum::Active,
                'date_end' => now()->addMonths(2),
                'name'     => 'Text',
                'description' => 'Text survey text text',
            ],
            [
                'type'     => SurveyTypeEnum::Onboarding,
                'status'   => SurveyStatusEnum::Created,
                'is_template' => true,
                'date_end' => null,
                'name'     => 'Text template',
                'description' => 'Template text survey',
            ],
            [
                'type'     => SurveyTypeEnum::Offboarding,
                'status'   => SurveyStatusEnum::Active,
                'date_end' => now()->addMonths(2),
                'name'     => 'Text',
                'description' => 'Text survey text text',
            ],
            [
                'type'     => SurveyTypeEnum::Offboarding,
                'status'   => SurveyStatusEnum::Created,
                'is_template' => true,
                'date_end' => null,
                'name'     => 'Text template',
                'description' => 'Template text survey',
            ],
            [
                'type'     => SurveyTypeEnum::Impulse,
                'status'   => SurveyStatusEnum::Created,
                'is_template' => true,
                'date_end' => null,
                'name'     => 'Impulse (Template)',
                'description' => 'Template Impulse',
            ],
            [
                'type'     => SurveyTypeEnum::Impulse,
                'status'   => SurveyStatusEnum::Active,
                'date_end' => now()->addDays(2),
                'name'     => 'Impulse survey',
                'description' => 'Text survey text text',
            ],
        ];

        collect($surveys)->each(function ($survey) {
            if (Survey::where('name', $survey['name'])->exists()) {
                return;
            }

            Survey::factory()->create([
                'survey_type_id' => SurveyType::where('code', $survey['type']->value)->value('id'),
                'status'         => $survey['status'],
                'is_template'    => $survey['is_template'] ?? false,
                'date_end'       => $survey['date_end'],
                'name'           => $survey['name'],
                'description'    => $survey['description'],
            ]);
        });
    }
}
