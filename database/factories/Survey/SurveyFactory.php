<?php

namespace Database\Factories\Survey;

use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Survey\Survey>
 */
class SurveyFactory extends Factory
{
    public const APPROXIMATE_TIME_DEFAULT = 5;
    protected $model = Survey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $surveyTypes = [
            SurveyTypeEnum::Default->value,
            SurveyTypeEnum::Onboarding->value,
            SurveyTypeEnum::Offboarding->value,
        ];

        $surveyStatuses = [
            SurveyStatusEnum::Created->value,
            SurveyStatusEnum::Active->value,
        ];

        $dateEnd = $this->faker->dateTimeBetween('-3 months', '+3 months');

        return [
            'name' => $this->faker->word,
            'description' => join(' ', $this->faker->words),
            'survey_type_id' => SurveyType::where('code', array_random($surveyTypes))->first()->id,
            'status' => now() < $dateEnd ? array_random($surveyStatuses) : SurveyStatusEnum::Closed->value,
            'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
            'created_at' => $this->faker->dateTimeBetween('-1 years', '-3 months'),
            'is_template' => false,
            'date_end' => $dateEnd,
        ];
    }
}
