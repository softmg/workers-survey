<?php

namespace App\Http\Resources\Survey\answers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyBurnoutAnswersResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary = $this['summary'];
        $workers = $this['workers'];

        return [
            'emotional_burnout'      => round($summary['emotional_burnout'], 2),
            'depersonalization'      => round($summary['depersonalization'], 2),
            'reduction_achievements' => round($summary['reduction_achievements'], 2),
            'index_burnout'          => round($summary['index_burnout'], 2),

            'worker_results' => WorkerResultResource::collection($workers),
        ];
    }
}
