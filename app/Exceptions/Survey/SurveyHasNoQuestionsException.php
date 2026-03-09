<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SurveyHasNoQuestionsException extends HttpException
{
    public function __construct(int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('validation.should_be_more_than_one_question');

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
