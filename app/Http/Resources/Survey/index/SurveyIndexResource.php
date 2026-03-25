<?php

namespace App\Http\Resources\Survey\index;

use App\Http\Resources\EnumResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyIndexResource extends JsonResource
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
            'createdAt' => $this->created_at?->format('Y-m-d'),
            'dateEnd' => $this->date_end,
            'approximateTime' => $this->approximate_time,
            'anonymity' => EnumResource::make($this->anonymity),
            /** @var bool $isCompleted */
            'isCompleted' => optional($this->completions->first())->completed ?? false,
        ];
    }
}
