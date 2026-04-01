<?php

namespace App\Validation;

use App\Models\Survey\Survey;
use Illuminate\Validation\Validator;

class SurveyValidator
{
    public function __invoke(Validator $validator)
    {
        $validatorData = $validator->getData();
        $survey = $validatorData['survey_id'];
        $survey = Survey::findOrFail($survey)->load('questions');

        $answers = $validatorData['answers'];

        $validator->errors()->addIf(
            $this->isContainsAllAnswers($answers, $survey),
            'answers',
            __('validation.survey_answers_count')
        );
    }

    private function isContainsAllAnswers(array $answers, Survey $survey): bool
    {
        $answeredQuestions = [];
        foreach ($answers as $answer) {
            $answeredQuestions[] = $answer['question_id'];
        }

        $answeredQuestions = array_unique($answeredQuestions);
        $surveyQuestions = $survey->questions->pluck('id')->toArray();

        return array_intersect($answeredQuestions, $surveyQuestions) !== $surveyQuestions;
    }
}
