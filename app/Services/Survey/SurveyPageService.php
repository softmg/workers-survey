<?php

namespace App\Services\Survey;

use App\Exceptions\Survey\PageNotBelongsToSurveyException;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyPageService
{
    public function createPages(Survey $survey, array $pagesData): array
    {
        if (empty($pagesData)) {
            return [];
        }

        $now = now();
        $pagesToInsert = [];

        foreach ($pagesData as $pageIndex => $pageData) {
            $pagesToInsert[] = [
                'survey_id' => $survey->id,
                'number' => $pageIndex + 1,
                'name' => $pageData['name'] ?? null,
                'description' => $pageData['description'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        SurveyPage::insert($pagesToInsert);

        $pageNumbers = collect($pagesToInsert)->pluck('number')->toArray();
        $createdPages = $this->getCreatedPages($survey, $pageNumbers);

        $pageMapping = [];
        foreach ($pagesData as $pageIndex => $pageData) {
            $pageNumber = $pageIndex + 1;
            $createdPage = $createdPages->get($pageNumber);
            if ($createdPage) {
                $pageMapping[$pageIndex] = $createdPage->id;
            }
        }

        return $pageMapping;
    }

    public function validatePageIds(array $requestPageIds, int $surveyId): void
    {
        if (empty($requestPageIds)) {
            return;
        }

        $invalidPageIds = SurveyPage::whereIn('id', $requestPageIds)
            ->where('survey_id', '!=', $surveyId)
            ->pluck('id')
            ->toArray();

        if (!empty($invalidPageIds)) {
            throw new PageNotBelongsToSurveyException($invalidPageIds);
        }
    }

    public function syncPages(Survey $survey, array $validated, Collection $existingPages): array
    {
        if (empty($validated['survey']['survey_pages'])) {
            if ($existingPages->isNotEmpty()) {
                SurveyPage::whereIn('id', $existingPages->keys())->delete();
            }
            return [];
        }

        $requestPageIds = [];
        foreach ($validated['survey']['survey_pages'] as $pageData) {
            if (!empty($pageData['id'])) {
                $requestPageIds[] = $pageData['id'];
            }
        }

        $this->validatePageIds($requestPageIds, $survey->id);

        $this->deleteRemovedPages($existingPages, $requestPageIds);

        $pagesToUpdate = [];
        $pagesToCreate = [];
        $pageMapping = [];

        foreach ($validated['survey']['survey_pages'] as $pageIndex => $pageData) {
            if (!empty($pageData['id']) && $existingPages->has($pageData['id'])) {
                $page = $existingPages->get($pageData['id']);
                $pagesToUpdate[] = [
                    'id' => $page->id,
                    'number' => $pageIndex + 1,
                    'name' => $pageData['name'] ?? null,
                    'description' => $pageData['description'] ?? null,
                ];
                $pageMapping[$pageIndex] = $page->id;
            } else {
                $pagesToCreate[] = [
                    'pageIndex' => $pageIndex,
                    'number' => $pageIndex + 1,
                    'name' => $pageData['name'] ?? null,
                    'description' => $pageData['description'] ?? null,
                ];
            }
        }

        if (!empty($pagesToUpdate)) {
            $this->updateExistingPages($pagesToUpdate);
        }

        if (!empty($pagesToCreate)) {
            $newPageMapping = $this->createNewPages($survey, $pagesToCreate);
            $pageMapping = [...$pageMapping, ...$newPageMapping];
        }

        return $pageMapping;
    }

    private function deleteRemovedPages(Collection $existingPages, array $requestPageIds): void
    {
        $diffToDBPages = $existingPages->keys()->diff($requestPageIds);
        if ($diffToDBPages->isNotEmpty()) {
            SurveyPage::whereIn('id', $diffToDBPages)->delete();
        }
    }

    private function updateExistingPages(array $pagesToUpdate): void
    {
        $ids = collect($pagesToUpdate)->pluck('id')->toArray();
        $pdo = DB::connection()->getPdo();

        $numberCases = collect($pagesToUpdate)->map(function ($page) {
            return "WHEN {$page['id']} THEN {$page['number']}";
        })->implode(' ');

        $nameCases = collect($pagesToUpdate)->map(function ($page) use ($pdo) {
            $name = $page['name'] !== null ? $pdo->quote($page['name']) : 'NULL';
            return "WHEN {$page['id']} THEN {$name}";
        })->implode(' ');

        $descriptionCases = collect($pagesToUpdate)->map(function ($page) use ($pdo) {
            $description = $page['description'] !== null ? $pdo->quote($page['description']) : 'NULL';
            return "WHEN {$page['id']} THEN {$description}";
        })->implode(' ');

        DB::table('survey_pages')
            ->whereIn('id', $ids)
            ->update([
                'number' => DB::raw("CASE id {$numberCases} END"),
                'name' => DB::raw("CASE id {$nameCases} END"),
                'description' => DB::raw("CASE id {$descriptionCases} END"),
            ]);
    }

    private function createNewPages(Survey $survey, array $pagesToCreate): array
    {
        $now = now();
        $pagesToInsert = collect($pagesToCreate)->map(function ($pageData) use ($survey, $now) {
            return [
                'survey_id' => $survey->id,
                'number' => $pageData['number'],
                'name' => $pageData['name'],
                'description' => $pageData['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        SurveyPage::insert($pagesToInsert);

        $pageNumbers = collect($pagesToCreate)->pluck('number')->toArray();
        $createdPages = $this->getCreatedPages($survey, $pageNumbers);

        $pageMapping = [];
        foreach ($pagesToCreate as $pageData) {
            $createdPage = $createdPages->get($pageData['number']);
            if ($createdPage) {
                $pageMapping[$pageData['pageIndex']] = $createdPage->id;
            }
        }

        return $pageMapping;
    }

    private function getCreatedPages(Survey $survey, array $pageNumbers): Collection
    {
        return SurveyPage::where('survey_id', $survey->id)
            ->whereIn('number', $pageNumbers)
            ->orderBy('number')
            ->get()
            ->keyBy('number');
    }

    public function createDefaultPage(Survey $survey): SurveyPage
    {
        return SurveyPage::create([
            'survey_id' => $survey->id,
            'number' => 1,
            'name' => null,
            'description' => null,
        ]);
    }
}
