<?php

namespace App\Services\Survey;

use App\Exceptions\Survey\PageNotFoundForQuestionsException;
use App\Exceptions\Survey\QuestionNotBelongsToSurveyException;
use App\Exceptions\Survey\VariantsNotAllowedForAnswerTypeException;
use App\Exceptions\Survey\VariantsRequiredForAnswerTypeException;
use App\Enums\SurveyAnswerTypeEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyQuestion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyQuestionService
{
    public function collectAnswerTypes(array $validated): Collection
    {
        $answerTypeIds = [];

        if (!empty($validated['survey']['survey_pages'])) {
            foreach ($validated['survey']['survey_pages'] as $pageData) {
                if (!empty($pageData['survey_questions'])) {
                    foreach ($pageData['survey_questions'] as $questionData) {
                        if (!empty($questionData['answer_type_id'])) {
                            $answerTypeIds[] = $questionData['answer_type_id'];
                        }
                    }
                }
            }
        }

        $answerTypeIds = array_unique($answerTypeIds);

        return !empty($answerTypeIds)
            ? SurveyAnswerType::whereIn('id', $answerTypeIds)->get()->keyBy('id')
            : collect();
    }

    public function validateQuestion(array $questionData, int $questionNumber, Collection $answerTypes): void
    {
        $answerType = $answerTypes->get($questionData['answer_type_id']);

        if (!$answerType) {
            return;
        }

        $hasVariants = !empty($questionData['survey_answer_variants'])
            && count($questionData['survey_answer_variants']) > 0;

        if ($hasVariants && in_array($answerType->baseType, [SurveyAnswerTypeEnum::Text, SurveyAnswerTypeEnum::Integer], true)) {
            throw new VariantsNotAllowedForAnswerTypeException(
                $questionNumber,
                $answerType->title
            );
        }

        if (!$answerType->custom && $answerType->baseType !== SurveyAnswerTypeEnum::Integer && $answerType->baseType !== SurveyAnswerTypeEnum::Text) {
            $variantsCount = $hasVariants ? count($questionData['survey_answer_variants']) : 0;

            if ($variantsCount === 0) {
                throw new VariantsRequiredForAnswerTypeException(
                    $questionNumber,
                    $answerType->title
                );
            }
        }
    }

    public function validateQuestionIds(array $requestQuestionIds, int $surveyId): void
    {
        if (empty($requestQuestionIds)) {
            return;
        }

        $invalidQuestionIds = SurveyQuestion::whereIn('id', $requestQuestionIds)
            ->where('survey_id', '!=', $surveyId)
            ->pluck('id')
            ->toArray();

        if (!empty($invalidQuestionIds)) {
            throw new QuestionNotBelongsToSurveyException($invalidQuestionIds);
        }
    }

    public function createQuestions(Survey $survey, array $validated, array $pageMapping, Collection $answerTypes, int &$questionNumber): array
    {
        if (empty($validated['survey']['survey_pages'])) {
            return ['variants' => [], 'questions' => collect()];
        }

        $questionsToInsert = [];
        $questionsVariants = [];
        $now = now();

        foreach ($validated['survey']['survey_pages'] as $pageIndex => $pageData) {
            $currentPageId = $pageMapping[$pageIndex] ?? null;
            if (!$currentPageId) {
                continue;
            }

            if (!empty($pageData['survey_questions'])) {
                foreach ($pageData['survey_questions'] as $questionData) {
                    $this->validateQuestion($questionData, $questionNumber, $answerTypes);

                    $questionsToInsert[] = [
                        'survey_id' => $survey->id,
                        'survey_page_id' => $currentPageId,
                        'question' => $questionData['question'],
                        'answer_type_id' => $questionData['answer_type_id'],
                        'question_number' => $questionNumber,
                        'is_required' => $questionData['is_required'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (!empty($questionData['survey_answer_variants'])) {
                        $questionsVariants[] = [
                            'page_id' => $currentPageId,
                            'question_number' => $questionNumber,
                            'variants' => $questionData['survey_answer_variants'],
                        ];
                    }

                    $questionNumber++;
                }
            }
        }

        $createdQuestions = collect();
        if (!empty($questionsToInsert)) {
            SurveyQuestion::insert($questionsToInsert);

            $createdQuestions = $this->getCreatedQuestions($survey, $questionsToInsert);

            foreach ($questionsVariants as &$qv) {
                $key = "{$qv['page_id']}_{$qv['question_number']}";
                $question = $createdQuestions->get($key);
                if ($question) {
                    $qv['question_id'] = $question->id;
                }
            }
            unset($qv);
        }

        return ['variants' => $questionsVariants, 'questions' => $createdQuestions];
    }

    public function syncQuestions(Survey $survey, array $validated, Collection $existingQuestions, array $pageMapping, Collection $answerTypes, int &$questionNumber): array
    {
        if (empty($validated['survey']['survey_pages'])) {
            return ['variants' => [], 'questions' => collect()];
        }

        $requestQuestionIds = [];

        foreach ($validated['survey']['survey_pages'] as $pageData) {
            if (!empty($pageData['survey_questions'])) {
                foreach ($pageData['survey_questions'] as $questionData) {
                    if (!empty($questionData['id'])) {
                        $requestQuestionIds[] = $questionData['id'];
                    }
                }
            }
        }

        $this->validateQuestionIds($requestQuestionIds, $survey->id);

        $this->deleteRemovedQuestions($existingQuestions, $requestQuestionIds);

        $questionsToUpdate = [];
        $questionsToCreate = [];
        $questionsVariants = [];
        $now = now();

        foreach ($validated['survey']['survey_pages'] as $pageIndex => $pageData) {
            if (!empty($pageData['survey_questions'])) {
                $currentPageId = $pageMapping[$pageIndex] ?? null;
                if (!$currentPageId) {
                    throw new PageNotFoundForQuestionsException($pageIndex);
                }

                foreach ($pageData['survey_questions'] as $questionData) {
                    $this->validateQuestion($questionData, $questionNumber, $answerTypes);

                    if (!empty($questionData['id']) && $existingQuestions->has($questionData['id'])) {
                        $question = $existingQuestions->get($questionData['id']);
                        $questionsToUpdate[] = [
                            'id' => $question->id,
                            'survey_page_id' => $currentPageId,
                            'question' => $questionData['question'],
                            'answer_type_id' => $questionData['answer_type_id'],
                            'question_number' => $questionNumber,
                            'is_required' => $questionData['is_required'],
                        ];

                        $questionsVariants[] = [
                            'question_id' => $question->id,
                            'question_number' => $questionNumber,
                            'is_new' => false,
                            'answer_type_id' => $questionData['answer_type_id'],
                            'variants' => $questionData['survey_answer_variants'] ?? [],
                        ];
                    } else {
                        $questionsToCreate[] = [
                            'survey_id' => $survey->id,
                            'survey_page_id' => $currentPageId,
                            'question' => $questionData['question'],
                            'answer_type_id' => $questionData['answer_type_id'],
                            'question_number' => $questionNumber,
                            'is_required' => $questionData['is_required'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $questionsVariants[] = [
                            'page_id' => $currentPageId,
                            'question_number' => $questionNumber,
                            'is_new' => true,
                            'answer_type_id' => $questionData['answer_type_id'],
                            'variants' => $questionData['survey_answer_variants'] ?? [],
                        ];
                    }

                    $questionNumber++;
                }
            }
        }

        if (!empty($questionsToUpdate)) {
            $this->updateExistingQuestions($questionsToUpdate);
        }

        $createdQuestions = collect();
        if (!empty($questionsToCreate)) {
            $createdQuestions = $this->createNewQuestions($survey, $questionsToCreate, $now);

            foreach ($questionsVariants as &$qv) {
                if ($qv['is_new']) {
                    $key = "{$qv['page_id']}_{$qv['question_number']}";
                    $question = $createdQuestions->get($key);
                    if ($question) {
                        $qv['question_id'] = $question->id;
                    }
                }
            }
            unset($qv);
        }

        return ['variants' => $questionsVariants, 'questions' => $createdQuestions];
    }

    private function deleteRemovedQuestions(Collection $existingQuestions, array $requestQuestionIds): void
    {
        $questionsToDelete = $existingQuestions->keys()->diff($requestQuestionIds);
        if ($questionsToDelete->isNotEmpty()) {
            SurveyQuestion::whereIn('id', $questionsToDelete)->delete();
        }
    }

    private function updateExistingQuestions(array $questionsToUpdate): void
    {
        $ids = collect($questionsToUpdate)->pluck('id')->toArray();
        $pdo = DB::connection()->getPdo();

        $pageIdCases = collect($questionsToUpdate)->map(function ($q) {
            return "WHEN {$q['id']} THEN {$q['survey_page_id']}";
        })->implode(' ');

        $questionCases = collect($questionsToUpdate)->map(function ($q) use ($pdo) {
            $question = $pdo->quote($q['question']);
            return "WHEN {$q['id']} THEN {$question}";
        })->implode(' ');

        $answerTypeCases = collect($questionsToUpdate)->map(function ($q) {
            return "WHEN {$q['id']} THEN {$q['answer_type_id']}";
        })->implode(' ');

        $questionNumberCases = collect($questionsToUpdate)->map(function ($q) {
            return "WHEN {$q['id']} THEN {$q['question_number']}";
        })->implode(' ');

        $isRequiredCases = collect($questionsToUpdate)->map(function ($q) {
            return "WHEN {$q['id']} THEN " . ($q['is_required'] ? '1' : '0');
        })->implode(' ');

        DB::table('survey_questions')
            ->whereIn('id', $ids)
            ->update([
                'survey_page_id' => DB::raw("CASE id {$pageIdCases} END"),
                'question' => DB::raw("CASE id {$questionCases} END"),
                'answer_type_id' => DB::raw("CASE id {$answerTypeCases} END"),
                'question_number' => DB::raw("CASE id {$questionNumberCases} END"),
                'is_required' => DB::raw("CASE id {$isRequiredCases} END"),
            ]);
    }

    private function createNewQuestions(Survey $survey, array $questionsToCreate, Carbon $now): Collection
    {
        SurveyQuestion::insert($questionsToCreate);

        return $this->getCreatedQuestions($survey, $questionsToCreate);
    }

    private function getCreatedQuestions(Survey $survey, array $questionsToInsert): Collection
    {
        if (empty($questionsToInsert)) {
            return collect();
        }

        $pairs = collect($questionsToInsert)
            ->map(function ($question) {
                return "({$question['survey_page_id']}, {$question['question_number']})";
            })
            ->unique()
            ->implode(',');

        return SurveyQuestion::where('survey_id', $survey->id)
            ->whereRaw("(survey_page_id, question_number) IN ({$pairs})")
            ->get()
            ->keyBy(function ($question) {
                return "{$question->survey_page_id}_{$question->question_number}";
            });
    }
}
