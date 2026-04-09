<?php

namespace Feature\Api\Survey;

use App\Enums\UserRoleEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use App\Models\Survey\SurveyPage;
use App\Models\Survey\SurveyQuestion;
use Tests\Feature\FeatureTestCase;

class SurveyTest extends FeatureTestCase
{
    private array $allowedRoles = [
        UserRoleEnum::Admin,
        UserRoleEnum::Hr,
        UserRoleEnum::Ceo,
    ];

    private array $deniedRoles = [
        UserRoleEnum::Worker,
        UserRoleEnum::Manager,
        UserRoleEnum::Staff,
        UserRoleEnum::Chief,
        UserRoleEnum::Accountant,
    ];

    private array $protectedRoutes = [
        ['api.surveys.all',           []],
        ['api.surveys.information',   ['survey' => 1]],
        ['api.surveys.workers',       ['survey' => 1]],
        ['api.surveys.answers',       ['survey' => 1]],
        ['api.surveys.questionAnswers', [
            'survey'   => 1,
            'question' => 1,
        ]],
        ['api.surveys.filters',       []],
    ];

    public function test_unauthenticated_cannot_load_index(): void
    {
        $this->getJson(route('api.surveys.index'))
            ->assertStatus(401);
    }

    public function test_all_authenticated_roles_can_load_index(): void
    {
        foreach (UserRoleEnum::except([UserRoleEnum::ApiClientOldCRM]) as $role) {
            $headers = $this->headers($role);
            $this->getJson(route('api.surveys.index'), $headers)->assertStatus(200);
        }
    }

    public function test_all_authenticated_roles_can_load_questions(): void
    {
        foreach (UserRoleEnum::except([UserRoleEnum::ApiClientOldCRM]) as $role) {
            $headers = $this->headers($role);
            $this->getJson(route('api.surveys.questions', ['survey' => 1]), $headers)->assertStatus(200);
        }
    }

    public function test_denied_roles_cannot_access_protected_routes(): void
    {
        foreach ($this->deniedRoles as $role) {
            $headers = $this->headers($role);

            foreach ($this->protectedRoutes as [$routeName, $params]) {
                $url = route($routeName, $params);
                $this->getJson($url, $headers)->assertStatus(403);
            }
        }
    }

    public function test_allowed_roles_can_access_protected_routes(): void
    {
        foreach ($this->allowedRoles as $role) {
            $headers = $this->headers($role);

            foreach ($this->protectedRoutes as [$routeName, $params]) {
                $url = route($routeName, $params);
                $this->getJson($url, $headers)->assertStatus(200);
            }
        }
    }

    public function test_survey_answers_can_be_saved_successfully(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $user   = $this->getUserFromRole(UserRoleEnum::Staff);
        $survey = Survey::factory()->create();
        $page = SurveyPage::firstOrCreate(
            ['survey_id' => $survey->id, 'number' => 1],
            ['name' => null, 'description' => null]
        );
        //Question text text text
        $question = SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'survey_page_id' => $page->id,
            'answer_type_id' => 3
        ]);
        SurveyCompletion::factory()->create([
            'worker_id' => $user->worker->id,
            'survey_id' => $survey->id,
            'completed' => false,
        ]);

        $body = [
            'pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'answer' => 'test',
                ]]
            ]]
        ];

        $response = $this->postJson(route('api.surveys.saveAnswers', $survey), $body, $headers);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'isCompleted',
            ]
        ]);

        $response->assertJsonFragment(['isCompleted' => true]);

        $this->assertDatabaseHas('survey_completions', [
            'worker_id' => $user->worker->id,
            'survey_id' => $survey->id,
            'completed' => true,
        ]);

    }

    public function test_completed_survey_cannot_be_resubmitted(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $user = $this->getUserFromRole(UserRoleEnum::Staff);

        $survey = Survey::factory()->create();
        $page = SurveyPage::firstOrCreate(
            ['survey_id' => $survey->id, 'number' => 1],
            ['name' => null, 'description' => null]
        );
        //Question text text text
        $question = SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'survey_page_id' => $page->id,
            'answer_type_id' => 3
        ]);
        SurveyCompletion::factory()->create([
            'worker_id' => $user->worker->id,
            'survey_id' => $survey->id,
            'completed' => true,
        ]);

        $body = [
            'pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'answer'      => 'Already done',
                ]]
            ]]
        ];

        $response = $this->postJson(
            route('api.surveys.saveAnswers', ['survey' => $survey->id]),
            $body,
            $headers
        );

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => __('exception.current_survey_is_completed')]);
    }

    public function test_unauthenticated_cannot_load_offboarding_workers(): void
    {
        $this->getJson(route('api.surveys.offboarding.workers'))
            ->assertStatus(401);
    }

    public function test_hr_authenticated_role_can_load_offboarding_workers(): void
    {
        $this->getJson(route('api.surveys.offboarding.workers'), $this->headers(UserRoleEnum::Hr))
            ->assertOk();
    }

    public function test_unauthenticated_cannot_load_offboarding_answers(): void
    {
        $this->getJson(route('api.surveys.offboarding.answers'))
            ->assertStatus(401);
    }

    public function test_hr_authenticated_role_can_load_offboarding_answers(): void
    {
        $this->getJson(route('api.surveys.offboarding.answers'), $this->headers(UserRoleEnum::Hr))
            ->assertOk();
    }
}
