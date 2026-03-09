<?php

namespace App\Enums;

enum SurveyTypeEnum: string
{
    use EnumTrait;

    case Default = 'default';
    case Onboarding = 'onboarding';
    case Offboarding = 'offboarding';
    case Impulse = 'impulse';
    case ProfessionalBurnout = 'professional burnout';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Text',
            self::Onboarding => 'Text',
            self::Offboarding => 'Text',
            self::Impulse => 'Impulse',
            self::ProfessionalBurnout => 'Text text',
        };
    }

    public static function default(): self
    {
        return self::Default;
    }
}
