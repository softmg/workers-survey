<?php

namespace App\Rules;

use App\Enums\SurveyAnswerPayloadKindEnum;
use App\Enums\SurveyAnswerTypeEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyQuestion;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class AnswerMatchesQuestionTypeRule implements ValidationRule
{
    protected Survey $survey;

    public function __construct(Survey $survey)
    {
        $this->survey = $survey;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $questionId = Arr::get($value, 'question_id');
        if (! $questionId) {
            return;
        }

        $question = $this->survey
            ->questions
            ->firstWhere('id', $questionId);

        if (! $question) {
            $fail(__('exception.survey.not_belong', ['number' => $questionId]));
            return;
        }

        /** @var SurveyAnswerType $answerTypeModel */
        $answerTypeModel = $question->answerType;
        if (! $answerTypeModel?->base_type) {
            $fail(__('exception.survey.type_missing', ['number' => $questionId]));
            return;
        }

        $hasAnyPayload = SurveyAnswerPayloadKindEnum::Text->hasContentInPayload($value)
            || SurveyAnswerPayloadKindEnum::Integer->hasContentInPayload($value)
            || SurveyAnswerPayloadKindEnum::Variants->hasContentInPayload($value);

        if (! $question->is_required && ! $hasAnyPayload) {
            return;
        }

        match ($answerTypeModel->base_type) {
            SurveyAnswerTypeEnum::Text     => $this->validateText($value, $question, $fail),
            SurveyAnswerTypeEnum::Integer  => $this->validateInteger($value, $question, $fail),
            SurveyAnswerTypeEnum::Radio    => $this->validateRadio($value, $question, $fail),
            SurveyAnswerTypeEnum::Checkbox => $this->validateCheckbox($value, $question, $fail),
        };
    }

    private function validateText(array $value, SurveyQuestion $question, Closure $fail): void
    {
        if (! SurveyAnswerPayloadKindEnum::Text->hasContentInPayload($value)
            || SurveyAnswerPayloadKindEnum::Integer->hasContentInPayload($value)
            || SurveyAnswerPayloadKindEnum::Variants->hasContentInPayload($value)) {
            $fail(__('exception.survey.text_required', [
                'number' => $question->question_number,
            ]));
        }
    }

    private function validateInteger(array $value, SurveyQuestion $question, Closure $fail): void
    {
        if (! SurveyAnswerPayloadKindEnum::Integer->hasContentInPayload($value)
            || SurveyAnswerPayloadKindEnum::Text->hasContentInPayload($value)
            || SurveyAnswerPayloadKindEnum::Variants->hasContentInPayload($value)) {
            $fail(__('exception.survey.integer_required', [
                'number' => $question->question_number,
            ]));
        }

        $answerType = $question->answerType;
        $int = (int) Arr::get($value, 'integer');
        if ($answerType->limited && ($int < $answerType->min || $int > $answerType->max)) {
            $fail(__('exception.survey.integer_limits', [
                'number' => $question->question_number,
                'min'    => $answerType->min,
                'max'    => $answerType->max,
            ]));
        }
    }

    private function validateRadio(array $value, SurveyQuestion $question, Closure $fail): void
    {
        $variants   = Arr::get($value, 'variants', []);
        $allowedIds = $question->variants->pluck('id')->all();
        $num        = $question->question_number;

        $hasVariant = is_array($variants)
            && count($variants) === 1
            && in_array(Arr::get($variants[0], 'id'), $allowedIds, true);

        $hasCustomAnswer = SurveyAnswerPayloadKindEnum::Text->hasContentInPayload($value);

        if ($hasVariant && $hasCustomAnswer) {
            $fail(__('exception.survey.radio_conflict', ['number' => $num]));
            return;
        }

        if ($question->answerType->custom) {
            if ($hasVariant || $hasCustomAnswer) {
                return;
            }

            $fail(__('exception.survey.radio_custom_or_variant', ['number' => $num]));
            return;
        }

        if (! $hasVariant) {
            $fail(__('exception.survey.radio_required', ['number' => $num]));
        }
    }

    private function validateCheckbox(array $value, SurveyQuestion $question, Closure $fail): void
    {
        $variantsPayload = Arr::get($value, 'variants', []);
        $allowedIds      = $question->variants->pluck('id')->all();
        $num             = $question->question_number;

        $hasVariants   = SurveyAnswerPayloadKindEnum::Variants->hasContentInPayload($value);
        $hasCustomText = SurveyAnswerPayloadKindEnum::Text->hasContentInPayload($value);

        if ($hasVariants) {
            foreach ($variantsPayload as $variant) {
                $id = Arr::get($variant, 'id');
                if (! in_array($id, $allowedIds, true)) {
                    $fail(__('exception.survey.checkbox_invalid_variant', ['number' => $num]));
                    return;
                }
            }
        }

        if (! $hasVariants && ! $hasCustomText) {
            $fail(__('exception.survey.' . ($question->answerType->custom
                    ? 'checkbox_required_or_text'
                    : 'checkbox_required'), ['number' => $num]));
            return;
        }

        if ($hasCustomText && ! $question->answerType->custom) {
            $fail(__('exception.survey.checkbox_no_text_allowed', ['number' => $num]));
        }
    }
}
