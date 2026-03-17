<?php

namespace App\Http\Resources\Survey;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyAnswerTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'baseType' => $this->baseType?->value,
            'custom' => (bool) $this->custom,
            'multiple' => (bool) $this->multiple,
            'limited' => (bool) $this->limited,
            $this->mergeWhen($this->limited, [
                'min' => $this->min,
                'max' => $this->max,
            ]),
        ];
    }
}
