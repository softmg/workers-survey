<?php

namespace App\Services\Survey;

use App\Enums\WorkerStatusEnum;
use App\Models\Worker\Worker;
use Illuminate\Support\Collection;

class SurveyRecipientsService
{
    /**
     * @param array<int, int|string> $workersId
     * @param array<int, int|string> $departmentsId
     */
    public function resolveWorkerIds(array $workersId = [], array $departmentsId = []): Collection
    {
        $recipientIds = collect();

        $normalizedWorkersId = $this->normalizeIds($workersId);
        if (!empty($normalizedWorkersId)) {
            $recipientIds = $recipientIds->merge(
                Worker::query()
                    ->whereIn('id', $normalizedWorkersId)
                    ->whereHas('status', function ($query) {
                        $query->whereIn('code', [
                            WorkerStatusEnum::Working->value,
                            WorkerStatusEnum::ProbationPeriod->value,
                        ]);
                    })
                    ->pluck('id')
            );
        }

        $normalizedDepartmentsId = $this->normalizeIds($departmentsId);
        if (!empty($normalizedDepartmentsId)) {
            $recipientIds = $recipientIds->merge(
                Worker::query()
                    ->whereIn('department_id', $normalizedDepartmentsId)
                    ->whereHas('status', function ($query) {
                        $query->whereIn('code', [
                            WorkerStatusEnum::Working->value,
                            WorkerStatusEnum::ProbationPeriod->value,
                        ]);
                    })
                    ->pluck('id')
            );
        }

        return $recipientIds->unique()->values();
    }

    /**
     * @param array<int, int|string> $workersId
     * @param array<int, int|string> $departmentsId
     */
    public function hasRecipients(array $workersId = [], array $departmentsId = []): bool
    {
        return $this->resolveWorkerIds($workersId, $departmentsId)->isNotEmpty();
    }

    /**
     * @param array<int, int|string> $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            $ids
        )));
    }
}
