<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class DifferentAnonymityTypesException extends HttpException
{
    public function __construct(int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('exception.different_anonymity_types');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
