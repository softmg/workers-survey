<?php

namespace App\Http\Resources\Survey;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyTypeFilterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->key,
            'value' => $this->name,
        ];
    }
}
