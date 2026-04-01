<?php

namespace App\Services\DTO\Filters;

use App\Enums\SurveyStatusEnum;
use Illuminate\Support\Collection;

class SurveyFilterDTO
{
    public Collection $names;
    public Collection $statuses;
    public Collection $types;

    public function __construct(private Collection $surveys)
    {
        $this->buildFilters();
    }

    public function __invoke(): object
    {
        return (object)[
            'names'    => $this->names,
            'statuses' => $this->statuses,
            'types'    => $this->types,
        ];
    }

    private function buildFilters(): void
    {
        $this->names = $this->surveys
            ->map(fn ($survey) => (object)[
                'key'  => $survey->id,
                'name' => $survey->name,
            ])
            ->values();

        $this->statuses = collect(SurveyStatusEnum::cases())
            ->filter(
                fn (SurveyStatusEnum $enum) =>
                $this->surveys
                    ->pluck('status')
                    ->contains(fn ($s) => $s === $enum)
            )
            ->values();

        $this->types = $this->surveys
            ->pluck('type')
            ->filter()
            ->unique('id')
            ->map(fn ($type) => (object)[
                'key'  => $type->code,
                'name' => $type->name,
            ])
            ->values();
    }
}
