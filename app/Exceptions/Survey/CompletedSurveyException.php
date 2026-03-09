<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CompletedSurveyException extends HttpException
{
    public function __construct(int $statusCode = 403, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('exception.current_survey_is_completed');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
