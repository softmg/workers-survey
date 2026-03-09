<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ExportSurveyWithoutCompletedException extends HttpException
{
    public function __construct(int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('exception.survey.cannot_export');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
