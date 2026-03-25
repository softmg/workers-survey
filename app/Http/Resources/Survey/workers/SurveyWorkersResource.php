<?php

namespace App\Http\Resources\Survey\workers;

use App\Http\Resources\AllWorkersResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyWorkersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'completed' => $this->completed,
            'completionDate' => $this->completion_date?->format('Y-m-d'),
        ];

        if ($this->relationLoaded('worker')) {
            $data['worker'] = AllWorkersResource::make($this->worker);
        }

        return $data;
    }
}
