<?php

namespace App\Enums;

enum SurveyAnswerTypeEnum: string
{
    use EnumTrait;

    case Radio = 'radio';
    case Text = 'text';
    case Integer = 'integer';
    case Checkbox = 'checkbox';

    public function label(): string
    {
        return match ($this) {
            self::Radio => 'Text text',
            self::Text => 'Text question',
            self::Integer => 'Text text',
            self::Checkbox => 'Text text',
        };
    }
}
