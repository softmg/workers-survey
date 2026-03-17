<?php

namespace App\Filters;

use EloquentFilter\ModelFilter;

class SurveyTemplatesFilter extends ModelFilter
{
    public function name(string $name): self
    {
        return $this->where(function ($query) use ($name) {
            if (!empty($name)) {
                $query->orWhere('name', 'LIKE', '%' . $name . '%');
            }
        });
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
        $this->where('is_template', true);
        $this->orderBy('created_at', 'desc');
    }
}
