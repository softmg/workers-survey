<?php

namespace App\Exports\Rows;

use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyAnswerVariants;
use App\Models\Survey\SurveyCompletion;
use Illuminate\Support\Collection;

class OffboardingSurveyResultsRow
{
    private ?string $userFio;

    private ?string $dismissalDate;
    private ?string $dismissalStatus;

    private ?array $answers;

    public function __construct(SurveyCompletion $workerCompletedSurvey)
    {
        $this->userFio = $workerCompletedSurvey->worker->fio ?? null;
        $this->dismissalDate = $workerCompletedSurvey->worker?->dismissal?->last_day?->format('d.m.Y');
        $this->dismissalStatus = $workerCompletedSurvey->completed ? 'Text' : 'Text text';
        $this->answers = $workerCompletedSurvey
            ->survey
            ->answers()
            ->where('worker_id', $workerCompletedSurvey->worker->id)
            ->get()
            ->mapToGroups(function (SurveyAnswer $answer) {
                /** @var Collection $res */
                $res = $answer->answerVariants->map(fn (SurveyAnswerVariants $sav) => $sav->variant->variant);
                $res->when($answer->answer_text !== null, fn (Collection $col) => $col->add($answer->answer_text));
                $res->when($answer->answer_int !== null, fn (Collection $col) => $col->add($answer->answer_int));

                return [$answer->question->id => $res->implode(fn (string $s) => $s, ', ')];
            })
            ->toArray();
    }

    public function getUserFio(): ?string
    {
        return $this->userFio;
    }

    public function getAnswers()
    {
        foreach ($this->answers as &$answer) {
            $answer = implode(', ', $answer);
        }

        return $this->answers;
    }

    public function getDismissalDate(): ?string
    {
        return $this->dismissalDate;
    }

    public function getStatus(): string
    {
        return $this->dismissalStatus;
    }
}
