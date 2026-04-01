<?php

namespace App\Services\Survey;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use Illuminate\Support\Collection;

class SurveyCompletionService
{
    public function syncCompletions(Survey $survey, Collection $workerIds): void
    {
        $existingWorkerIds = $survey->completions()->pluck('worker_id')->toArray();

        $newWorkerIds = $workerIds->diff($existingWorkerIds);
        if ($newWorkerIds->isNotEmpty()) {
            $this->addNewWorkers($survey, $newWorkerIds);
        }

        $workersToDelete = collect($existingWorkerIds)->diff($workerIds);
        if ($workersToDelete->isNotEmpty()) {
            $this->removeOldWorkers($survey, $workersToDelete);
        }
    }

    private function addNewWorkers(Survey $survey, Collection $newWorkerIds): void
    {
        $completionsToInsert = $newWorkerIds->map(function ($workerId) use ($survey) {
            return [
                'survey_id' => $survey->id,
                'worker_id' => $workerId,
                'completed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        SurveyCompletion::insert($completionsToInsert);
    }

    private function removeOldWorkers(Survey $survey, Collection $workersToDelete): void
    {
        SurveyCompletion::where('survey_id', $survey->id)
            ->whereIn('worker_id', $workersToDelete)
            ->delete();
    }
}
