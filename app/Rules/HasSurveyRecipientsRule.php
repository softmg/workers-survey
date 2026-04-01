<?php

namespace App\Rules;

use App\Services\Survey\SurveyRecipientsService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class HasSurveyRecipientsRule implements ValidationRule, DataAwareRule
{
    private array $data = [];

    public function __construct(
        private readonly SurveyRecipientsService $surveyRecipientsService,
        private readonly string $workersField,
        private readonly string $departmentsField,
    ) {
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $workers = data_get($this->data, $this->workersField, []);
        $departments = data_get($this->data, $this->departmentsField, []);

        $hasRecipients = $this->surveyRecipientsService->hasRecipients(
            is_array($workers) ? $workers : [],
            is_array($departments) ? $departments : []
        );

        if (!$hasRecipients) {
            $fail(__('validation.survey_should_have_recipients'));
        }
    }
}
