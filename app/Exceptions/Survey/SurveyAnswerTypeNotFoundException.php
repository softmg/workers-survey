<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SurveyAnswerTypeNotFoundException extends HttpException
{
    public function __construct(int $statusCode = 404, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('exception.survey.answer_type_not_found');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
