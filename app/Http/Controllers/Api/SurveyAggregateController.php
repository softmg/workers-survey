<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\CreateSurveyAggregateRequest;
use App\Http\Requests\Survey\UpdateSurveyAggregateRequest;
use App\Http\Resources\Survey\SurveyAggregateResource;
use App\Models\Survey\Survey;
use App\Services\Survey\SurveyAggregateService;

class SurveyAggregateController extends Controller
{
    public function __construct(
        private SurveyAggregateService $surveyAggregateService
    ) {
    }

    /**
     * Text survey text
     */
    public function saveAggregate(CreateSurveyAggregateRequest $request): SurveyAggregateResource
    {
        return new SurveyAggregateResource($this->surveyAggregateService->save($request));
    }

    /**
     * Text survey text
     */
    public function updateAggregate(Survey $survey, UpdateSurveyAggregateRequest $request): SurveyAggregateResource
    {
        return new SurveyAggregateResource($this->surveyAggregateService->update($request, $survey));
    }

}
