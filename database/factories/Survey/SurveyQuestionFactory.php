<?php

namespace Database\Factories\Survey;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Survey\SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    protected $model = SurveyQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::inRandomOrder()->first()->id,
            'answer_type_id' => SurveyAnswerType::inRandomOrder()->first()->id,
            'question' => implode(' ', $this->faker->words).'?',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
