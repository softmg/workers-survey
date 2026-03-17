<?php

namespace App\Exports;

use App\Exports\Sheets\OffboardingSurveyResultsExportSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OffboardingSurveyResultsExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly ?array $workers,
        private readonly ?string $dismissalDateFrom,
        private readonly ?string $dismissalDateTo,
        private readonly ?bool $completed,
    ) {

    }

    public function sheets(): array
    {
        return [
            new OffboardingSurveyResultsExportSheet(
                $this->workers,
                $this->dismissalDateFrom,
                $this->dismissalDateTo,
                $this->completed,
            ),
        ];
    }
}
