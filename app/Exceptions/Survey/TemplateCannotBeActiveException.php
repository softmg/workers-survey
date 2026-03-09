<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TemplateCannotBeActiveException extends HttpException
{
    public function __construct(int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('validation.template_cannot_be_active');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
