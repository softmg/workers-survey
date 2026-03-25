<?php

namespace App\Models\Survey;

use App\Enums\SurveyAnonymityEnum;
use App\Enums\SurveyStatusEnum;
use App\Enums\SurveyTypeEnum;
use App\Models\Worker\Worker;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Survey extends Model
{
    use Filterable;
    use HasFactory;

    public const IMMUTABLE_UPDATE_ALLOWED_FIELDS = [
        'approximate_time',
        'status',
        'date_end',
    ];

    protected $fillable = [
        'name',
        'description',
        'survey_type_id',
        'status',
        'date_end',
        'is_template',
        'approximate_time',
        'anonymity',
    ];

    protected $casts = [
        'status' => SurveyStatusEnum::class,
        'anonymity' => SurveyAnonymityEnum::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approximate_time' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(SurveyType::class, 'survey_type_id', 'id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class, 'survey_id', 'id');
    }

    public function completions(): HasMany
    {
        return $this->HasMany(SurveyCompletion::class, 'survey_id', 'id');
    }

    public function workerCompletedSurveys(): HasMany
    {
        return $this->HasMany(SurveyCompletion::class, 'survey_id', 'id');
    }

    public function workerNotCompletedSurveyCompletions(): HasMany
    {
        return $this->completions()->where('completed', 0);
    }

    public function workerCompletedSurveyCompletions(): HasMany
    {
        return $this->completions()->where('completed', 1);
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(Worker::class, 'survey_completions', 'survey_id', 'worker_id')
            ->withTimestamps();
    }

    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyAnswer::class, SurveyQuestion::class, 'survey_id', 'question_id', 'id', 'id');
    }

    public function scopeWithType(Builder $query, SurveyTypeEnum $type): void
    {
        $query->whereHas('type', function (Builder $query) use ($type) {
            $query->where('code', $type->value);
        });
    }

    public function pages(): HasMany
    {
        return $this->hasMany(SurveyPage::class, 'survey_id', 'id');
    }

    public function isImmutable(): bool
    {
        return in_array($this->status, [SurveyStatusEnum::Active, SurveyStatusEnum::Closed], true);
    }
}
