<?php

namespace App\Models\Survey;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswerVariants extends Model
{
    use Filterable;
    use HasFactory;

    protected $fillable = [
        'survey_answer_id',
        'variant_id',
    ];

    protected $casts = [
        'variant_ids' => 'array',
    ];

    public function surveyAnswer(): BelongsTo
    {
        return $this->belongsTo(SurveyAnswer::class, 'survey_answer_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestionVariant::class, 'variant_id');
    }
}
