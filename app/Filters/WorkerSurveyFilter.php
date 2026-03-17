<?php

namespace App\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class WorkerSurveyFilter extends ModelFilter
{
    public function departments(array $departments): self
    {
        return $this->whereHas('worker', function (Builder $query) use ($departments) {
            $query->whereIn('department_id', $departments);
        });
    }

    public function chiefs(array $chiefs): self
    {
        return $this->whereHas('worker', function (Builder $query) use ($chiefs) {
            $query->whereIn('chief_id', $chiefs);
        });
    }

    public function workers(array $workers): self
    {
        return $this->whereHas('worker', function (Builder $query) use ($workers) {
            $query->whereIn('worker_id', $workers);
        });
    }

    public function dismissalDateFrom(string $date): self
    {
        return $this->whereHas('worker.dismissal', function (Builder $query) use ($date) {
            $query->where('last_day', '>=', $date);
        });
    }

    public function dismissalDateTo(string $date): self
    {
        return $this->whereHas('worker.dismissal', function (Builder $query) use ($date) {
            $query->where('last_day', '<=', $date);
        });
    }

    public function completed(bool $completed): self
    {
        return $this->where('completed', $completed);
    }

    public function setup(): void
    {
        $this->orderBy('id', 'desc');
    }
}
