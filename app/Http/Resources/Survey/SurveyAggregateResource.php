<?php

namespace App\Http\Resources\Survey;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Survey\index\SurveyTypeResource;
use App\Http\Resources\Survey\question\SurveyQuestionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyAggregateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => EnumResource::make($this->status),
            'surveyType' => SurveyTypeResource::make($this->whenLoaded('type')),
            'dateEnd' => $this->date_end,
            'approximateTime' => $this->approximate_time,
            'anonymity' => EnumResource::make($this->anonymity),
            'isTemplate' => $this->is_template,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),

            'questions' => SurveyQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
