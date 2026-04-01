<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SurveyTypeConflictRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $has   = $value['has']   ?? null;
        $noHas = $value['noHas'] ?? null;

        if ($has && $noHas && $has === $noHas) {
            $fail("Type text text text text text text: {$has}");
        }
    }
}
