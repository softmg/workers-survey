<?php

namespace App\Http\Resources\Survey\all;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Survey\index\SurveyTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyAllResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /** @var int $id */
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => EnumResource::make($this->status),
            'surveyType' => SurveyTypeResource::make($this->type),
            'created_at' => $this->created_at?->format('Y-m-d'),
            'date_end' => $this->date_end,
            'approximate_time' => $this->approximate_time,
            'anonymity' => EnumResource::make($this->anonymity),
            'workersCompleted' => $this->completions()->where('completed', true)->count(),
            'workersCount' => $this->completions->count(),
        ];
    }
}
