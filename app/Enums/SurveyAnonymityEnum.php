<?php

namespace App\Enums;

enum SurveyAnonymityEnum: string
{
    use EnumTrait;

    case Public = 'public';
    case HrOnly = 'hr only';
    case Anonymous = 'anonymous';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Text',
            self::HrOnly => 'Text text HR',
            self::Anonymous => 'Text',
        };
    }
}
