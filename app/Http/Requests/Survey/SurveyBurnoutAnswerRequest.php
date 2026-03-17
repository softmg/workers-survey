<?php

namespace App\Http\Requests\Survey;

use App\Models\Department;
use App\Models\Project;
use App\Models\User\User;
use App\Models\Worker\Worker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyBurnoutAnswerRequest extends FormRequest
{
    public function rules(): array
    {
        $userIdRules = [
            'integer',
            Rule::exists(User::class, 'id'),
            'distinct',
        ];

        return [
            /** Text text `Y-m-d` */
            'date_from' => ['required', 'date_format:Y-m-d', 'before_or_equal:date_to'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'worker' => ['nullable', 'array'],
            'project' => ['nullable', 'array'],
            'department' => ['nullable', 'array'],
            'chief' => ['nullable', 'array'],
            'hr' => ['nullable', 'array'],

            'chief.*'    => $userIdRules,
            'hr.*'       => $userIdRules,
            'department.*' => [
                'integer',
                Rule::exists(Department::class, 'id'),
                'distinct',
            ],
            'worker.*' => [
                'integer',
                Rule::exists(Worker::class, 'id'),
                'distinct',
            ],
            'project.*' => [
                'integer',
                Rule::exists(Project::class, 'id'),
                'distinct',
            ],
        ];
    }

    public function messages(): array
    {
        $arrayMessage = 'Text :attribute text text text text.';
        $integerMessage = 'Text :attribute text text text text.';
        $existsMessage = 'Text text :attribute.';
        $distinctMessage = 'Text text :attribute text text text.';

        return [
            'date_from.date_format' => 'Text :attribute text text text text text Text-Text-Text.',
            'date_to.date_format'   => 'Text :attribute text text text text text Text-Text-Text.',
            'date_from.before_or_equal' => 'Date text text text text text text text.',

            'worker.array'     => $arrayMessage,
            'project.array'    => $arrayMessage,
            'department.array' => $arrayMessage,
            'chief.array'      => $arrayMessage,
            'hr.array'         => $arrayMessage,

            'chief.*.integer'  => $integerMessage,
            'chief.*.exists'   => $existsMessage,
            'chief.*.distinct' => $distinctMessage,

            'hr.*.integer'  => $integerMessage,
            'hr.*.exists'   => $existsMessage,
            'hr.*.distinct' => $distinctMessage,

            'department.*.integer'  => $integerMessage,
            'department.*.exists'   => $existsMessage,
            'department.*.distinct' => $distinctMessage,

            'worker.*.integer'  => $integerMessage,
            'worker.*.exists'   => $existsMessage,
            'worker.*.distinct' => $distinctMessage,

            'project.*.integer'  => $integerMessage,
            'project.*.exists'   => $existsMessage,
            'project.*.distinct' => $distinctMessage,
        ];
    }

    public function attributes(): array
    {
        return [
            'date_from'  => 'date text',
            'date_to'    => 'date text',

            'worker'        => 'text',
            'worker.*'      => 'employee',
            'project'       => 'text',
            'project.*'     => 'text',
            'department'    => 'departments',
            'department.*'  => 'department',
            'chief'         => 'text',
            'chief.*'       => 'text',
            'hr'            => 'HR-text',
            'hr.*'          => 'HR-employee',
        ];
    }
}
