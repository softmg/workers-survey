<?php

namespace App\Exports\Sheets;

use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\Survey;
use App\Models\User\User;
use App\Models\Worker\Worker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SurveysResultsExportSheet implements FromCollection, WithMapping, WithEvents, WithStyles, ShouldAutoSize, WithCustomStartCell, WithHeadings, WithTitle
{
    use Exportable;

    private array $headerStructure = [];
    private int $baseColumnsCount = 3;
    private array $assignedWorkerIds = [];
    private array $answersByWorkerAndQuestion = [];
    private bool $isVisible;

    public function __construct(
        private readonly Survey $survey,
        private readonly string $sheetTitle,
    ) {
        $this->isVisible = Auth::check()
            ? Gate::allows('viewWorkers', $this->survey)
            : false;

        $this->baseColumnsCount = $this->isVisible ? 3 : 0;
        $this->buildHeaderStructure();
    }

    private function buildHeaderStructure(): void
    {
        // Text text text text text/questions text text text survey
        $currentColumn = $this->baseColumnsCount;
        $pagesData = [];

        $pages = $this->survey->pages->sortBy('number');

        foreach ($pages as $page) {
            $questions = $page->questions->sortBy('question_number');
            $questionsCount = $questions->count();

            $pagesData[] = [
                'page' => $page,
                'start_column' => $currentColumn,
                'questions_count' => $questionsCount,
                'questions' => $questions,
            ];

            $currentColumn += $questionsCount;
        }

        $this->headerStructure = $pagesData;
    }

    public function headings(): array
    {
        return [];
    }

    public function collection(): Collection
    {
        $allQuestionIds = [];
        foreach ($this->headerStructure as $pageData) {
            foreach ($pageData['questions'] as $question) {
                $allQuestionIds[] = $question->id;
            }
        }

        $workerIds = $this->survey->workerCompletedSurveyCompletions()
            ->distinct()
            ->pluck('worker_id')
            ->toArray();

        $completions = $this->survey->workerCompletedSurveyCompletions()
            ->whereIn('worker_id', $workerIds)
            ->get();

        $this->assignedWorkerIds = $completions
            ->pluck('worker_id')
            ->flip()
            ->toArray();

        $answers = SurveyAnswer::whereIn('worker_id', $workerIds)
            ->whereIn('question_id', $allQuestionIds)
            ->with(['answerVariants.variant'])
            ->get();

        // Text text answers text text text [worker_id][question_id] text text text text text text
        $this->answersByWorkerAndQuestion = [];
        foreach ($answers->groupBy('worker_id') as $workerId => $workerAnswers) {
            $this->answersByWorkerAndQuestion[$workerId] = [];
            foreach ($workerAnswers as $answer) {
                $this->answersByWorkerAndQuestion[$workerId][$answer->question_id] = $answer;
            }
        }

        return Worker::withoutGlobalScopes()
            ->withTrashed()
            ->whereIn('id', $workerIds)
            ->with(['department', 'chief.worker'])
            ->get();
    }

    private function formatChiefFio(?User $chief): string
    {
        if (!$chief || !$chief->worker) {
            return '';
        }

        $worker = $chief->worker;
        $parts = [];

        if ($worker->secondName) {
            $parts[] = $worker->secondName;
        }

        if ($worker->firstName) {
            $parts[] = mb_substr($worker->firstName, 0, 1) . '.';
        }

        if ($worker->middleName) {
            $parts[] = mb_substr($worker->middleName, 0, 1) . '.';
        }

        return implode(' ', $parts);
    }

    private function formatPageName(?string $pageName, int $pageNumber): string
    {
        $baseName = trim((string)$pageName);
        if ($baseName === '') {
            return sprintf('Text %d', $pageNumber);
        }

        return sprintf('%s %d', $baseName, $pageNumber);
    }

    public function map($worker): array
    {
        $row = [];

        if ($this->isVisible) {
            $row = [
                $worker->fio ?? '',
                $worker->department?->name ?? '',
                $this->formatChiefFio($worker->chief),
            ];
        }

        // Text, text text survey text, text text answers text text text text
        $isWorkerAssignedToSurvey = array_key_exists($worker->id, $this->assignedWorkerIds);

        foreach ($this->headerStructure as $pageData) {
            foreach ($pageData['questions'] as $question) {
                $answer = $isWorkerAssignedToSurvey
                    ? (($this->answersByWorkerAndQuestion[$worker->id] ?? [])[$question->id] ?? null)
                    : null;

                if (!$answer) {
                    $row[] = '-';
                } else {
                    $row[] = $this->formatAnswer($answer);
                }
            }
        }

        return $row;
    }

    public function startCell(): string
    {
        // Text text text 3 text, text text first 2 text text text text
        return 'A3';
    }

    private function formatAnswer(SurveyAnswer $answer): string
    {
        // Text text types answers (variants text, text, text) text text text text text
        $answerParts = [];

        if ($answer->answerVariants && $answer->answerVariants->isNotEmpty()) {
            foreach ($answer->answerVariants as $answerVariant) {
                if ($answerVariant->variant && $answerVariant->variant->variant) {
                    $answerParts[] = $answerVariant->variant->variant;
                }
            }
        }

        if ($answer->answer_text !== null && $answer->answer_text !== '') {
            $answerParts[] = $answer->answer_text;
        }

        if ($answer->answer_int !== null) {
            $answerParts[] = (string)$answer->answer_int;
        }

        return !empty($answerParts) ? implode(', ', $answerParts) : '-';
    }

    public function registerEvents(): array
    {
        Sheet::macro('mergeCells', function (Sheet $sheet, string $cellRange) {
            $sheet->getDelegate()->mergeCells($cellRange);
        });

        Sheet::macro('setCellValue', function (Sheet $sheet, string $cell, $value) {
            $sheet->getDelegate()->setCellValue($cell, $value);
        });

        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                if ($highestRow >= 2) {
                    $this->buildHeaders($sheet);
                    $this->applyStyles($sheet);
                }
            },
        ];
    }

    private function buildHeaders(Worksheet $sheet): void
    {
        if ($this->isVisible) {
            $sheet->setCellValue('A1', 'Text');
            $sheet->setCellValue('B1', 'Department');
            $sheet->setCellValue('C1', 'Text');

            $sheet->mergeCells('A1:A2');
            $sheet->mergeCells('B1:B2');
            $sheet->mergeCells('C1:C2');
        }

        foreach ($this->headerStructure as $pageData) {
            if ($pageData['questions_count'] > 0) {
                $page = $pageData['page'];
                $pageStartCol = Coordinate::stringFromColumnIndex($pageData['start_column'] + 1);
                $pageEndCol = Coordinate::stringFromColumnIndex($pageData['start_column'] + $pageData['questions_count']);

                $pageName = $this->formatPageName($page->name, $page->number);
                $sheet->setCellValue($pageStartCol . '1', $pageName);
                if ($pageData['questions_count'] > 1) {
                    $sheet->mergeCells($pageStartCol . '1:' . $pageEndCol . '1');
                }

                $questionColumn = $pageData['start_column'];
                foreach ($pageData['questions'] as $question) {
                    $questionCol = Coordinate::stringFromColumnIndex($questionColumn + 1);
                    $sheet->setCellValue($questionCol . '2', $question->question);
                    $questionColumn++;
                }
            }
        }
    }

    private function applyStyles(Worksheet $sheet): void
    {
        $totalColumns = $this->baseColumnsCount;
        foreach ($this->headerStructure as $pageData) {
            $totalColumns += $pageData['questions_count'];
        }

        if ($totalColumns === 0) {
            return;
        }

        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);
        $startColumn = $this->isVisible ? 'A' : Coordinate::stringFromColumnIndex($this->baseColumnsCount + 1);

        $headerRange = $startColumn . '1:' . $lastColumn . '2';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '91D2FF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(20);
    }

    public function styles(Worksheet $sheet): void
    {
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
