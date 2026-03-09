<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SurveyNotAssignedToWorkerException extends HttpException
{
    public function __construct(int $statusCode = 403, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('exception.survey_not_assigned_to_worker');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
