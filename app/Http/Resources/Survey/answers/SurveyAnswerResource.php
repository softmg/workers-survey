<?php

namespace App\Http\Resources\Survey\answers;

use App\Http\Resources\Survey\SurveyAnswerVariantsResource;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin SurveyQuestion
 */
class SurveyAnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $variantAnswers = $this->answers
            ->filter(fn ($ans) => is_null($ans->answer_text) && is_null($ans->answer_int));

        $variants = $this->variants->each(fn ($variant) =>
        $variant->count = $variantAnswers
            ->flatMap->answerVariants
            ->where('variant_id', $variant->id)
            ->count());

        $customAnswers = $this->answers
            ->filter(fn ($ans) => $ans->answer_text !== null);

        $integerAnswers = $this->answers
            ->filter(fn ($ans) => $ans->answer_int !== null)
            ->pluck('answer_int')
            ->countBy()
            ->map(fn ($count, $value) => [
                'value' => $value,
                'count' => $count,
            ])
            ->sortBy('value')
            ->values();

        return [
            'variants' => SurveyAnswerVariantsResource::collection(
                $variants
            ),

            'scale' => SurveyIntegerAnswerStatisticsResource::collection(
                new Collection($integerAnswers)
            ),

            'custom' => [
                'count' => $customAnswers->count(),
                'data'  => SurveyCustomAnswerResource::collection(
                    $customAnswers
                ),
            ],
        ];
    }
}
