<?php

namespace App\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class SurveyStatisticFilter extends ModelFilter
{
    public function workers(array $workers): self
    {
        return $this->whereIn('worker_id', $workers);
    }

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

    public function completed(bool $completed): self
    {
        return $this->whereHas('worker', function (Builder $query) use ($completed) {
            return $this->where('completed', $completed);
        });
    }
}
