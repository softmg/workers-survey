<?php

use App\Enums\SurveyAnswerTypeEnum;
use App\Enums\SurveyTypeEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var Collection $questions */
        $questions = $this->findImpulseQuestion();
        if ($questions->isEmpty()) {
            throw new Exception('Surveys question not found');
        }

        $answerType = $this->findNewImpulseAnswerType();
        if (!$answerType) {
            $answerType = $this->createAnswerTypeForImpulse();
        }

        $questions->map(fn (SurveyQuestion $sq) => $sq->update(['answer_type_id' => $answerType->id]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $questions = $this->findImpulseQuestion();

        if ($questions->isEmpty()) {
            throw new Exception('Surveys question not found');
        }

        $answerType = $this->findOldImpulseAnswerType();

        $questions->map(fn (SurveyQuestion $sq) => $sq->update(['answer_type_id' => $answerType->id]));
    }

    public function findImpulseQuestion(): Collection
    {
        $surveys = Survey::query()
            ->whereHas('type', fn ($q) => $q->where('code', SurveyTypeEnum::Impulse->value))
            ->get();

        if ($surveys->isEmpty()) {
            throw new Exception('Surveys not found');
        }

        return SurveyQuestion::query()
            ->where('question_number', 1)
            ->whereIn('survey_id', $surveys->pluck('id'))
            ->get();
    }


    public function createAnswerTypeForImpulse(): SurveyAnswerType
    {
        return SurveyAnswerType::create([
            'title' => 'Text text, text 0-10, Impulse',
            'base_type' => SurveyAnswerTypeEnum::Integer->value,
            'custom' => 0,
            'multiple' => 0,
            'limited' => 1,
            'max' => 10,
            'min' => 0,
        ]);
    }

    public function findOldImpulseAnswerType(): ?SurveyAnswerType
    {
        return SurveyAnswerType::query()
            ->where('min', 1)
            ->where('max', 10)
            ->where('limited', 1)
            ->first();
    }


    public function findNewImpulseAnswerType(): ?SurveyAnswerType
    {
        return SurveyAnswerType::query()
            ->where('min', 0)
            ->where('max', 10)
            ->where('limited', 1)
            ->first();
    }
};
