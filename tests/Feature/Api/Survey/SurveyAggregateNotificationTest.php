<?php

namespace Feature\Api\Survey;

use App\Enums\SurveyAnswerTypeEnum;
use App\Enums\SurveyAnonymityEnum;
use App\Enums\SurveyStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\WorkerStatusEnum;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswerType;
use App\Models\Survey\SurveyCompletion;
use App\Models\Survey\SurveyType;
use App\Models\User\User;
use App\Models\Worker\Worker;
use App\Models\Worker\WorkerStatus;
use App\Services\NotifyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Tests\Feature\FeatureTestCase;

class SurveyAggregateNotificationTest extends FeatureTestCase
{
    /**
     * Text type answer text base_type
     */
    private function getAnswerTypeByBaseType(SurveyAnswerTypeEnum $baseType, bool $multiple = false, bool $limited = false): Builder|Model
    {
        return SurveyAnswerType::query()
            ->where('base_type', $baseType)
            ->where('multiple', $multiple)
            ->where('limited', $limited)
            ->firstOrFail();
    }

    /**
     * Text text text text text
     */
    private function createBaseData(): array
    {
        $surveyType = SurveyType::firstOrFail();
        $workingStatus = WorkerStatus::where('code', WorkerStatusEnum::Working->value)->firstOrFail();
        $probationStatus = WorkerStatus::where('code', WorkerStatusEnum::ProbationPeriod->value)->firstOrFail();

        $user1 = User::factory()->create();
        $worker1 = Worker::where('user_id', $user1->id)->first();
        $worker1->statusId = $workingStatus->id;
        $worker1->save();

        $user2 = User::factory()->create();
        $worker2 = Worker::where('user_id', $user2->id)->first();
        $worker2->statusId = $probationStatus->id;
        $worker2->save();

        return [
            'survey_type_id' => $surveyType->id,
            'users_id' => [$user1->id, $user2->id],
            'departments_id' => [],
            'workers' => [$worker1, $worker2],
            'users' => [$user1, $user2],
        ];
    }

    /**
     * Text survey text text workers
     */
    private function createSurveyWithWorkers(array $workerIds, SurveyStatusEnum $status = SurveyStatusEnum::Created): Survey
    {
        $baseData = $this->createBaseData();
        $survey = Survey::factory()->create([
            'status' => $status,
            'survey_type_id' => $baseData['survey_type_id'],
        ]);

        foreach ($workerIds as $workerId) {
            SurveyCompletion::factory()->create([
                'survey_id' => $survey->id,
                'worker_id' => $workerId,
                'completed' => false,
            ]);
        }

        return $survey;
    }
    // todo: Text/text text text text text Notifier.
    //    /**
    //     * Text: notification text workers text text text text Active
    //     */
    //    public function test_notifies_new_workers_when_status_changes_to_active(): void
    //    {
    //        $baseData = $this->createBaseData();
    //
    //        $existingWorker = $baseData['workers'][0];
    //        $survey = $this->createSurveyWithWorkers([$existingWorker->id], SurveyStatusEnum::Created);
    //
    //        $newUser = User::factory()->create();
    //        $newWorker = Worker::where('user_id', $newUser->id)->first();
    //        $newWorker->statusId = WorkerStatus::where('code', WorkerStatusEnum::Working->value)->firstOrFail()->id;
    //        $newWorker->save();
    //
    //        $notifyServiceMock = Mockery::mock(NotifyService::class);
    //        $notifyServiceMock->shouldReceive('notifyUser')
    //            ->once()
    //            ->with(
    //                Mockery::type('string'),
    //                Mockery::on(function ($user) use ($newUser) {
    //                    return $user->id === $newUser->id;
    //                })
    //            );
    //
    //        $this->app->instance(NotifyService::class, $notifyServiceMock);
    //
    //        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Radio);
    //
    //        $body = [
    //            'survey' => [
    //                'name' => 'Updated Survey Name',
    //                'survey_type_id' => $baseData['survey_type_id'],
    //                'status' => SurveyStatusEnum::Active->value,
    //                'survey_questions' => [
    //                    [
    //                        'question' => 'Test Question',
    //                        'answer_type_id' => $answerType->id,
    //                        'is_required' => true,
    //                        'survey_answer_variants' => ['Variant 1'],
    //                    ],
    //                ],
    //            ],
    //            'users_id' => [$baseData['users'][0]->id, $newUser->id],
    //            'departments_id' => [],
    //        ];
    //
    //        $response = $this->putJson(
    //            route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
    //            $body,
    //            $this->headers(UserRoleEnum::Admin)
    //        );
    //
    //        $response->assertStatus(200);
    //    }

    /**
     * Text: text text notifications, text text text Active
     */
    public function test_does_not_notify_when_status_not_active(): void
    {
        $baseData = $this->createBaseData();

        $existingWorker = $baseData['workers'][0];
        $survey = $this->createSurveyWithWorkers([$existingWorker->id], SurveyStatusEnum::Created);

        $newUser = User::factory()->create();
        $newWorker = Worker::where('user_id', $newUser->id)->first();
        $newWorker->statusId = WorkerStatus::where('code', WorkerStatusEnum::Working->value)->firstOrFail()->id;
        $newWorker->save();

        $notifyServiceMock = Mockery::mock(NotifyService::class);
        $notifyServiceMock->shouldNotReceive('notifyUser');

        $this->app->instance(NotifyService::class, $notifyServiceMock);

        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Radio);

        $body = [
            'survey' => [
                'name' => 'Updated Survey Name',
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'survey_type_id' => $baseData['survey_type_id'],
                'status' => SurveyStatusEnum::Created->value,
                'approximate_time' => 10,
                'survey_questions' => [
                    [
                        'question' => 'Test Question',
                        'answer_type_id' => $answerType->id,
                        'is_required' => true,
                        'survey_answer_variants' => ['Variant 1'],
                    ],
                ],
            ],
            'users_id' => [$baseData['users'][0]->id, $newUser->id],
            'workers_id' => [$existingWorker->id, $newWorker->id],
            'departments_id' => $baseData['departments_id'],
        ];

        $response = $this->putJson(
            route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
            $body,
            $this->headers(UserRoleEnum::Admin)
        );

        $response->assertStatus(200);
    }

    /**
     * Text: text text text text Active notifies text text workers
     */
    public function test_notifies_all_workers_when_status_changes_to_active(): void
    {
        $baseData = $this->createBaseData();

        $existingWorkers = $baseData['workers'];
        $workerIds = array_map(fn ($w) => $w->id, $existingWorkers);
        $survey = $this->createSurveyWithWorkers($workerIds);

        $notifyServiceMock = Mockery::mock(NotifyService::class);
        $notifyServiceMock->shouldReceive('notifyUser')
            ->times(count($workerIds))
            ->with(
                Mockery::type('string'),
                Mockery::on(function ($user) use ($baseData) {
                    return in_array($user->id, $baseData['users_id'], true);
                })
            );

        $this->app->instance(NotifyService::class, $notifyServiceMock);

        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Radio);

        $body = [
            'survey' => [
                'name' => 'Updated Survey Name',
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'survey_type_id' => $baseData['survey_type_id'],
                'status' => SurveyStatusEnum::Active->value,
                'approximate_time' => 10,
                'survey_questions' => [
                    [
                        'question' => 'Test Question',
                        'answer_type_id' => $answerType->id,
                        'is_required' => true,
                        'survey_answer_variants' => ['Variant 1'],
                    ],
                ],
            ],
            'users_id' => $baseData['users_id'],
            'workers_id' => $workerIds,
            'departments_id' => [],
        ];

        $response = $this->putJson(
            route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
            $body,
            $this->headers(UserRoleEnum::Admin)
        );

        $response->assertStatus(200);
    }

    // todo: Text/text text text text text Notifier.
    //    /**
    //     * Text: notifies text workers text text Working text ProbationPeriod
    //     */
    //    public function test_notifies_only_active_workers(): void
    //    {
    //        $baseData = $this->createBaseData();
    //
    //        $existingWorker = $baseData['workers'][0];
    //        $survey = $this->createSurveyWithWorkers([$existingWorker->id], SurveyStatusEnum::Created);
    //
    //        $workingUser = User::factory()->create();
    //        $workingWorker = Worker::where('user_id', $workingUser->id)->first();
    //        $workingWorker->statusId = WorkerStatus::where('code', WorkerStatusEnum::Working->value)->firstOrFail()->id;
    //        $workingWorker->save();
    //
    //        $probationUser = User::factory()->create();
    //        $probationWorker = Worker::where('user_id', $probationUser->id)->first();
    //        $probationWorker->statusId = WorkerStatus::where('code', WorkerStatusEnum::ProbationPeriod->value)->firstOrFail()->id;
    //        $probationWorker->save();
    //
    //        $notifyServiceMock = Mockery::mock(NotifyService::class);
    //        $notifyServiceMock->shouldReceive('notifyUser')
    //            ->twice()
    //            ->with(
    //                Mockery::type('string'),
    //                Mockery::on(function ($user) use ($workingUser, $probationUser) {
    //                    return $user->id === $workingUser->id || $user->id === $probationUser->id;
    //                })
    //            );
    //
    //        $this->app->instance(NotifyService::class, $notifyServiceMock);
    //
    //        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Radio);
    //
    //        $body = [
    //            'survey' => [
    //                'name' => 'Updated Survey Name',
    //                'survey_type_id' => $baseData['survey_type_id'],
    //                'status' => SurveyStatusEnum::Active->value,
    //                'survey_questions' => [
    //                    [
    //                        'question' => 'Test Question',
    //                        'answer_type_id' => $answerType->id,
    //                        'is_required' => true,
    //                        'survey_answer_variants' => ['Variant 1'],
    //                    ],
    //                ],
    //            ],
    //            'users_id' => [$baseData['users'][0]->id, $workingUser->id, $probationUser->id],
    //            'departments_id' => [],
    //        ];
    //
    //        $response = $this->putJson(
    //            route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
    //            $body,
    //            $this->headers(UserRoleEnum::Admin)
    //        );
    //
    //        $response->assertStatus(200);
    //    }

    /**
     * Text: text text notifications text text workers text survey text text Created
     * Text: text workers text survey text text text Created text text
     */
    public function test_does_not_notify_when_workers_removed(): void
    {
        $baseData = $this->createBaseData();

        $existingWorkers = $baseData['workers'];
        $workerIds = array_map(fn ($w) => $w->id, $existingWorkers);
        $survey = $this->createSurveyWithWorkers($workerIds, SurveyStatusEnum::Created);

        $notifyServiceMock = Mockery::mock(NotifyService::class);
        $notifyServiceMock->shouldNotReceive('notifyUser');

        $this->app->instance(NotifyService::class, $notifyServiceMock);

        $answerType = $this->getAnswerTypeByBaseType(SurveyAnswerTypeEnum::Radio);

        // Text text worker, text text text Created
        $body = [
            'survey' => [
                'name' => 'Updated Survey Name',
                'anonymity' => SurveyAnonymityEnum::Public->value,
                'survey_type_id' => $baseData['survey_type_id'],
                'status' => SurveyStatusEnum::Created->value, // Text Created, text text text text workers
                'approximate_time' => 10,
                'survey_questions' => [
                    [
                        'question' => 'Test Question',
                        'answer_type_id' => $answerType->id,
                        'is_required' => true,
                        'survey_answer_variants' => ['Variant 1'],
                    ],
                ],
            ],
            'workers_id' => [$existingWorkers[0]->id],
            'users_id' => [$baseData['users'][0]->id], // Text text worker
            'departments_id' => [],
        ];

        $response = $this->putJson(
            route('api.surveys.survey.updateAggregate', ['survey' => $survey->id]),
            $body,
            $this->headers(UserRoleEnum::Admin)
        );

        $response->assertStatus(200);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
