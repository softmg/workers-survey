<?php

namespace App\Http\Requests\Survey;

use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Rules\SurveyTypeConflictRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyTemplatesFilterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'nullable|string',
            'status' => ['nullable', Rule::enum(SurveyStatusEnum::class)],
            'surveyType' => ['nullable', 'array'],
            'surveyType.*' => [new SurveyTypeConflictRule()],
            'surveyType.*.has' => ['nullable', Rule::enum(SurveyTypeEnum::class)],
            'surveyType.*.noHas' => ['nullable', Rule::enum(SurveyTypeEnum::class)],
        ];
    }
}
