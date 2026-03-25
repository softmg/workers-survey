<?php

namespace App\Http\Resources\Survey\answers;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyIntegerAnswerStatisticsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'value' => (int)$this->resource['value'],
            'count' => $this->resource['count'],
        ];
    }
}
