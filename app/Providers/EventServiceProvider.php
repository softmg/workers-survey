<?php

namespace App\Providers;

use App\Events\SurveyChanged;
use App\Listeners\HandleSurveyChangedListener;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyCompletion;
use App\Observers\SurveyObserver;
use App\Observers\Survey\SurveyCompletionObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SurveyChanged::class => [
            HandleSurveyChangedListener::class,
        ],
    ];

    public function boot()
    {
        parent::boot();

        Survey::observe(SurveyObserver::class);
        SurveyCompletion::observe(SurveyCompletionObserver::class);
    }
}
