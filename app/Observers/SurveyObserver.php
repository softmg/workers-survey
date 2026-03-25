<?php

namespace App\Observers;

use App\Events\SurveyChanged;
use App\Models\Survey\Survey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SurveyObserver
{
    private static array $oldWorkersCache = [];
    private static array $oldStatusCache = [];
    private static array $shouldCheckWorkers = [];

    public static function getOldWorkers(int $surveyId): ?Collection
    {
        return self::$oldWorkersCache[$surveyId] ?? null;
    }

    public static function clearOldWorkersCache(int $surveyId): void
    {
        unset(
            self::$oldWorkersCache[$surveyId],
            self::$oldStatusCache[$surveyId],
            self::$shouldCheckWorkers[$surveyId],
        );
    }

    private static function clearOldWorkersCachePrivate(int $surveyId): void
    {
        self::clearOldWorkersCache($surveyId);
    }

    public function saving(Survey $survey): void
    {
        if ($survey->exists) {
            if (!$survey->relationLoaded('workers')) {
                $survey->load('workers');
            }
            self::$oldWorkersCache[$survey->id] = $survey->workers;
            self::$oldStatusCache[$survey->id] = $survey->getOriginal('status') ?? $survey->status;
            self::$shouldCheckWorkers[$survey->id] = true;
        }
    }

    public function updating(Survey $survey): void
    {
        if ($survey->exists) {
            if (!$survey->relationLoaded('workers')) {
                $survey->load('workers');
            }
            self::$oldWorkersCache[$survey->id] = $survey->workers;
            self::$oldStatusCache[$survey->id] = $survey->getOriginal('status') ?? $survey->status;
            self::$shouldCheckWorkers[$survey->id] = true;
        }
    }

    public function saved(Survey $survey): void
    {
        if ($survey->wasRecentlyCreated) {
            return;
        }

        DB::afterCommit(function () use ($survey) {
            $this->checkWorkersChangesOnSave($survey);
        });
    }

    private function checkWorkersChangesOnSave(Survey $survey): void
    {
        if (!isset(self::$shouldCheckWorkers[$survey->id])) {
            return;
        }

        $this->checkWorkersChanges($survey);
    }

    private function checkWorkersChanges(Survey $survey): void
    {
        $survey->refresh();
        $survey->load('workers');

        $newWorkers = $survey->workers;
        $newWorkerIds = $newWorkers->pluck('id')->toArray();

        $oldWorkers = self::$oldWorkersCache[$survey->id] ?? null;
        $oldWorkerIds = $oldWorkers ? $oldWorkers->pluck('id')->toArray() : [];

        $hasWorkersChange = $oldWorkerIds !== $newWorkerIds;

        $oldStatus = self::$oldStatusCache[$survey->id] ?? null;
        $newStatus = $survey->status;

        if ($hasWorkersChange || $oldStatus !== $newStatus) {
            event(new SurveyChanged(
                $survey,
                $oldWorkers,
                $newWorkers,
                $oldStatus,
                $newStatus
            ));
        }

        self::clearOldWorkersCachePrivate($survey->id);
    }
}
