<?php

use App\Enums\SurveyAnswerTypeEnum;
use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyType;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        //Text survey
        $typeId = SurveyType::query()->where('code', SurveyTypeEnum::Offboarding->value)->first()?->id;

        if (!$typeId) {
            throw new \RuntimeException('Text text type survey');
        }

        $offboardingSurvey = Survey::query()->updateOrCreate(
            ['name' => 'Text text'],
            [
                'description' => 'Text text text text text text text. Text, text text text text text text. Text, text, text questions Text text text text text text, text text text',
                'survey_type_id' => $typeId,
                'status' => SurveyStatusEnum::Active->value,
            ]
        );

        //Text text text survey
        $radioBaseType = SurveyAnswerTypeEnum::Radio->value;
        $checkboxBaseType = SurveyAnswerTypeEnum::Checkbox->value;
        $integerBaseType = SurveyAnswerTypeEnum::Integer->value;
        $radioAnswerTypeId = SurveyAnswerType::query()
            ->where('base_type', $radioBaseType)
            ->where('custom', 0)
            ->where('multiple', 0)
            ->where('limited', 0)
            ->first()
            ?->id;
        $radioTextAnswerTypeId = SurveyAnswerType::query()
            ->where('base_type', $radioBaseType)
            ->where('custom', 1)
            ->where('multiple', 0)
            ->where('limited', 0)
            ->first()
            ?->id;
        $checkboxTextAnswerTypeId = SurveyAnswerType::query()
            ->where('base_type', $checkboxBaseType)
            ->where('custom', 1)
            ->where('multiple', 1)
            ->where('limited', 0)
            ->first()
            ?->id;
        $rangeToTenAnswerTypeId = SurveyAnswerType::query()
            ->where('base_type', $integerBaseType)
            ->where('custom', 0)
            ->where('multiple', 0)
            ->where('limited', 1)
            ->where('max', 10)
            ->first()
            ?->id;

        if (!$radioAnswerTypeId || !$checkboxTextAnswerTypeId || !$radioTextAnswerTypeId || !$rangeToTenAnswerTypeId) {
            throw new \RuntimeException('Text text type text text question');
        }

        $questions = [
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioAnswerTypeId,
                'question' => 'Text text text text (text text text)?',
                'question_number' => 1,
                'variants' => [
                    'Text',
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioTextAnswerTypeId,
                'question' => 'Text text text text, text text text text text text text?',
                'question_number' => 2,
                'variants' => [
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioTextAnswerTypeId,
                'question' => 'Text text text text text text text text, text text text, text text workers, text text?',
                'question_number' => 3,
                'variants' => [
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioAnswerTypeId,
                'question' => 'Text department text text text text text, text text Text text text text text  text?',
                'question_number' => 4,
                'variants' => [
                    'Text',
                    'Text',
                    'Text',
                    'Text',
                    'Text text text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioAnswerTypeId,
                'question' => 'Text text text/text text?',
                'question_number' => 5,
                'variants' => [
                    'Text',
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioAnswerTypeId,
                'question' => 'Text text text?',
                'question_number' => 6,
                'variants' => [
                    'Text',
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioAnswerTypeId,
                'question' => 'Text text text?',
                'question_number' => 7,
                'variants' => [
                    'Text',
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $rangeToTenAnswerTypeId,
                'question' => 'Text text text text text text text text?',
                'question_number' => 8,
                'variants' => []
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioTextAnswerTypeId,
                'question' => 'Text text text text?',
                'question_number' => 9,
                'variants' => [
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioAnswerTypeId,
                'question' => 'Text text text, text text?',
                'question_number' => 10,
                'variants' => [
                    'Text',
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $radioTextAnswerTypeId,
                'question' => 'Text text text text text text text text?',
                'question_number' => 11,
                'variants' => [
                    'Text',
                    'Text',
                ]
            ],
            [
                'survey_id' => $offboardingSurvey->id,
                'answer_type_id' => $checkboxTextAnswerTypeId,
                'question' => 'Text text text text, text text text text text',
                'question_number' => 12,
                'variants' => [
                    'Text text text',
                    'Text text text text',
                    'Text text text',
                    'Text text text text text text',
                    'Text text text text',
                    'Text text text',
                    'Text text text text',
                ]
            ],
        ];

        foreach ($questions as $question) {
            $variants = $question['variants'];
            unset($question['variants']);

            $surveyQuestion = SurveyQuestion::query()->create($question);

            foreach ($variants as $variant) {
                $surveyQuestion->variants()->create(['variant' => $variant]);
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Survey::query()->where('name', 'Text text')->delete();
    }
};
