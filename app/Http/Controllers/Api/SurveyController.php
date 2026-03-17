<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\StoreSurveyAnswersRequest;
use App\Http\Requests\Survey\SurveyBurnoutAnswerRequest;
use App\Http\Requests\Survey\SurveyIndexRequest;
use App\Http\Requests\Survey\SurveyRequest;
use App\Http\Requests\Survey\SurveyTemplatesFilterRequest;
use App\Http\Requests\Survey\WorkerCompletedOffboardingSurveyRequest;
use App\Http\Requests\Survey\WorkerCompletedSurveyRequest;
use App\Http\Resources\Survey\all\SurveyAllResource;
use App\Http\Resources\Survey\all\SurveyAllTemplatesResource;
use App\Http\Resources\Survey\answers\SurveyBurnoutAnswersResource;
use App\Http\Resources\Survey\answers\SurveyQuestionStatisticsResource;
use App\Http\Resources\Survey\index\SurveyIndexResource;
use App\Http\Resources\Survey\information\SurveyInformationResource;
use App\Http\Resources\Survey\OffboardingSurveyFilterResource;
use App\Http\Resources\Survey\question\SurveyResource;
use App\Http\Resources\Survey\question\SurveyPagesResource;
use App\Http\Resources\Survey\save\WorkerCompletedSurveyResource;
use App\Http\Resources\Survey\SurveyFilterResource;
use App\Http\Resources\Survey\SurveyWorkerFilterResource;
use App\Http\Resources\Survey\SurveyAnswerTypeResource;
use App\Http\Resources\Survey\workers\OffboardingSurveyWorkersResource;
use App\Http\Resources\Survey\workers\SurveyWorkersResource;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyAnswerType;
use App\Services\Survey\SurveyService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Services\Survey\SurveyBurnoutService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class SurveyController extends Controller
{
    public function __construct(
        private SurveyService $surveyService,
        private SurveyBurnoutService $surveyBurnoutService
    ) {
    }

    /**
     * Text text surveys text text text text
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<SurveyIndexResource>>
     */
    public function index(SurveyIndexRequest $request): JsonResource
    {
        return SurveyIndexResource::collection($this->surveyService->getWorkerSurveysWithPaginate($request, Auth::user()));
    }

    /**
     * Text text surveys
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<SurveyIndexResource>>
     */
    public function getAll(SurveyIndexRequest $request): JsonResource
    {
        return SurveyIndexResource::collection($this->surveyService->getWorkerSurveysWithPaginate($request, Auth::user()));
    }

    /**
     * Text questions
     */
    public function questions(Survey $survey): JsonResource
    {
        return SurveyResource::make($survey->load('questions'));
    }

    /**
     * Text text survey text text
     */
    public function aggregate(Survey $survey): JsonResource
    {
        return SurveyPagesResource::make($this->surveyService->getSurveyPagesWithQuestions($survey));
    }

    /**
     * Text answers text questions
     */
    public function saveAnswers(StoreSurveyAnswersRequest $request, Survey $survey): JsonResource
    {
        return WorkerCompletedSurveyResource::make($this->surveyService->save($request, $survey));
    }

    /**
     * Text text text surveys
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<SurveyAllResource>>
     */
    public function all(SurveyRequest $request): JsonResource
    {
        return SurveyAllResource::collection($this->surveyService->getAllSurveysWithPaginate($request));
    }

    /**
     * Text text text text surveys
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<SurveyAllResource>>
     */
    public function getTemplates(SurveyTemplatesFilterRequest $request): JsonResource
    {
        return SurveyAllTemplatesResource::collection($this->surveyService->getSurveysWithTemplatesFilter($request));
    }

    /**
     * Text text text text
     */
    public function information(Survey $survey): JsonResource
    {
        return SurveyInformationResource::make($survey->loadCount('questions'));
    }

    /**
     * Text employees text survey
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<SurveyWorkersResource>>
     */
    public function workers(Survey $survey, WorkerCompletedSurveyRequest $request): AnonymousResourceCollection
    {
        return SurveyWorkersResource::collection($this->surveyService->getSurveyWorkers($request, $survey));
    }

    /**
     * Text answers text survey
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<SurveyQuestionStatisticsResource>>
     */
    public function answers(Survey $survey, WorkerCompletedSurveyRequest $request): AnonymousResourceCollection
    {
        return SurveyQuestionStatisticsResource::collection($this->surveyService->getSurveyAnswers($request, $survey));
    }

    /**
     * Answers text question
     */
    public function questionAnswers(SurveyQuestion $question): JsonResource
    {
        return SurveyQuestionStatisticsResource::make($question->load('answerType'));
    }

    /**
     * Text text text text
     */
    public function filters(): JsonResource
    {
        return SurveyFilterResource::make($this->surveyService->getFilters());
    }

    /**
     * Text text text text employees
     */
    public function workerFilters(Survey $survey): JsonResource
    {
        return SurveyWorkerFilterResource::make($this->surveyService->getWorkerFilters($survey));
    }

    /**
     * Text employees text text survey
    */
    public function offboardingWorkers(WorkerCompletedOffboardingSurveyRequest $request): JsonResource
    {
        return OffboardingSurveyWorkersResource::collection($this->surveyService->getOffboardingSurveyWorkers($request));
    }

    /**
     * Text answers text text survey
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<SurveyQuestionStatisticsResource>>
     */
    public function offboardingAnswers(WorkerCompletedOffboardingSurveyRequest $request): AnonymousResourceCollection
    {
        return SurveyQuestionStatisticsResource::collection($this->surveyService->getOffboardingSurveyAnswers($request));
    }

    /**
     * Text text text text text
     */
    public function offboardingFilters(): JsonResource
    {
        return OffboardingSurveyFilterResource::make($this->surveyService->getOffboardingFilters());
    }

    /**
     * Text text types answers
     *
     * @return AnonymousResourceCollection<SurveyAnswerTypeResource>
     */
    public function answerTypes(): AnonymousResourceCollection
    {
        return SurveyAnswerTypeResource::collection(SurveyAnswerType::all());
    }

    /**
     * Text text surveys text text
     *
     * @param SurveyBurnoutAnswerRequest $burnoutAnswerRequest
     * @return JsonResource
     */
    public function burnoutResult(SurveyBurnoutAnswerRequest $burnoutAnswerRequest): SurveyBurnoutAnswersResource
    {
        return SurveyBurnoutAnswersResource::make($this->surveyBurnoutService->getBurnoutAnswers($burnoutAnswerRequest));
    }
}
