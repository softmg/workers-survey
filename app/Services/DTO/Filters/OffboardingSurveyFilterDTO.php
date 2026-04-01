<?php

namespace App\Services\DTO\Filters;

use App\Models\Worker\Worker;
use Illuminate\Support\Collection;

class OffboardingSurveyFilterDTO
{
    private Collection $workers;

    public function __construct(private Collection $surveyCompletion)
    {
        $this->buildFilters();
    }

    public function __invoke(): object
    {
        return (object)[
            'workers' => $this->workers,
        ];
    }

    private function buildFilters(): void
    {
        $this->surveyCompletion->loadMissing(['worker']);

        $this->workers = $this->surveyCompletion
            ->pluck('worker')
            ->map(fn (Worker $worker) => (object)[
                'key'  => $worker->id,
                'name' => $worker->fio,
            ])
            ->values();
    }
}
