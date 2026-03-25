<?php

namespace App\Http\Resources\Survey\question;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyAggregateWorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fio' => $this->fio,
            'departmentName' => $this->whenLoaded('department', fn () => $this->department?->name),
        ];
    }
}
