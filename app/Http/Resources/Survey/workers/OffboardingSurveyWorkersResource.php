<?php

namespace App\Http\Resources\Survey\workers;

use App\Http\Resources\AllWorkersResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OffboardingSurveyWorkersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'worker' => AllWorkersResource::make($this->worker),
            'completed' => $this->completed,
            'dismissalDate' => $this->worker?->dismissal?->last_day?->format('Y-m-d'),
        ];
    }
}
