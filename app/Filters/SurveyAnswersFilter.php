<?php

namespace App\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class SurveyAnswersFilter extends ModelFilter
{
    public function departments(array $departments): self
    {
        return $this->whereHas('answers', function (Builder $query) use ($departments) {
            $query->whereHas('worker', function (Builder $q) use ($departments) {
                $q->whereIn('department_id', $departments);
            });
        });
    }

    public function chiefs(array $chiefs): self
    {
        return $this->whereHas('answers', function (Builder $query) use ($chiefs) {
            $query->whereHas('worker', function (Builder $q) use ($chiefs) {
                $q->whereIn('chief_id', $chiefs);
            });
        });
    }

    public function workers(array $workers): self
    {
        return $this->whereHas('answers', function (Builder $query) use ($workers) {
            $query->whereHas('worker', function (Builder $q) use ($workers) {
                $q->whereIn('worker_id', $workers);
            });
        });
    }

    public function dismissalDateFrom(string $date): self
    {
        return $this->whereHas('answers.worker.dismissal', function (Builder $query) use ($date) {
            $query->where('last_day', '>=', $date);
        });
    }

    public function dismissalDateTo(string $date): self
    {
        return $this->whereHas('answers.worker.dismissal', function (Builder $query) use ($date) {
            $query->where('last_day', '<=', $date);
        });
    }

    public function completed(bool $completed): self
    {
        if (!$completed) {
            return $this->where('id', -1);
        }

        return $this;
    }
}
