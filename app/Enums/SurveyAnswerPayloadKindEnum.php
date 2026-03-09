<?php

namespace App\Enums;

enum SurveyAnswerPayloadKindEnum: string
{
    use EnumTrait;

    case Text = 'text';
    case Variants = 'variants';
    case Integer = 'integer';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text answer',
            self::Variants => 'Variants',
            self::Integer => 'Text',
        };
    }

    public function requestKey(): string
    {
        return match ($this) {
            self::Text => 'answer',
            self::Integer => 'integer',
            self::Variants => 'variants',
        };
    }

    /**
     * Text text text text text text text text payload answer text question.
     *
     * @param  array<string, mixed>  $payload
     */
    public function hasContentInPayload(array $payload): bool
    {
        $key = $this->requestKey();

        return match ($this) {
            self::Text => array_key_exists($key, $payload)
                && $payload[$key] !== null
                && trim((string) $payload[$key]) !== '',
            self::Integer => array_key_exists($key, $payload)
                && $payload[$key] !== null,
            self::Variants => array_key_exists($key, $payload)
                && is_array($payload[$key])
                && count($payload[$key]) > 0,
        };
    }
}
