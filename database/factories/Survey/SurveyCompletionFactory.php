<?php

namespace Database\Factories\Survey;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use App\Models\Worker\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Survey\SurveyCompletion>
 */
class SurveyCompletionFactory extends Factory
{
    protected $model = SurveyCompletion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $completed = $this->faker->boolean();

        return [
            'worker_id' => Worker::inRandomOrder()->first()->id,
            'survey_id' => Survey::inRandomOrder()->first()->id,
            'completed' => $completed,
            'completion_date' => $completed ? now() : null,
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-3 months', 'now'),

        ];
    }
}
