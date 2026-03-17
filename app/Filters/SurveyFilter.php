<?php

namespace App\Filters;

use App\Enums\SurveyStatusEnum;
use Carbon\Carbon;
use EloquentFilter\ModelFilter;

class SurveyFilter extends ModelFilter
{
    public function names(array $names): self
    {
        return $this->whereIn('id', $names);
    }

    public function createdDateFrom(string $createdDateFrom): self
    {
        return $this->where('created_at', '>=', Carbon::parse($createdDateFrom)->startOfDay());
    }

    public function createdDateTo(string $createdDateTo): self
    {
        return $this->where('created_at', '<=', Carbon::parse($createdDateTo)->endOfDay());
    }

    public function endDateFrom(string $endDateFrom): self
    {
        return $this->where('date_end', '>=', Carbon::parse($endDateFrom)->startOfDay());
    }

    public function endDateTo(string $endDateTo): self
    {
        return $this->where('date_end', '<=', Carbon::parse($endDateTo)->endOfDay());

    }

    public function status(string $status): self
    {
        return $this->where('status', $status);
    }

    public function surveyType(array $items): self
    {
        $include = [];
        $exclude = [];

        foreach ($items as $item) {
            if (isset($item['has'])) {
                $include[] = $item['has'];
            }
            if (isset($item['noHas'])) {
                $exclude[] = $item['noHas'];
            }
        }

        if ($include) {
            $this->whereHas('type', fn ($q) => $q->whereIn('code', $include));
        }

        if ($exclude) {
            $this->whereHas('type', fn ($q) => $q->whereNotIn('code', $exclude));
        }

        return $this;
    }

    public function setup(): void
    {
        $this->whereIn('status', [SurveyStatusEnum::Active, SurveyStatusEnum::Closed]);
        $this->orderBy('status');
        $this->orderBy('date_end');
        $this->orderBy('created_at', 'desc');
    }
}
