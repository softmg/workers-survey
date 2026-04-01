<?php

namespace App\Rules;

use App\Models\Survey\Survey;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class RequiredSurveyQuestionsPresentRule implements ValidationRule
{
    public function __construct(
        private Survey $survey,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }

        $answeredIds = [];
        foreach ($value as $pageData) {
            if (isset($pageData['answers']) && is_array($pageData['answers'])) {
                foreach ($pageData['answers'] as $answer) {
                    if (isset($answer['question_id'])) {
                        $answeredIds[] = (int) $answer['question_id'];
                    }
                }
            }
        }

        $answeredIds = array_values(array_unique(array_filter($answeredIds, fn ($v) => $v !== null && $v !== 0)));

        $requiredIds = $this->survey->questions()
            ->where('is_required', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $missing = array_values(array_diff($requiredIds, $answeredIds));

        if ($missing) {
            $fail(__('exception.survey.required_questions_missing', [
                'ids' => implode(', ', $missing),
            ]));
        }
    }
}
