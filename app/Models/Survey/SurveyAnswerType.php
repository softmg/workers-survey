<?php

namespace App\Models\Survey;

use App\Enums\SurveyAnswerPayloadKindEnum;
use App\Enums\SurveyAnswerTypeEnum;
use Eloquence\Behaviours\CamelCasing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyAnswerType extends Model
{
    use HasFactory;
    use CamelCasing;

    protected $fillable = [
        'title',
        'code',
        'base_type',
        'custom',
        'multiple',
        'limited',
        'min',
        'max',
    ];

    protected $casts = [
        'base_type' => SurveyAnswerTypeEnum::class,
        'custom' => 'boolean',
        'multiple' => 'boolean',
        'limited' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeWithType(Builder $query, array $types)
    {
        $query->whereIn('base_type', $types);
    }

    /**
     * Text text answer text API (text answer / variants / integer), text text text type question.
     *
     * @return list<SurveyAnswerPayloadKindEnum>
     */
    public function answerPayloadKinds(): array
    {
        return match ($this->base_type) {
            SurveyAnswerTypeEnum::Text => [SurveyAnswerPayloadKindEnum::Text],
            SurveyAnswerTypeEnum::Integer => [SurveyAnswerPayloadKindEnum::Integer],
            SurveyAnswerTypeEnum::Radio => $this->custom
                ? [
                    SurveyAnswerPayloadKindEnum::Variants,
                    SurveyAnswerPayloadKindEnum::Text,
                ]
                : [SurveyAnswerPayloadKindEnum::Variants],
            SurveyAnswerTypeEnum::Checkbox => $this->custom
                ? [
                    SurveyAnswerPayloadKindEnum::Variants,
                    SurveyAnswerPayloadKindEnum::Text,
                ]
                : [SurveyAnswerPayloadKindEnum::Variants],
        };
    }

    /**
     * Text text text payload text text text text text text text text text type question.
     *
     * @param  array<string, mixed>  $payload
     */
    public function payloadHasAnswer(array $payload): bool
    {
        foreach ($this->answerPayloadKinds() as $kind) {
            if ($kind->hasContentInPayload($payload)) {
                return true;
            }
        }

        return false;
    }
}
