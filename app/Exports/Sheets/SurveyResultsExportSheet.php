<?php

namespace App\Exports\Sheets;

use App\Exports\Rows\SurveyResultsRow;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SurveyResultsExportSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithPreCalculateFormulas, WithStyles, WithTitle
{
    use Exportable;

    private bool $isVisible;

    private int $baseColumnsCount;

    /** @var array<int, array{page: ?\App\Models\Survey\SurveyPage, name: string, questions: Collection}> */
    private array $pagesData = [];

    private int $questionsCount = 0;

    public function __construct(
        private readonly Survey $survey,
        private readonly ?array $workers,
        private readonly ?array $departments,
        private readonly ?array $chiefs,
        private readonly ?bool $completed,
    ) {
        $this->isVisible = Auth::check() ? Gate::allows('viewWorkers', $this->survey) : false;
        $this->baseColumnsCount = $this->isVisible ? 5 : 2;
        $this->survey->loadMissing([
            'pages' => fn ($q) => $q->orderBy('number'),
            'pages.questions' => fn ($q) => $q->orderBy('question_number'),
        ]);
        $this->pagesData = $this->buildPagesData();
        foreach ($this->pagesData as $pageBlock) {
            $this->questionsCount += $pageBlock['questions']->count();
        }
    }

    /**
     * @return array<int, array{page: ?\App\Models\Survey\SurveyPage, name: string, questions: Collection}>
     */
    private function buildPagesData(): array
    {
        $pages = $this->survey->pages->sortBy('number');
        $result = [];

        foreach ($pages as $page) {
            $questions = $page->questions->sortBy('question_number')->values();
            if ($questions->isEmpty()) {
                continue;
            }
            $result[] = [
                'page' => $page,
                'name' => $this->formatPageName($page->name, $page->number),
                'questions' => $questions,
            ];
        }

        if ($result !== []) {
            return $result;
        }

        $questions = $this->survey->questions()->orderBy('question_number')->get();
        if ($questions->isEmpty()) {
            return [];
        }

        return [[
            'page' => null,
            'name' => 'Questions',
            'questions' => $questions,
        ]];
    }

    private function formatPageName(?string $pageName, int $pageNumber): string
    {
        $baseName = trim((string)$pageName);
        if ($baseName === '') {
            return sprintf('Text %d', $pageNumber);
        }

        return sprintf('%s %d', $baseName, $pageNumber);
    }

    public function headings(): array
    {
        return [];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    /**
     * @var SurveyResultsRow $row
     */
    public function map($row): array
    {
        $info = [
            $row->getStartDate(),
            $row->getEndDate() ?? '-',
        ];

        if ($this->isVisible) {
            $info = array_merge([
                $row->getUserFio(),
            ], $info, [
                $row->getDepartment(),
                $row->getChief(),
            ]);
        }

        $answerCells = [];
        foreach ($this->pagesData as $pageBlock) {
            foreach ($pageBlock['questions'] as $question) {
                $answerCells[] = $row->getAnswerForQuestion((int) $question->id);
            }
        }

        return array_merge($info, $answerCells);
    }

    public function columnFormats(): array
    {
        if ($this->isVisible) {
            return [
                'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
                'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            ];
        }

        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function collection(): Collection
    {
        $query = $this->survey->completions();

        if ($this->workers !== null) {
            $query->whereIn('worker_id', $this->workers);
        }

        if ($this->departments !== null) {
            $query->whereHas('worker', function ($query) {
                $query->whereIn('department_id', $this->departments);
            });
        }

        if ($this->chiefs !== null) {
            $query->whereHas('worker', function ($query) {
                $query->whereIn('chief_id', $this->chiefs);
            });
        }

        if ($this->completed !== null) {
            $query->where('completed', $this->completed);
        }

        return $query
            ->get()
            ->map(fn (SurveyCompletion $workerCompletedSurvey) => new SurveyResultsRow($workerCompletedSurvey));
    }

    public function title(): string
    {
        return sprintf(
            'offline_time_report_from_123',
        );
    }

    public function styles(Worksheet $sheet): void
    {
        $totalColumns = $this->baseColumnsCount + $this->questionsCount;
        if ($totalColumns === 0) {
            return;
        }

        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);
        $sheet->getStyle("A:{$lastColumn}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->buildHeaders($sheet);
                $this->applyHeaderStyles($sheet);
            },
        ];
    }

    private function buildHeaders(Worksheet $sheet): void
    {
        if ($this->isVisible) {
            $sheet->setCellValue('A1', 'Text');
            $sheet->setCellValue('B1', 'Date text survey');
            $sheet->setCellValue('C1', 'Date text survey');
            $sheet->setCellValue('D1', 'Department');
            $sheet->setCellValue('E1', 'Text');
            $sheet->mergeCells('A1:A2');
            $sheet->mergeCells('B1:B2');
            $sheet->mergeCells('C1:C2');
            $sheet->mergeCells('D1:D2');
            $sheet->mergeCells('E1:E2');
        } else {
            $sheet->setCellValue('A1', 'Date text survey');
            $sheet->setCellValue('B1', 'Date text survey');
            $sheet->mergeCells('A1:A2');
            $sheet->mergeCells('B1:B2');
        }

        $column = $this->baseColumnsCount;
        foreach ($this->pagesData as $pageBlock) {
            $questions = $pageBlock['questions'];
            $count = $questions->count();
            if ($count === 0) {
                continue;
            }

            $pageStartCol = Coordinate::stringFromColumnIndex($column + 1);
            $pageEndCol = Coordinate::stringFromColumnIndex($column + $count);

            $sheet->setCellValue($pageStartCol . '1', $pageBlock['name']);
            if ($count > 1) {
                $sheet->mergeCells($pageStartCol . '1:' . $pageEndCol . '1');
            }

            foreach ($questions as $question) {
                $questionCol = Coordinate::stringFromColumnIndex($column + 1);
                $sheet->setCellValue($questionCol . '2', $question->question);
                $column++;
            }
        }
    }

    private function applyHeaderStyles(Worksheet $sheet): void
    {
        $totalColumns = $this->baseColumnsCount + $this->questionsCount;
        if ($totalColumns === 0) {
            return;
        }

        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);
        $sheet->getStyle("A1:{$lastColumn}2")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_GRADIENT_LINEAR,
                'color' => ['rgb' => '91D2FF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(22);
    }
}
