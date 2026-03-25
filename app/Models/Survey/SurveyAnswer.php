<?php

namespace App\Models\Survey;

use App\Models\Worker\Worker;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyAnswer extends Model
{
    use HasFactory;
    use Filterable;

    protected $fillable = [
        'worker_id',
        'question_id',
        'answer_text',
        'answer_int',
    ];

    public $casts = [
        'answer_text' => 'string',
        'answer_int' => 'int',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }

    public function answerVariants(): HasMany
    {
        return $this->hasMany(SurveyAnswerVariants::class, 'survey_answer_id');
    }
}
