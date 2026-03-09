<?php

namespace App\Enums;

enum SurveyStatusEnum: string
{
    use EnumTrait;

    case Created = 'created';
    case Active = 'active';
    case Closed = 'closed';
    case Template = 'template';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Text',
            self::Active => 'Text',
            self::Closed => 'Text',
            self::Template => 'Template',
        };
    }

    /**
     * @return array<array<string, string>>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Created => $this->optionsOnly([self::Created->value, self::Active->value, self::Template->value]),
            self::Active => $this->optionsOnly([self::Active->value, self::Closed->value]),
            self::Closed => $this->optionsOnly([self::Closed->value, self::Active->value]),
            self::Template => $this->optionsOnly([self::Template->value]),
        };
    }
}
