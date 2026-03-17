<?php

namespace App\Exports\Rows;

use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyCompletion;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SurveyResultsRow
{
    private ?string $userFio;

    private Carbon $startDate;

    private ?Carbon $endDate;

    private ?string $department;

    private ?string $chief;

    /** @var array<int, string> question_id => formatted answer */
    private array $answersByQuestionId = [];

    public function __construct(SurveyCompletion $workerCompletedSurvey)
    {
        $endDate = $workerCompletedSurvey->survey->date_end;
        $this->userFio = $workerCompletedSurvey->worker->fio ?? null;
        $this->startDate = $workerCompletedSurvey->survey->created_at;
        $this->endDate = $endDate ? Carbon::parse($endDate) : null;
        $this->department = $workerCompletedSurvey->worker->department->name ?? null;
        $this->chief = $workerCompletedSurvey->worker->chief->worker->name ?? null;

        $answers = $workerCompletedSurvey
            ->survey
            ->answers()
            ->where('worker_id', $workerCompletedSurvey->worker->id)
            ->with(['answerVariants.variant'])
            ->get();

        $grouped = [];
        foreach ($answers as $answer) {
            $qid = $answer->question_id;
            if (!isset($grouped[$qid])) {
                $grouped[$qid] = [];
            }
            $grouped[$qid][] = $this->formatAnswer($answer);
        }

        foreach ($grouped as $qid => $parts) {
            $this->answersByQuestionId[$qid] = implode(', ', array_filter($parts, fn ($s) => $s !== ''));
        }
    }

    private function formatAnswer(SurveyAnswer $answer): string
    {
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
            $answerParts[] = (string) $answer->answer_int;
        }

        return !empty($answerParts) ? implode(', ', $answerParts) : '';
    }

    public function getUserFio(): ?string
    {
        return $this->userFio;
    }

    public function getStartDate(): float
    {
        return Date::dateTimeToExcel($this->startDate);
    }

    public function getEndDate(): ?float
    {
        return $this->endDate ? Date::dateTimeToExcel($this->endDate) : null;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function getChief(): ?string
    {
        return $this->chief;
    }

    public function getAnswerForQuestion(int $questionId): string
    {
        $text = $this->answersByQuestionId[$questionId] ?? '';

        return $text !== '' ? $text : '-';
    }
}
