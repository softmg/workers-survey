<?php

namespace App\Http\Resources\Survey\answers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyQuestionStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'question' => $this->question,
            'questionNumber' => $this->question_number,
            'pageName' => $this->page->name ?? 'Text text',
            'type' => [
                'id' => $this->answerType?->id,
                'title' => $this->answerType?->title,
            ],
            'answers' => SurveyAnswerResource::make($this),
        ];
    }
}
