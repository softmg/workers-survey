<?php

namespace App\Models\Survey;

use App\Enums\SurveyTypeEnum;
use Eloquence\Behaviours\CamelCasing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyType extends Model
{
    use HasFactory;
    use CamelCasing;

    protected $fillable = [
        'code',
        'name',
    ];

    protected $casts = [
        'code' => SurveyTypeEnum::class,
    ];
}
