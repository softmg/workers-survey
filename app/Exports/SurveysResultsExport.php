<?php

namespace App\Exports;

use App\Exceptions\Survey\InvalidSurveySheetNameException;
use App\Exports\Sheets\SurveysResultsExportSheet;
use App\Models\Survey\Survey;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SurveysResultsExport implements WithMultipleSheets
{
    use Exportable;
    private const MAX_SHEET_TITLE_LENGTH = 31;

    public function __construct(
        private readonly array $surveys,
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];
        $usedTitles = [];

        foreach ($this->surveys as $survey) {
            if (!$survey instanceof Survey) {
                continue;
            }

            $baseTitle = $this->resolveSheetTitle($survey->name);
            $sheetTitle = $this->makeUniqueSheetTitle($baseTitle, $usedTitles);
            $usedTitles[] = $sheetTitle;

            $sheets[] = new SurveysResultsExportSheet(
                $survey,
                $sheetTitle
            );
        }

        return $sheets;
    }

    private function resolveSheetTitle(?string $name): string
    {
        $title = trim((string) $name);
        $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

        if ($title === '') {
            throw new InvalidSurveySheetNameException();
        }

        if (mb_strlen($title) > self::MAX_SHEET_TITLE_LENGTH) {
            throw new InvalidSurveySheetNameException();
        }

        if (preg_match('/[\[\]\:\*\?\/\\\\]/u', $title) === 1) {
            throw new InvalidSurveySheetNameException();
        }

        return $title;
    }

    private function makeUniqueSheetTitle(string $baseTitle, array $usedTitles): string
    {
        if (!in_array($baseTitle, $usedTitles, true)) {
            return $baseTitle;
        }

        $counter = 2;
        do {
            $suffix = " ({$counter})";
            $availableLength = self::MAX_SHEET_TITLE_LENGTH - mb_strlen($suffix);
            $candidate = mb_substr($baseTitle, 0, $availableLength) . $suffix;
            $counter++;
        } while (in_array($candidate, $usedTitles, true));

        return $candidate;
    }
}
