<?php

namespace App\Http\Resources\Survey\question;

use App\Http\Resources\Survey\SurveyAnswerTypeResource;
use App\Http\Resources\Survey\SurveyQuestionVariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'questionNumber' => $this->question_number,
            'question' => $this->question,
            'isRequired' => (bool) $this->is_required,
            'answerType' => SurveyAnswerTypeResource::make($this->answerType),
            'variants' => SurveyQuestionVariantResource::collection($this->variants),
        ];
    }
}
