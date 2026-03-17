<?php

namespace App\Exceptions\Survey;

use Symfony\Component\HttpKernel\Exception\HttpException;

class VariantsRequiredForAnswerTypeException extends HttpException
{
    public function __construct(int $questionNumber, string $answerTypeTitle, int $statusCode = 422, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $message ?: __('exception.survey.variants_required_for_answer_type', [
            'number' => $questionNumber,
            'answer_type' => $answerTypeTitle,
        ]);

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
