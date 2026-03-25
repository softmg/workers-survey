<?php

namespace App\Models\Survey;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestionVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'worker_id',
        'variant',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class, 'question_id', 'id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id', 'id');
    }

    public function answerVariants(): HasMany
    {
        return $this->hasMany(SurveyAnswerVariants::class, 'variant_id', 'id');
    }
}
