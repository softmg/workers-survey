<?php

namespace App\Exports\Sheets;

use App\Enums\SurveyTypeEnum;
use App\Exports\Rows\OffboardingSurveyResultsRow;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OffboardingSurveyResultsExportSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithPreCalculateFormulas, WithStyles, WithTitle
{
    use Exportable;

    private Survey $survey;

    public function __construct(
        private readonly ?array $workers,
        private readonly ?string $dismissalDateFrom,
        private readonly ?string $dismissalDateTo,
        private readonly ?bool $completed,
    ) {

    }

    public function headings(): array
    {
        $headings = [
            'Text',
            'Date text',
            'Text',
        ];

        $this->survey = Survey::query()->withType(SurveyTypeEnum::Offboarding)->first();

        $questions = $this->survey->questions?->pluck('question')->toArray();

        return array_merge($headings, $questions);
    }

    /**
     * @var OffboardingSurveyResultsRow $row
     */
    public function map($row): array
    {
        $info = [
            $row->getUserFio(),
            $row->getDismissalDate(),
            $row->getStatus(),
        ];

        $answers = $row->getAnswers();

        return array_merge($info, $answers);
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function collection(): Collection
    {
        $query = $this->survey->completions();

        if ($this->workers !== null) {
            $query->whereIn('worker_id', $this->workers);
        }

        if ($this->dismissalDateFrom !== null) {
            $query->whereHas('worker.dismissal', function (Builder $query) {
                $query->where('last_day', '>=', $this->dismissalDateFrom);
            });
        }

        if ($this->dismissalDateTo !== null) {
            $query->whereHas('worker.dismissal', function (Builder $query) {
                $query->where('last_day', '<=', $this->dismissalDateTo);
            });
        }

        if ($this->workers !== null) {
            $query->whereIn('worker_id', $this->workers);
        }

        if ($this->completed !== null) {
            $query->where('completed', $this->completed);
        }

        return $query
            ->get()
            ->map(fn (SurveyCompletion $workerCompletedSurvey) => new OffboardingSurveyResultsRow($workerCompletedSurvey));
    }

    public function title(): string
    {
        return sprintf(
            'offline_time_report_from_123',
        );
    }

    public function styles(Worksheet $sheet): void
    {
        $headerEndCell = range('A', 'Z')[count($this->headings()) - 1];
        $sheet->getStyle("A1:{$headerEndCell}1")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_GRADIENT_LINEAR,
                'color' => ['rgb' => '91D2FF'],
            ],
        ]);
        $sheet->getStyle("A:{$headerEndCell}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
