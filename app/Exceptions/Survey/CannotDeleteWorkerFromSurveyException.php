<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CannotDeleteWorkerFromSurveyException extends HttpException
{
    public function __construct(int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('validation.cannot_delete_worker_from_survey');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
