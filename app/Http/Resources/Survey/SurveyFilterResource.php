<?php

namespace App\Http\Resources\Survey;

use App\Http\Resources\EnumResource;
use App\Http\Resources\FilterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyFilterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'names' => FilterResource::collection($this->names),
            'statuses' => EnumResource::collection($this->statuses),
            'types' => SurveyTypeFilterResource::collection($this->types),
        ];
    }
}
