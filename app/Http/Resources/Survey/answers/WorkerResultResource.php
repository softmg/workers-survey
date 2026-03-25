<?php

namespace App\Http\Resources\Survey\answers;

use App\Http\Resources\DepartmentForUserResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResultResource extends JsonResource
{
    public function toArray($request): array
    {
        $worker = $this['worker'];
        $department = $this['department'];
        $chief = $this['chief'];
        $hr = $this['hr'];
        $projects = $this['projects'];

        return [
            'id_worker' => $worker->id,
            'name_worker' => $worker->fio,
            'hiring_date' => $worker->hiring_day?->toDateString(),

            'date_survey_create' => $this['date_survey_create']?->toDateString(),
            'date_survey_answer' => $this['date_survey_answer']?->toDateString(),

            'department' => $department
                ? DepartmentForUserResource::make($department)
                : null,

            'chief' => $chief
                ? UserResource::make($chief)
                : null,
            'hr' => $hr
                ? UserResource::make($hr)
                : null,

            'projects' => $projects
                ? $projects->map(fn ($p) => $p->getAttributes())->values()->all()
                : [],

            'emotional_burnout' => round($this['emotional_burnout'], 2),
            'depersonalization' => round($this['depersonalization'], 2),
            'reduction_achievements' => round($this['reduction_achievements'], 2),
            'index_burnout' => round($this['index_burnout'], 2),
        ];
    }
}
