<?php

namespace App\Exceptions\Survey;

use Exception;
use Illuminate\Http\JsonResponse;

class IncompleteSurveyAnswersException extends Exception
{
    /**
     * @param int $expected  Text text text answers
     * @param int $received  Text text
     */
    public function __construct(int $expected, int $received)
    {
        parent::__construct(
            "Text text text text {$expected} questions, text text {$received}.",
            422
        );
    }


    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->getCode());
    }

}
