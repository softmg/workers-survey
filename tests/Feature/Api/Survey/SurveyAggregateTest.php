<?php

namespace Feature\Api\Survey;

use App\Enums\SurveyAnswerTypeEnum;
use App\Enums\SurveyAnonymityEnum;
use App\Enums\SurveyStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\WorkerStatusEnum;
use App\Models\Department;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyPage;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyQuestionVariant;
use App\Models\Survey\SurveyType;
use App\Models\User\User;
use App\Models\Worker\Worker;
use App\Models\Worker\WorkerStatus;
use Carbon\Carbon;
use Tests\Feature\FeatureTestCase;

class SurveyAggregateTest extends FeatureTestCase
{
    public const APPROXIMATE_TIME_DEFAULT = 10;

    private function getAnswerTypeByBaseType(SurveyAnswerTypeEnum $baseType, bool $custom = false, bool $limited = false): SurveyAnswerType
    {
        return SurveyAnswerType::query()
            ->where('base_type', $baseType)
            ->where('custom', $custom)
            ->where('limited', $limited)
            ->firstOrFail();
    }

    private function createBaseData(): array
    {
        $surveyType = SurveyType::firstOrFail();
        $workingStatus = WorkerStatus::where('code', WorkerStatusEnum::Working->value)->firstOrFail();

        $user = User::factory()->create();
        $worker = Worker::where('user_id', $user->id)->first();
        $worker->statusId = $workingStatus->id;
        $worker->save();

        $department = Department::firstOrFail();

        return [
            'survey_type_id' => $surveyType->id,
            'workers_id' => [$worker->id],
            'departments_id' => [$department->id],
        ];
    }

    private function createSurveyBody(array $baseData, array $overrides = []): array
    {
        $default = [
            'survey' => [
                'name' => 'Test Survey',
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'status' => SurveyStatusEnum::Created->value,
                'approximate_time' => $baseData['approximate_time'] ?? self::APPROXIMATE_TIME_DEFAULT,
                'survey_type_id' => $baseData['survey_type_id'],
                'date_end' => Carbon::tomorrow()->format('Y-m-d'),
            ],
            'workers_id' => $baseData['workers_id'],
            'departments_id' => $baseData['departments_id'],
        ];

        if (isset($overrides['survey']) && is_array($overrides['survey'])) {
            $default['survey'] = array_merge($default['survey'], $overrides['survey']);
            unset($overrides['survey']);
        }

        return array_merge($default, $overrides);
    }

    public function test_create_survey_successfully(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();
        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Radio);

        $body = $this->createSurveyBody($baseData, [
            'survey' => [
                'description' => 'Test Description',
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                'survey_pages' => [[
                    'survey_questions' => [[
                        'question' => 'Test Question 1',
                        'answer_type_id' => $answerType->id,
                        'is_required' => true,
                        'survey_answer_variants' => ['Variant 1', 'Variant 2'],
                    ]],
                ]],
            ],
        ]);

        $response = $this->postJson(route('api.surveys.survey.saveAggregate'), $body, $headers);

        $response->assertStatus(201);

        $this->assertDatabaseHas('surveys', [
            'name' => 'Test Survey',
            'status' => SurveyStatusEnum::Created->value,
        ]);

        $survey = Survey::where('name', 'Test Survey')->first();
        $this->assertNotNull($survey);
        $this->assertGreaterThan(0, $survey->pages()->count());
        $this->assertGreaterThan(0, $survey->questions()->count());
        $this->assertGreaterThan(0, $survey->completions()->count());
    }

    public function test_create_survey_rejects_invalid_date(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();

        foreach ([Carbon::yesterday(), Carbon::today()] as $date) {
            $body = $this->createSurveyBody($baseData, [
                'survey' => ['date_end' => $date->format('Y-m-d')],
            ]);

            $this->postJson(route('api.surveys.survey.saveAggregate'), $body, $headers)
                ->assertStatus(422)
                ->assertJsonFragment(['message' => __('exception.invalid_survey_date')]);
        }
    }

    public function test_create_survey_rejects_variants_for_text_type(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();
        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Text, custom: true);

        $body = $this->createSurveyBody($baseData, [
            'survey' => [
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                'survey_pages' => [
                    [
                        'survey_questions' => [
                            [
                                'question' => 'Test Question',
                                'answer_type_id' => $answerType->id,
                                'is_required' => true,
                                'survey_answer_variants' => ['Variant 1'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->postJson(route('api.surveys.survey.saveAggregate'), $body, $headers);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => __('exception.survey.variants_not_allowed_for_answer_type', [
                    'number' => 1,
                    'answer_type' => $answerType->title,
                ]),
            ]);
    }

    public function test_create_survey_rejects_variants_for_integer_type(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();
        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Integer);

        $body = $this->createSurveyBody($baseData, [
            'survey' => [
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                'survey_pages' => [
                    [
                        'survey_questions' => [
                            [
                                'question' => 'Test Question',
                                'answer_type_id' => $answerType->id,
                                'is_required' => true,
                                'survey_answer_variants' => ['1'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->postJson(route('api.surveys.survey.saveAggregate'), $body, $headers);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => __('exception.survey.variants_not_allowed_for_answer_type', [
                    'number' => 1,
                    'answer_type' => $answerType->title,
                ]),
            ]);
    }

    public function test_create_survey_requires_workers_or_departments(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();

        $body = [
            'survey' => [
                'name' => 'Test Survey',
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                'status' => SurveyStatusEnum::Created->value,
                'survey_type_id' => $baseData['survey_type_id'],
            ],
            'workers_id' => [],
            'departments_id' => [],
        ];

        $response = $this->postJson(route('api.surveys.survey.saveAggregate'), $body, $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workers_id']);
    }

    public function test_create_template_rejects_non_created_status(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();

        $body = $this->createSurveyBody($baseData, [
            'survey' => [
                'is_template' => true,
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                'status' => SurveyStatusEnum::Active->value,
            ],
        ]);

        $response = $this->postJson(route('api.surveys.survey.saveAggregate'), $body, $headers);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => __('exception.survey.cannot_set_template'),
            ]);
    }

    public function test_update_survey_successfully(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();
        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Radio);

        $survey = Survey::factory()->create([
            'status' => SurveyStatusEnum::Created,
            'survey_type_id' => $baseData['survey_type_id'],
            'anonymity' => SurveyAnonymityEnum::Public->value,
            'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
        ]);

        $page = SurveyPage::create([
            'survey_id' => $survey->id,
            'number' => 1,
        ]);

        $question1 = SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'survey_page_id' => $page->id,
            'answer_type_id' => $answerType->id,
        ]);

        $question2 = SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'survey_page_id' => $page->id,
            'answer_type_id' => $answerType->id,
        ]);

        $body = [
            'survey' => [
                'name' => 'Updated Survey Name',
                'description' => 'Updated Description',
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                'status' => SurveyStatusEnum::Created->value,
                'survey_type_id' => $baseData['survey_type_id'],
                'date_end' => Carbon::tomorrow()->format('Y-m-d'),
                'survey_pages' => [
                    [
                        'id' => $page->id,
                        'survey_questions' => [
                            [
                                'id' => $question1->id,
                                'question' => 'Updated Question',
                                'answer_type_id' => $answerType->id,
                                'is_required' => false,
                                'survey_answer_variants' => ['Text variant'],
                            ],
                            [
                                'question' => 'New Question',
                                'answer_type_id' => $answerType->id,
                                'is_required' => true,
                                'survey_answer_variants' => ['Variant 1'],
                            ],
                        ],
                    ],
                ],
            ],
            'workers_id' => $baseData['workers_id'],
            'departments_id' => $baseData['departments_id'],
        ];

        $response = $this->putJson(
            route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
            $body,
            $headers
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                ],
            ]);

        $this->assertDatabaseHas('surveys', [
            'id' => $survey->id,
            'name' => 'Updated Survey Name',
        ]);

        $survey->refresh();
        $this->assertEquals(2, $survey->questions()->count());
        $this->assertDatabaseHas('survey_questions', [
            'id' => $question1->id,
            'question' => 'Updated Question',
        ]);
        $this->assertDatabaseMissing('survey_questions', [
            'id' => $question2->id,
        ]);
        $this->assertDatabaseHas('survey_questions', [
            'question' => 'New Question',
        ]);
    }

    public function test_update_survey_rejects_invalid_status(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();

        foreach ([SurveyStatusEnum::Active, SurveyStatusEnum::Closed] as $status) {
            $survey = Survey::factory()->create([
                'status' => $status,
                'survey_type_id' => $baseData['survey_type_id'],
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
            ]);

            $body = [
                'survey' => [
                    'name' => 'Updated Survey Name',
                    'anonymity' => SurveyAnonymityEnum::Public->value,
                    'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                    'status' => $status->value,
                    'survey_type_id' => $baseData['survey_type_id'],
                ],
                'workers_id' => $baseData['workers_id'],
                'departments_id' => $baseData['departments_id'],
            ];

            $this->putJson(
                route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
                $body,
                $headers
            )->assertStatus(422)
                ->assertJsonFragment(['message' => __('exception.survey.cannot_update_survey')]);
        }
    }

    public function test_update_survey_rejects_past_date(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();

        $survey = Survey::factory()->create([
            'status' => SurveyStatusEnum::Created,
            'survey_type_id' => $baseData['survey_type_id'],
            'anonymity' => SurveyAnonymityEnum::Public->value,
            'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
        ]);

        $body = [
            'survey' => [
                'name' => 'Updated Survey Name',
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                'status' => SurveyStatusEnum::Created->value,
                'survey_type_id' => $baseData['survey_type_id'],
                'date_end' => Carbon::yesterday()->format('Y-m-d'),
            ],
            'workers_id' => $baseData['workers_id'],
            'departments_id' => $baseData['departments_id'],
        ];

        $response = $this->putJson(
            route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
            $body,
            $headers
        );

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => __('exception.invalid_survey_date'),
            ]);
    }

    public function test_update_survey_rejects_structure_change_with_answers(): void
    {
        $headers = $this->headers(UserRoleEnum::Admin);
        $baseData = $this->createBaseData();
        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Radio);

        $survey = Survey::factory()->create([
            'status' => SurveyStatusEnum::Created,
            'survey_type_id' => $baseData['survey_type_id'],
            'anonymity' => SurveyAnonymityEnum::Public->value,
            'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
        ]);

        $page = SurveyPage::create([
            'survey_id' => $survey->id,
            'number' => 1,
        ]);

        $question = SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'survey_page_id' => $page->id,
            'answer_type_id' => $answerType->id,
        ]);

        $user = $this->getUserFromRole(UserRoleEnum::Staff);
        if (!$user) {
            $user = User::factory()->create(['email' => UserRoleEnum::Staff->value . '@example.com']);
            $role = \App\Models\User\Role::where('name', UserRoleEnum::Staff->value)->first();
            if ($role) {
                $user->roles()->attach($role);
            }
        }

        $variant = $question->variants->first();
        if (!$variant) {
            $variant = SurveyQuestionVariant::factory()->create(['question_id' => $question->id]);
        }

        $answer = \App\Models\Survey\SurveyAnswer::create([
            'question_id' => $question->id,
            'worker_id' => $user->worker->id,
            'answer_text' => null,
            'answer_int' => null,
        ]);

        \App\Models\Survey\SurveyAnswerVariants::create([
            'survey_answer_id' => $answer->id,
            'variant_id' => $variant->id,
        ]);

        $body = [
            'survey' => [
                'name' => 'Updated Survey Name',
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'status' => SurveyStatusEnum::Created->value,
                'approximate_time' => self::APPROXIMATE_TIME_DEFAULT,
                'survey_type_id' => $baseData['survey_type_id'],
                'survey_pages' => [
                    [
                        'id' => $page->id,
                        'survey_questions' => [
                            [
                                'id' => $question->id,
                                'question' => 'Updated Question',
                                'answer_type_id' => $answerType->id,
                                'is_required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'workers_id' => $baseData['workers_id'],
            'departments_id' => $baseData['departments_id'],
        ];

        $response = $this->putJson(
            route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
            $body,
            $headers
        );

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => __('exception.survey.cannot_update_survey_structure_with_answers'),
            ]);
    }
}
