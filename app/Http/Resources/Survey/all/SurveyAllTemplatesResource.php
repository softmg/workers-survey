<?php

namespace App\Http\Resources\Survey\all;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Survey\index\SurveyTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyAllTemplatesResource extends JsonResource
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
        ];
    }
}
