<?php

namespace App\Services\DTO\Filters;

use App\Models\Department;
use App\Models\Worker\Worker;
use Illuminate\Support\Collection;

class WorkerSurveyFilterDTO
{
    private Collection $workers;
    private Collection $departments;
    private Collection $chiefs;

    public function __construct(private Collection $surveyCompletion)
    {
        $this->buildFilters();
    }

    public function __invoke(): object
    {
        return (object)[
            'workers' => $this->workers,
            'departments' => $this->departments,
            'chiefs' => $this->chiefs,
        ];
    }

    private function buildFilters(): void
    {
        $this->workers = $this->surveyCompletion
            ->pluck('worker')
            ->filter()
            ->map(fn (Worker $worker) => (object)[
                'key'  => $worker->id,
                'name' => $worker->fio,
            ])
            ->values();

        $this->departments = $this->surveyCompletion
            ->pluck('worker.department')
            ->filter()
            ->unique('id')
            ->map(fn (Department $department) => (object)[
                'key' => $department->id,
                'name' => $department->name,
            ])
            ->values();

        $this->chiefs = $this->surveyCompletion
            ->pluck('worker.chief.worker')
            ->filter()
            ->unique()
            ->map(fn (Worker $worker) => (object)[
                'key'  => $worker->user->id,
                'name' => $worker->fio,
            ])
            ->values();
    }
}
