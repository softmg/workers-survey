<?php

namespace App\Http\Resources\Survey\question;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Survey\index\SurveyTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyPagesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var int $id */
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => EnumResource::make($this->status),
            'surveyType' => SurveyTypeResource::make($this->whenLoaded('type')),
            'created_at' => $this->created_at?->format('Y-m-d'),
            'date_end' => $this->date_end,
            'approximate_time' => $this->approximate_time,
            'anonymity' => EnumResource::make($this->anonymity),
            'pages' => SurveyPageResource::collection($this->whenLoaded('pages')),
            'workers' => SurveyAggregateWorkerResource::collection($this->whenLoaded('workers')),
        ];
    }
}
