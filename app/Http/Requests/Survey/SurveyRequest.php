<?php

namespace App\Http\Requests\Survey;

use App\Enums\SurveyAnswerTypeEnum;
use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Rules\SurveyTypeConflictRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'names' => 'nullable|array|min:1',
            'names.*' => 'integer|exists:surveys,id',
            /** Text text `Y-m-d` */
            'createdDateFrom' => 'nullable|date_format:Y-m-d|before_or_equal:createdDateTo',
            /** Text text `Y-m-d` */
            'createdDateTo' => 'nullable|date_format:Y-m-d|after_or_equal:createdDateFrom',
            /** Text text `Y-m-d` */
            'endDateFrom' => 'nullable|date_format:Y-m-d|before_or_equal:endDateTo',
            /** Text text `Y-m-d` */
            'endDateTo' => 'nullable|date_format:Y-m-d|after_or_equal:endDateFrom',
            'status' => Rule::enum(SurveyStatusEnum::class),
            'surveyAnswerType' => Rule::enum(SurveyAnswerTypeEnum::class),

            'surveyType' => ['nullable', 'array'],
            'surveyType.*' => [new SurveyTypeConflictRule()],
            'surveyType.*.has' => [Rule::enum(SurveyTypeEnum::class)],
            'surveyType.*.noHas' => [Rule::enum(SurveyTypeEnum::class)],

            'perPage' => 'nullable|integer|between:1,200',
        ];
    }
}
