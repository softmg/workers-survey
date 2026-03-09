<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidSurveyDateException extends HttpException
{
    public function __construct(int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('exception.invalid_survey_date');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
