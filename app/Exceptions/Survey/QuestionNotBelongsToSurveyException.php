<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class QuestionNotBelongsToSurveyException extends HttpException
{
    public function __construct(array $questionIds = [], int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        if (empty($message)) {
            $idsString = !empty($questionIds) ? implode(', ', $questionIds) : '';
            $message = __('exception.survey.question_not_belongs_to_survey', ['ids' => $idsString]);
        }

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
