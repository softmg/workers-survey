<?php

namespace App\Http\Requests\Survey;

use App\Rules\AnswerMatchesQuestionTypeRule;
use App\Rules\RequiredSurveyQuestionsPresentRule;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyAnswersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pages' => ['present', 'array'],
            'pages.*' => ['array'],

            'pages.*.page_id' => 'required|int|exists:survey_pages,id',
            'pages.*.answers' => 'required|array|min:1',
            'pages.*.answers.*' => ['array'],

            'pages.*.answers.*.question_id' => 'required|int|exists:survey_questions,id',
            'pages.*.answers.*.variants' => 'nullable|array',
            'pages.*.answers.*.variants.*.id' => 'nullable|int|exists:survey_question_variants,id',
            'pages.*.answers.*.answer' => 'nullable|string|max:500',
            'pages.*.answers.*.integer' => 'nullable|int',
        ];
    }

    public function withValidator($validator)
    {
        /** @var Survey $survey */
        $survey = $this->route('survey');
        $survey->load('questions:id,survey_id,survey_page_id,question_number,is_required,answer_type_id', 'questions.answerType', 'questions.variants');

        $validator->after(function ($validator) use ($survey) {
            $pages = $this->input('pages', []);

            foreach ($pages as $pageIndex => $pageData) {
                $pageId = $pageData['page_id'] ?? null;
                $answers = $pageData['answers'] ?? [];

                foreach ($answers as $answerIndex => $answer) {
                    $questionId = $answer['question_id'] ?? null;

                    if ($questionId && $pageId) {
                        $questionBelongsToPage = SurveyQuestion::where('id', $questionId)
                            ->where('survey_page_id', $pageId)
                            ->exists();

                        if (!$questionBelongsToPage) {
                            $validator->errors()->add(
                                "pages.{$pageIndex}.answers.{$answerIndex}.question_id",
                                __('exception.survey.question_not_belong_to_page', [
                                    'question_id' => $questionId,
                                    'page_id' => $pageId,
                                ])
                            );
                        }
                    }
                }
            }
        });

        $validator->sometimes(
            'pages',
            new RequiredSurveyQuestionsPresentRule($survey),
            fn () => true
        );

        $validator->sometimes(
            'pages.*.answers.*',
            new AnswerMatchesQuestionTypeRule($survey),
            fn () => true
        );
    }

    public function attributes(): array
    {
        return [
            'pages.*.page_id' => 'text text',
            'pages.*.answers.*.answer' => 'text answer',
            'pages.*.answers.*.variants.*.id' => 'variant answer',
            'pages.*.answers.*.question_id' => 'text question',
            'pages.*.answers.*.integer' => 'text answer',
        ];
    }

    public function messages(): array
    {
        return [
            'pages.array' => 'Text text text text text.',

            'pages.*.page_id.required' => 'Text text text.',
            'pages.*.page_id.integer' => 'Text text text text text text.',
            'pages.*.page_id.exists' => 'Text text text text text text.',

            'pages.*.answers.required' => 'Text answers text text required.',
            'pages.*.answers.array' => 'Text answers text text text.',
            'pages.*.answers.min' => 'Text text text text text text text answer.',

            'pages.*.answers.*.question_id.required' => 'Text question text.',
            'pages.*.answers.*.question_id.integer' => 'Text question text text text text.',
            'pages.*.answers.*.question_id.exists' => 'Question text text text text text.',

            'pages.*.answers.*.variants.array' => 'Text variants text text text.',

            'pages.*.answers.*.variants.*.id.integer' => 'Text variant text text text text.',
            'pages.*.answers.*.variants.*.id.exists' => __('exception.survey.variant_not_found'),

            'pages.*.answers.*.answer.string' => 'Answer text text string.',
            'pages.*.answers.*.answer.max' => 'Answer text text text :max characters.',

            'pages.*.answers.*.integer.integer' => 'Text answer text text text text.',
        ];
    }
}
