<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoCompletedSurveyResource extends JsonResource
{
    public static function collection($resource)
    {
        $sorted = $resource->where('completed', false)->values();

        return parent::collection($sorted);
    }

    public function toArray(Request $request): array
    {
        return [
            /** @var int $id */
            'id' => $this->id,
            'title' => $this->name,
            'description' => $this->description,
        ];
    }
}
