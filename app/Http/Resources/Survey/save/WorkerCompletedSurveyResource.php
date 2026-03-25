<?php

namespace App\Http\Resources\Survey\save;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerCompletedSurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /** @var int $id */
            'id' => $this->survey->id,
            'name' => $this->survey->name,
            'description' => $this->survey->description,
            /** @var (bool) $isCompleted */
            'isCompleted' => $this->completed,
        ];
    }
}
