<?php

namespace App\Http\Requests\Survey;

use App\Enums\SurveyAnonymityEnum;
use App\Enums\SurveyStatusEnum;
use App\Rules\AnonymityChangeRule;
use App\Rules\ActiveWorkerRule;
use App\Rules\UniqueAnswerVariantsPerQuestionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSurveyAggregateRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $survey = $this->route('survey');

        return [
            'survey.name' => 'required|string|max:250',
            'survey.survey_type_id' => 'nullable|integer|exists:survey_types,id',
            'survey.date_end' => 'nullable|date_format:Y-m-d',
            'survey.description' => 'nullable|string|max:500',
            'survey.is_template' => 'nullable|boolean',
            'survey.approximate_time' => 'required|integer|min:1|max:60',
            'survey.anonymity' => ['required', 'string', Rule::enum(SurveyAnonymityEnum::class), new AnonymityChangeRule($survey)],
            'survey.status' => ['required', 'string', Rule::enum(SurveyStatusEnum::class)],

            'workers_id' => 'nullable|array',
            'workers_id.*' => ['integer', 'exists:workers,id', new ActiveWorkerRule()],
            'departments_id' => 'nullable|array',
            'departments_id.*' => 'integer|exists:departments,id',

            'survey.survey_pages' => 'nullable|array',
            // Text text id text text text, text text
            'survey.survey_pages.*.id' => 'nullable|integer|exists:survey_pages,id',
            'survey.survey_pages.*.name' => 'nullable|string|max:250',
            'survey.survey_pages.*.description' => 'nullable|string|max:500',
            'survey.survey_pages.*.survey_questions' => 'nullable|array',
            // Text text id question text text, text text
            'survey.survey_pages.*.survey_questions.*.id' => 'nullable|integer|exists:survey_questions,id',
            'survey.survey_pages.*.survey_questions.*.question' => 'required|string|max:1000',
            'survey.survey_pages.*.survey_questions.*.is_required' => 'required|boolean',
            'survey.survey_pages.*.survey_questions.*.answer_type_id' => 'required|integer|exists:survey_answer_types,id',

            'survey.survey_pages.*.survey_questions.*.survey_answer_variants' => ['nullable', 'array', new UniqueAnswerVariantsPerQuestionRule()],
            'survey.survey_pages.*.survey_questions.*.survey_answer_variants.*' => 'required|string|min:1|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'survey.name' => 'name survey',
            'survey.survey_type_id' => 'type survey',
            'survey.date_end' => 'date text',
            'survey.description' => 'description survey',
            'survey.is_template' => 'template survey',
            'survey.approximate_time' => 'text text',
            'survey.anonymity' => 'text',
            'survey.status' => 'text survey',
            'workers_id' => 'text',
            'workers_id.*' => 'employee',
            'departments_id' => 'departments',
            'departments_id.*' => 'department',
            'survey.survey_pages' => 'text survey',
            'survey.survey_pages.*.id' => 'text text',
            'survey.survey_pages.*.name' => 'name text',
            'survey.survey_pages.*.description' => 'description text',
            'survey.survey_pages.*.survey_questions' => 'questions survey',
            'survey.survey_pages.*.survey_questions.*.id' => 'text question',
            'survey.survey_pages.*.survey_questions.*.question' => 'text question',
            'survey.survey_pages.*.survey_questions.*.is_required' => 'text question',
            'survey.survey_pages.*.survey_questions.*.answer_type_id' => 'type answer',
            'survey.survey_pages.*.survey_questions.*.survey_answer_variants' => 'variants answers',
            'survey.survey_pages.*.survey_questions.*.survey_answer_variants.*' => 'variant answer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'survey.name.required' => 'Name survey required text text.',
            'survey.name.string' => 'Name survey text text string.',
            'survey.name.max' => 'Name survey text text text :max characters.',

            'survey.survey_type_id.integer' => 'Type survey text text text text.',
            'survey.survey_type_id.exists' => 'Selected type survey text text.',

            'survey.date_end.date_format' => 'Date text text text text text Y-m-d.',

            'survey.description.string' => 'Description survey text text string.',
            'survey.description.max' => 'Description survey text text text :max characters.',

            'survey.is_template.boolean' => 'Text text survey text text text text.',

            'survey.approximate_time.integer' => 'Text text text text text text.',
            'survey.approximate_time.min' => 'Text text text text text text :min text.',
            'survey.approximate_time.max' => 'Text text text text text :max text.',

            'survey.anonymity.required' => 'Text text required text text.',
            'survey.anonymity.string' => 'Text text text string.',

            'survey.status.string' => 'Text survey text text string.',

            'workers_id.array' => 'Text employees text text text.',
            'workers_id.*.integer' => 'Text employee text text text text.',
            'workers_id.*.exists' => 'Employee text text text text text.',

            'departments_id.array' => 'Text text text text text.',
            'departments_id.*.integer' => 'Text department text text text text.',
            'departments_id.*.exists' => 'Department text text text text text.',

            'survey.survey_pages.array' => 'Text text text text text.',
            'survey.survey_pages.*.id.integer' => 'Text text text text text text.',
            'survey.survey_pages.*.id.exists' => 'Text text text text text text.',
            'survey.survey_pages.*.name.string' => 'Name text text text string.',
            'survey.survey_pages.*.name.max' => 'Name text text text text :max characters.',
            'survey.survey_pages.*.description.string' => 'Description text text text string.',
            'survey.survey_pages.*.description.max' => 'Description text text text text :max characters.',

            'survey.survey_pages.*.survey_questions.array' => 'Text questions text text text.',
            'survey.survey_pages.*.survey_questions.*.id.integer' => 'Text question text text text text.',
            'survey.survey_pages.*.survey_questions.*.id.exists' => 'Question text text text text text.',
            'survey.survey_pages.*.survey_questions.*.question.required' => 'Text question text text text.',
            'survey.survey_pages.*.survey_questions.*.question.string' => 'Text question text text string.',
            'survey.survey_pages.*.survey_questions.*.question.max' => 'Text question text text text :max characters.',

            'survey.survey_pages.*.survey_questions.*.is_required.boolean' => 'Text text question text text text text.',

            'survey.survey_pages.*.survey_questions.*.answer_type_id.required' => 'Type answer text text text.',
            'survey.survey_pages.*.survey_questions.*.answer_type_id.integer' => 'Type answer text text text text.',
            'survey.survey_pages.*.survey_questions.*.answer_type_id.exists' => 'Selected type answer text text.',

            'survey.survey_pages.*.survey_questions.*.survey_answer_variants.array' => 'Text variants answers text text text.',
            'survey.survey_pages.*.survey_questions.*.survey_answer_variants.*.required' => 'Variant answer text text text.',
            'survey.survey_pages.*.survey_questions.*.survey_answer_variants.*.string' => 'Variant answer text text string.',
            'survey.survey_pages.*.survey_questions.*.survey_answer_variants.*.min' => 'Variant answer text text text empty.',
            'survey.survey_pages.*.survey_questions.*.survey_answer_variants.*.max' => 'Variant answer text text text :max characters.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $workersId = $this->input('workers_id', []);
            $departmentsId = $this->input('departments_id', []);
            $workersNotEmpty = is_array($workersId) && count($workersId) > 0;
            $departmentsNotEmpty = is_array($departmentsId) && count($departmentsId) > 0;

            if (!$workersNotEmpty && !$departmentsNotEmpty) {
                $validator->errors()->add('workers_id', 'Text text text text text employee text text department.');
                return;
            }

            if ($workersNotEmpty) {
                return;
            }
        });
    }
}
