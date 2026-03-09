<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PageNotFoundForQuestionsException extends HttpException
{
    public function __construct(int $pageIndex, int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        if (empty($message)) {
            $message = __('exception.survey.page_not_found_for_questions', ['page_index' => $pageIndex]);
        }

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
