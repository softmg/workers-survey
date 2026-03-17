<?php

namespace App\Http\Requests\Survey;

use Illuminate\Foundation\Http\FormRequest;

class WorkerCompletedSurveyRequest extends FormRequest
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
            'departments' => 'nullable|array|min:1',
            'departments.*' => 'integer|exists:departments,id',
            'chiefs' => 'nullable|array|min:1',
            'chiefs.*' => 'integer|exists:users,id',
            'workers' => 'nullable|array|min:1',
            'workers.*' => 'integer|exists:workers,id',
            'completed' => 'nullable|boolean',
            'perPage' => 'nullable|integer|between:1,200',
        ];
    }
}
