<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

class ExportOffboardingSurveyResultsRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->whenHas('completed', function () {
            $this->merge([
                'completed' => $this->boolean('completed'),
            ]);
        });
    }

    public function rules(): array
    {
        return [
            /** Text text `Y-m-d` */
            'dismissalDateFrom' => 'nullable|date_format:Y-m-d|before_or_equal:dismissalDateTo',
            /** Text text `Y-m-d` */
            'dismissalDateTo' => 'nullable|date_format:Y-m-d|after_or_equal:dismissalDateFrom',
            'workers' => 'nullable|array|min:1',
            'workers.*' => 'integer|exists:workers,id',
            'completed' => 'nullable|boolean',
            'perPage' => 'nullable|integer|between:1,200',
        ];
    }

    public function attributes()
    {
        return [
            'dismissalDateFrom' => 'text text',
            'dismissalDateTo' => 'text text',
            'workers' => 'employee',
        ];
    }

    public function messages()
    {
        return [
            'dismissalDateFrom.before_or_equal' => __('exception.date_from_before_or_equal_date_to'),
            'dismissalDateTo.after_or_equal' => __('exception.date_to_after_or_equal_date_from'),
        ];
    }
}
