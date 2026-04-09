<?php

namespace Database\Seeders;

use App\Enums\SurveyTypeEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyPage;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyQuestionVariant;
use App\Models\Survey\SurveyType;
use App\Services\Survey\ImpulseTemplateService;
use Illuminate\Database\Seeder;

class SurveyQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $typeIds = SurveyType::pluck('id', 'code')->toArray();

        $templatesByType = [
            SurveyTypeEnum::Default->value => [
                [
                    'answer_type_id' => 1,
                    'question'       => 'Text Text text text text text?',
                    'variants'       => ['Windows', 'macOS', 'Linux'],
                ],
                [
                    'answer_type_id' => 2,
                    'question'       => 'Text IDE text text text?',
                    'variants'       => ['PhpStorm', 'VS Code', 'Sublime Text', 'Vim'],
                ],
                [
                    'answer_type_id' => 3,
                    'question'       => 'Text text text text text.',
                ],
                [
                    'answer_type_id' => 4,
                    'question'       => 'Text text text text text text?',
                ],
                [
                    'answer_type_id' => 5,
                    'question'       => 'Text text text text text (1–5).',
                ],
                [
                    'answer_type_id' => 6,
                    'question'       => 'Text text text text text (1–10)?',
                ],
                [
                    'answer_type_id' => 7,
                    'question'       => 'Text text text text-text text text (text text variant)?',
                    'variants'       => ['Jira', 'Trello', 'Asana'],
                ],
                [
                    'answer_type_id' => 8,
                    'question'       => 'Text text text text (text text text):',
                    'variants'       => ['PHP', 'JavaScript', 'SQL', 'HTML/CSS'],
                ],
            ],

            SurveyTypeEnum::Onboarding->value => [
                [
                    'answer_type_id' => 1,
                    'question'       => 'Text text text text text text text?',
                    'variants'       => ['Text', 'Text text', 'Text text-text'],
                ],
                [
                    'answer_type_id' => 2,
                    'question'       => 'Text onboarding-text text text?',
                    'variants'       => ['Text', 'Text', 'Text text text'],
                ],
                [
                    'answer_type_id' => 3,
                    'question'       => 'Text text text text text text.',
                ],
                [
                    'answer_type_id' => 4,
                    'question'       => 'Text text text text text text text?',
                ],
                [
                    'answer_type_id' => 5,
                    'question'       => 'Text text text text text text (1–5).',
                ],
                [
                    'answer_type_id' => 6,
                    'question'       => 'Text text text text text text text text (1–10)?',
                ],
                [
                    'answer_type_id' => 7,
                    'question'       => 'Text text text text text text onboarding (text text variant)?',
                    'variants'       => ['FAQ', 'Text text', 'Text'],
                ],
                [
                    'answer_type_id' => 8,
                    'question'       => 'Text text text text text? (text text text)',
                    'variants'       => ['Text', 'Text', 'Text'],
                ],
            ],

            SurveyTypeEnum::Offboarding->value => [
                [
                    'answer_type_id' => 1,
                    'question'       => 'Text text text text text?',
                    'variants'       => ['Text text', 'Text text', 'Text text'],
                ],
                [
                    'answer_type_id' => 2,
                    'question'       => 'Text text text text text?',
                    'variants'       => ['Text', 'Text', 'Text'],
                ],
                [
                    'answer_type_id' => 3,
                    'question'       => 'Text text text text text text text?',
                ],
                [
                    'answer_type_id' => 4,
                    'question'       => 'Text text text text text text?',
                ],
                [
                    'answer_type_id' => 5,
                    'question'       => 'Text text HR text text text (1–5).',
                ],
                [
                    'answer_type_id' => 6,
                    'question'       => 'Text text, text text text text text (1–10)?',
                ],
                [
                    'answer_type_id' => 7,
                    'question'       => 'Text text text text text text? (text text variant)',
                    'variants'       => ['Text', 'Text', 'Text text'],
                ],
                [
                    'answer_type_id' => 8,
                    'question'       => 'Text text text text text text text? (text text text)',
                    'variants'       => ['Text', 'Text', 'Text text'],
                ],
            ],
            SurveyTypeEnum::Impulse->value => [
                [
                    'answer_type_id' => 9,
                    'question'       => ImpulseTemplateService::FIRST_QUESTION_TEXT,
                ],
                [
                    'answer_type_id' => 8,
                    'question'       => ImpulseTemplateService::SECOND_QUESTION_TEXT,
                    'variants'       => ['Text text text', 'Text', 'Text text', 'Text text', 'Text text', 'Text text', 'Text text'],
                ]
            ]
        ];

        foreach ($templatesByType as $typeCode => $templates) {
            $surveyTypeId = $typeIds[$typeCode] ?? null;
            if (! $surveyTypeId) {
                continue;
            }

            Survey::where('survey_type_id', $surveyTypeId)
                ->get()
                ->each(function (Survey $survey) use ($templates) {

                    if ($survey->questions()->exists()) {
                        return;
                    }

                    $page = $survey->pages()->first();
                    if (!$page) {
                        $page = SurveyPage::create([
                            'survey_id' => $survey->id,
                            'number' => 1,
                        ]);
                    }

                    foreach ($templates as $tpl) {
                        $question = SurveyQuestion::factory()->create([
                            'survey_id'      => $survey->id,
                            'survey_page_id' => $page->id,
                            'answer_type_id' => $tpl['answer_type_id'],
                            'question'       => $tpl['question'],
                        ]);

                        if (! empty($tpl['variants'])) {
                            foreach ($tpl['variants'] as $title) {
                                SurveyQuestionVariant::factory()->create([
                                    'question_id' => $question->id,
                                    'variant'     => $title,
                                ]);
                            }
                        }
                    }
                });
        }
    }
}
