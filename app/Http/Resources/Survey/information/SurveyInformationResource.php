<?php

namespace App\Http\Resources\Survey\information;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Survey\index\SurveyTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyInformationResource extends JsonResource
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
            'completed_count' => $this->completions?->where('completed', true)->count(),
            'workers_count' => $this->completions?->count(),
            'questions_count' => $this->questions_count,
            'created_at' => $this->created_at?->format('Y-m-d'),
            'date_end' => $this->date_end,
            'approximate_time' => $this->approximate_time,
            'anonymity' => EnumResource::make($this->anonymity),
        ];
    }
}
