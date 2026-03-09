<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PageNotBelongsToSurveyException extends HttpException
{
    public function __construct(array $pageIds = [], int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        if (empty($message)) {
            $idsString = !empty($pageIds) ? implode(', ', $pageIds) : '';
            $message = __('exception.survey.page_not_belongs_to_survey', ['ids' => $idsString]);
        }

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
