<?php

namespace Database\Factories\Survey;

use App\Enums\SurveyAnswerTypeEnum;
use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyQuestion;
use App\Models\Worker\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Survey\SurveyAnswer>
 */
class SurveyAnswerFactory extends Factory
{
    protected $model = SurveyAnswer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $surveyQuestion = SurveyQuestion::inRandomOrder()->whereHas('answerType', function ($query) {
            return $query->withType([SurveyAnswerTypeEnum::Radio]);
        })->first();
        $surveyQuestionVariant = $surveyQuestion->variants()->inRandomOrder()->first();
        return [
            'worker_id' => Worker::inRandomOrder()->first()->id,
            'question_id' => $surveyQuestion->id,
            'variant_id' => $surveyQuestionVariant->id,
            'answer' => null,
        ];
    }
}
