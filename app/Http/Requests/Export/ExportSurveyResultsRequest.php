<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

class ExportSurveyResultsRequest extends FormRequest
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
            'workers' => 'nullable|array',
            'workers.*' => 'integer',
            'departments' => 'nullable|array',
            'departments.*' => 'integer',
            'chiefs' => 'nullable|array',
            'chiefs.*' => 'integer',
            'completed' => 'nullable|boolean',
        ];
    }
}
