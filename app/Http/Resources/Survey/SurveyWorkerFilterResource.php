<?php

namespace App\Http\Resources\Survey;

use App\Http\Resources\FilterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyWorkerFilterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'workers' => FilterResource::collection($this->workers),
            'departments' => FilterResource::collection($this->departments),
            'chiefs' => FilterResource::collection($this->chiefs),
        ];
    }
}
