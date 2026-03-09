<?php

namespace App\Events;

use App\Enums\SurveyStatusEnum;
use App\Models\Survey\Survey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Queue\SerializesModels;

class SurveyChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Survey $survey,
        public ?Collection $oldWorkers = null,
        public ?Collection $newWorkers = null,
        public ?SurveyStatusEnum $oldStatus = null,
        public ?SurveyStatusEnum $newStatus = null,
    ) {
    }
}
