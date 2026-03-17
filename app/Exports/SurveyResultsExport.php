<?php

namespace App\Exports;

use App\Exports\Sheets\SurveyResultsExportSheet;
use App\Models\Survey\Survey;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SurveyResultsExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly Survey $survey,
        private readonly ?array $workers,
        private readonly ?array $departments,
        private readonly ?array $chiefs,
        private readonly ?bool $completed,
    ) {

    }

    public function sheets(): array
    {
        return [
            new SurveyResultsExportSheet(
                $this->survey,
                $this->workers,
                $this->departments,
                $this->chiefs,
                $this->completed,
            ),
        ];
    }
}
