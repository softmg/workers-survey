<?php

namespace App\Services\Survey;

use App\Enums\SurveyAnswerTypeEnum;
use App\Enums\SurveyTypeEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyType;
use Closure;
use Illuminate\Support\Facades\DB;

class ImpulseTemplateService
{
    public const FIRST_QUESTION_TEXT = 'Text text, text text text text text text text text text text?';
    public const SECOND_QUESTION_TEXT = 'Text text text text text?';

    public function createImpulseTemplate(): void
    {
        try {
            DB::beginTransaction();
            $type = $this->createImpulseType();
            $survey = new Survey();
            $survey->name = 'Impulse (Template)';
            $survey->description = 'Text text text text - text survey, text text text text, text text text. Text text ~1 text';
            $survey->is_template = 1;
            $survey->date_end = null;
            $survey->type()->associate($type);

            $survey->save();

            $this->createImpulseQuestions($survey);
            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }
    }

    private function createImpulseType(): SurveyType
    {
        $type = new SurveyType();
        $type->code = SurveyTypeEnum::Impulse;
        $type->name = 'Impulse';
        $type->save();

        return $type;
    }

    private function createImpulseQuestions(Survey $survey): void
    {
        $sq = new SurveyQuestion();
        $sq->survey_id = $survey->id;
        $answerType = $this->getAnswerType(fn ($q) => $q->where('base_type', 'integer')->where('min', 1)->where('max', 10));
        if (!$answerType) {
            $answerType = SurveyAnswerType::create([
                'base_type' => SurveyAnswerTypeEnum::Integer->value,
                'min' => 1,
                'max' => 10,
                'limited' => 1,
                'custom' => 0,
                'multiple' => 0,
                'title' => 'Impulse type answer №1.'
            ]);
        }
        $sq->answer_type_id = $answerType->id;
        $sq->question = self::FIRST_QUESTION_TEXT;
        $sq->question_number = 1;
        $sq->save();

        $sq = new SurveyQuestion();
        $sq->survey_id = $survey->id;
        $answerType = $this->getAnswerType(fn ($q) => $q->where('base_type', 'checkbox')->where('custom', 1)->where('multiple', 1));
        if (!$answerType) {
            $answerType = SurveyAnswerType::create([
                'base_type' => SurveyAnswerTypeEnum::Checkbox->value,
                'multiple' => 1,
                'custom' => 1,
                'title' => 'Impulse type answer №2.'
            ]);
        }
        $sq->answer_type_id = $answerType->id;
        $sq->question = self::SECOND_QUESTION_TEXT;
        $sq->question_number = 2;
        $sq->save();

        $sq->variants()->createMany([
            ['question_id' => $sq->id, 'variant' => 'Text text text'],
            ['question_id' => $sq->id, 'variant' => 'Text'],
            ['question_id' => $sq->id, 'variant' => 'Text text'],
            ['question_id' => $sq->id, 'variant' => 'Text text'],
            ['question_id' => $sq->id, 'variant' => 'Text text'],
            ['question_id' => $sq->id, 'variant' => 'Text text'],
            ['question_id' => $sq->id, 'variant' => 'Text text'],
        ]);
    }

    private function getAnswerType(Closure $where): ?SurveyAnswerType
    {
        return SurveyAnswerType::query()->where($where)->first();
    }


    public function deleteImpulseTemplate(): void
    {
        Survey::query()
            ->whereHas('type', fn ($q) => $q->where('code', SurveyTypeEnum::Impulse))
            ->where('is_template', 1)
            ->delete();

        SurveyType::query()
            ->where('code', SurveyTypeEnum::Impulse)
            ->delete();
    }
}
