<?php

namespace App\Models\Survey;

use App\Models\Worker\Worker;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SurveyQuestion extends Model
{
    use Filterable;
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'survey_page_id',
        'answer_type_id',
        'question',
        'question_number',
        'is_required',
        'code',
    ];

    public $casts = [
        'is_required' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($question) {
            if (is_null($question->question_number)) {
                $max = static::where('survey_id', $question->survey_id)
                    ->max('question_number');
                $question->question_number = $max ? $max + 1 : 1;
            }
        });
    }

    public function survey(): HasOne
    {
        return $this->hasOne(Survey::class, 'id', 'survey_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(SurveyQuestionVariant::class, 'question_id', 'id');
    }

    public function answerType(): HasOne
    {
        return $this->hasOne(SurveyAnswerType::class, 'id', 'answer_type_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class, 'question_id', 'id');
    }

    public function workers(): HasManyThrough
    {
        return $this->hasManyThrough(Worker::class, SurveyCompletion::class, 'survey_id', 'variant_id', 'id', 'id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SurveyPage::class, 'survey_page_id', 'id');
    }
}
