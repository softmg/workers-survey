<?php

namespace Database\Factories\Survey;

use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyQuestionVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Survey\SurveyQuestionVariant>
 */
class SurveyQuestionVariantFactory extends Factory
{
    protected $model = SurveyQuestionVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => SurveyQuestion::inRandomOrder()->first()->id,
            'variant' => join(' ', $this->faker->words),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
