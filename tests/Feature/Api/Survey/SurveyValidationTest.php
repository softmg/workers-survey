<?php

namespace Feature\Api\Survey;

use App\Enums\SurveyAnswerTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyCompletion;
use App\Models\Survey\SurveyPage;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyQuestionVariant;
use App\Models\User\Role;
use App\Models\User\User;
use Tests\Feature\FeatureTestCase;

class SurveyValidationTest extends FeatureTestCase
{
    private function makeSurveyWithQuestion(SurveyAnswerTypeEnum $type, bool $custom = false, bool $limit = false, bool $required = false): Survey
    {
        $survey = Survey::factory()->create();
        $page = SurveyPage::firstOrCreate(
            ['survey_id' => $survey->id, 'number' => 1],
            ['name' => null, 'description' => null]
        );

        $answerType = SurveyAnswerType::query()
            ->where('base_type', $type)
            ->where('custom', $custom)
            ->where('limited', $limit)
            ->firstOrFail();

        $question = SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'survey_page_id' => $page->id,
            'answer_type_id' => $answerType->id,
            'is_required' => $required,
        ]);

        if (in_array($type, [SurveyAnswerTypeEnum::Radio, SurveyAnswerTypeEnum::Checkbox], true)) {
            SurveyQuestionVariant::factory()
                ->count(3)
                ->create(['question_id' => $question->id]);
        }

        return $survey->load('questions.answerType', 'questions.variants', 'questions.page');
    }

    private function markIncomplete(Survey $survey): void
    {
        $user = $this->getUserFromRole(UserRoleEnum::Staff);

        if (!$user) {
            $user = User::factory()->create([
                'email' => UserRoleEnum::Staff->value . '@example.com',
            ]);
            $role = Role::where('name', UserRoleEnum::Staff->value)->first();
            if ($role) {
                $user->roles()->attach($role);
            }
        }

        SurveyCompletion::factory()->create([
            'worker_id' => $user->worker->id,
            'survey_id' => $survey->id,
            'completed' => false,
        ]);
    }

    public function test_question_must_belong_to_page(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Integer);
        $this->markIncomplete($survey);

        $question = $survey->questions->first();
        $otherPage = SurveyPage::create(['survey_id' => $survey->id, 'number' => 2]);

        $body = ['pages' => [[
            'page_id' => $otherPage->id,
            'answers' => [[
                'question_id' => $question->id,
                'integer' => 5,
            ]]
        ]]];

        $this->postJson(route('api.surveys.saveAnswers', $survey), $body, $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages.0.answers.0.question_id' => __('exception.survey.question_not_belong_to_page', [
                    'question_id' => $question->id,
                    'page_id' => $otherPage->id,
                ]),
            ]);
    }

    public function test_question_must_belong_to_survey(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $targetSurvey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Integer);
        $this->markIncomplete($targetSurvey);

        $externalSurvey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Integer);
        $externalQuestion = $externalSurvey->questions->first();

        $body = ['pages' => [[
            'page_id' => $externalQuestion->survey_page_id,
            'answers' => [[
                'question_id' => $externalQuestion->id,
                'integer' => 5,
            ]]
        ]]];

        $this->postJson(route('api.surveys.saveAnswers', $targetSurvey), $body, $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages.0.answers.0' => __('exception.survey.not_belong', ['number' => $externalQuestion->id]),
            ]);
    }

    public function test_required_questions_must_be_answered(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = Survey::factory()->create();
        $page = SurveyPage::firstOrCreate(
            ['survey_id' => $survey->id, 'number' => 1],
            ['name' => null, 'description' => null]
        );

        $requiredQuestion = SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'survey_page_id' => $page->id,
            'answer_type_id' => SurveyAnswerType::where('base_type', SurveyAnswerTypeEnum::Text)->where('custom', true)->first()->id,
            'is_required' => true,
        ]);

        $optionalQuestion = SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'survey_page_id' => $page->id,
            'answer_type_id' => SurveyAnswerType::where('base_type', SurveyAnswerTypeEnum::Text)->where('custom', true)->first()->id,
            'is_required' => false,
        ]);

        $this->markIncomplete($survey);

        $body = ['pages' => [[
            'page_id' => $page->id,
            'answers' => [[
                'question_id' => $optionalQuestion->id,
                'answer' => 'answer',
            ]]
        ]]];

        $this->postJson(route('api.surveys.saveAnswers', $survey), $body, $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages' => __('exception.survey.required_questions_missing', [
                    'ids' => $requiredQuestion->id,
                ]),
            ]);
    }

    public function test_integer_question_requires_integer_field(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Integer);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'answer' => 'text',
                ]]
            ]]],
            $headers
        )->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages.0.answers.0' => __('exception.survey.integer_required', [
                    'number' => $question->question_number,
                ]),
            ]);
    }

    public function test_integer_question_validates_limits(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Integer, limit: true);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();
        $answerType = $question->answerType;

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'integer' => $answerType->max + 1,
                ]]
            ]]],
            $headers
        )->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages.0.answers.0' => __('exception.survey.integer_limits', [
                    'number' => $question->question_number,
                    'min' => $answerType->min,
                    'max' => $answerType->max,
                ]),
            ]);
    }

    public function test_text_question_rejects_invalid_fields(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Text, custom: true);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'integer' => 5,
                ]]
            ]]],
            $headers
        )->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages.0.answers.0' => __('exception.survey.text_required', [
                    'number' => $question->question_number,
                ]),
            ]);
    }

    public function test_radio_question_requires_exactly_one_variant(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Radio);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();
        $variants = $question->variants->pluck('id')->toArray();

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'variants' => $variants,
                ]]
            ]]],
            $headers
        )->assertStatus(422)
            ->assertJsonPath('message', __('exception.survey.radio_required', [
                'number' => $question->question_number,
            ]));
    }

    public function test_radio_question_rejects_variant_and_text_together(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Radio, custom: true);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();
        $variant = $question->variants->first()->id;

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'variants' => [['id' => $variant]],
                    'answer' => 'custom',
                ]]
            ]]],
            $headers
        )->assertStatus(422)
            ->assertJsonFragment([
                'message' => __('exception.survey.radio_conflict', [
                    'number' => $question->question_number,
                ]),
            ]);
    }

    public function test_checkbox_question_requires_at_least_one_option(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Checkbox, required: true);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'variants' => [],
                ]]
            ]]],
            $headers
        )->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages.0.answers.0' => __('exception.survey.checkbox_required', [
                    'number' => $question->question_number,
                ]),
            ]);
    }

    public function test_checkbox_question_rejects_invalid_variant_ids(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Checkbox);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'variants' => [['id' => 999]],
                ]]
            ]]],
            $headers
        )->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages.0.answers.0' => __('exception.survey.checkbox_invalid_variant', [
                    'number' => $question->question_number,
                ]),
                'pages.0.answers.0.variants.0.id' => __('exception.survey.variant_not_found'),
            ]);
    }

    public function test_checkbox_question_rejects_text_if_not_allowed(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Checkbox);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'answer' => 'text',
                ]]
            ]]],
            $headers
        )->assertStatus(422)
            ->assertJsonValidationErrors([
                'pages.0.answers.0' => __('exception.survey.checkbox_no_text_allowed', [
                    'number' => $question->question_number,
                ]),
            ]);
    }

    public function test_checkbox_question_allows_custom_text_if_custom_allowed(): void
    {
        $headers = $this->headers(UserRoleEnum::Staff);
        $survey = $this->makeSurveyWithQuestion(SurveyAnswerTypeEnum::Checkbox, custom: true);
        $this->markIncomplete($survey);
        $question = $survey->questions->first();

        $this->postJson(
            route('api.surveys.saveAnswers', $survey),
            ['pages' => [[
                'page_id' => $question->survey_page_id,
                'answers' => [[
                    'question_id' => $question->id,
                    'answer' => 'custom text',
                ]]
            ]]],
            $headers
        )->assertStatus(200);
    }
}
