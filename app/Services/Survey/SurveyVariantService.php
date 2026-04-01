<?php

namespace App\Services\Survey;

use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyQuestionVariant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SurveyVariantService
{
    public function createVariants(array $questionsVariants, Collection $createdQuestions, Carbon $now): void
    {
        if (empty($questionsVariants)) {
            return;
        }

        $allVariantsToInsert = [];

        foreach ($questionsVariants as $questionVariantData) {
            $key = "{$questionVariantData['page_id']}_{$questionVariantData['question_number']}";
            $question = $createdQuestions->get($key);
            if ($question && !empty($questionVariantData['variants'])) {
                foreach ($questionVariantData['variants'] as $variant) {
                    $allVariantsToInsert[] = [
                        'question_id' => $question->id,
                        'variant' => $variant,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (!empty($allVariantsToInsert)) {
            SurveyQuestionVariant::insert($allVariantsToInsert);
        }
    }

    public function syncVariants(array $questionsVariants, Collection $existingQuestions, Collection $createdQuestions, Collection $answerTypes, Carbon $now): void
    {
        if (empty($questionsVariants)) {
            return;
        }

        $existingQuestionIdsForVariants = collect($questionsVariants)
            ->filter(function ($qv) {
                return !$qv['is_new'] && !empty($qv['question_id']);
            })
            ->pluck('question_id')
            ->unique()
            ->toArray();

        $allExistingVariants = $this->loadExistingVariants($existingQuestionIdsForVariants, $existingQuestions);

        $allVariantsToDelete = [];
        $allVariantsToInsert = [];

        foreach ($questionsVariants as $qv) {
            if (empty($qv['question_id'])) {
                continue;
            }

            $question = $qv['is_new']
                ? $createdQuestions->get("{$qv['page_id']}_{$qv['question_number']}")
                : $existingQuestions->get($qv['question_id']);

            if (!$question) {
                continue;
            }

            $answerType = $answerTypes->get($qv['answer_type_id']);
            if (!$answerType) {
                continue;
            }

            $existingVariants = $this->getExistingVariantsForQuestion($qv, $question, $allExistingVariants);

            $diff = $this->calculateVariantsDiff($qv, $existingVariants, $answerType);

            if (!empty($diff['toDelete'])) {
                $allVariantsToDelete = [...$allVariantsToDelete, ...$diff['toDelete']];
            }

            if (!empty($diff['toInsert'])) {
                foreach ($diff['toInsert'] as $variantText) {
                    $allVariantsToInsert[] = [
                        'question_id' => $qv['question_id'],
                        'variant' => $variantText,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        $this->applyVariantsChanges($allVariantsToDelete, $allVariantsToInsert);
    }

    private function loadExistingVariants(array $existingQuestionIdsForVariants, Collection $existingQuestions): Collection
    {
        if (empty($existingQuestionIdsForVariants)) {
            return collect();
        }

        $questionsWithoutVariants = [];
        foreach ($existingQuestionIdsForVariants as $questionId) {
            $question = $existingQuestions->get($questionId);
            if ($question && !$question->relationLoaded('variants')) {
                $questionsWithoutVariants[] = $questionId;
            }
        }

        if (empty($questionsWithoutVariants)) {
            return collect();
        }

        return SurveyQuestionVariant::whereIn('question_id', $questionsWithoutVariants)
            ->get()
            ->groupBy('question_id');
    }

    private function getExistingVariantsForQuestion(array $qv, SurveyQuestion $question, Collection $allExistingVariants): Collection
    {
        if ($qv['is_new']) {
            return collect();
        }

        if ($question->relationLoaded('variants')) {
            return $question->variants;
        }

        return $allExistingVariants->get($qv['question_id']) ?? collect();
    }

    private function calculateVariantsDiff(array $qv, Collection $existingVariants, $answerType): array
    {
        $requestVariants = $qv['variants'];
        $requiresVariants = in_array($answerType->base_type?->value, ['radio', 'checkbox']);

        $toDelete = [];
        $toInsert = [];

        if (!$requiresVariants) {
            if ($existingVariants->isNotEmpty()) {
                $toDelete = $existingVariants->pluck('id')->toArray();
            }
        } else {
            $variantsToDelete = $existingVariants->filter(function ($variant) use ($requestVariants) {
                return !in_array($variant->variant, $requestVariants);
            });

            if ($variantsToDelete->isNotEmpty()) {
                $toDelete = $variantsToDelete->pluck('id')->toArray();
            }

            $existingVariantTexts = $existingVariants->pluck('variant')->toArray();
            $newVariants = array_filter($requestVariants, function ($variantText) use ($existingVariantTexts) {
                return !in_array($variantText, $existingVariantTexts);
            });

            $toInsert = $newVariants;
        }

        return [
            'toDelete' => $toDelete,
            'toInsert' => $toInsert,
        ];
    }

    private function applyVariantsChanges(array $variantsToDelete, array $variantsToInsert): void
    {
        if (!empty($variantsToDelete)) {
            SurveyQuestionVariant::whereIn('id', array_unique($variantsToDelete))->delete();
        }

        if (!empty($variantsToInsert)) {
            SurveyQuestionVariant::insert($variantsToInsert);
        }
    }
}
