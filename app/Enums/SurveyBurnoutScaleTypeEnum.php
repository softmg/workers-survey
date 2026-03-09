<?php

namespace App\Enums;

enum SurveyBurnoutScaleTypeEnum: string
{
    use EnumTrait;

    case EXHAUSTION = 'exhaustion';
    case DEPERSONALIZATION = 'depersonalization';
    case REDUCTION = 'reduction';

    public function label(): string
    {
        return match ($this) {
            self::EXHAUSTION => 'Text text',
            self::DEPERSONALIZATION => 'Text',
            self::REDUCTION => 'Text text',
        };
    }
}
