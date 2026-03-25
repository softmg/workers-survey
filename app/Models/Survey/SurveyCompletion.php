<?php

namespace App\Models\Survey;

use App\Models\Worker\Worker;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SurveyCompletion extends Model
{
    use Filterable;
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'survey_id',
        'completed',
        'completion_date',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completion_date' => 'date',
    ];

    public function worker(): HasOne
    {
        return $this->hasOne(Worker::class, 'id', 'worker_id');
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class, 'survey_id', 'id');
    }
}
