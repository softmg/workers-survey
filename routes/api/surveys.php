<?php

use App\Http\Controllers\Api\SurveyAggregateController;
use App\Http\Controllers\Api\SurveyController;
use Illuminate\Support\Facades\Route;

Route::prefix('surveys')->name('surveys.')->group(function () {
    Route::prefix('offboarding')->name('offboarding.')->middleware('role:hr')->group(function () {
        Route::get('workers', [SurveyController::class, 'offboardingWorkers'])->name('workers');
        Route::get('answers', [SurveyController::class, 'offboardingAnswers'])->name('answers');
        Route::get('filters', [SurveyController::class, 'offboardingFilters'])->name('filters');
    });

    Route::get('', [SurveyController::class, 'index'])->name('index');
    Route::post('/aggregate', [SurveyAggregateController::class, 'saveAggregate'])->name('survey.saveAggregate');
    Route::put('/{survey}/aggregate', [SurveyAggregateController::class, 'updateAggregate'])->name('survey.updateAggregate');
    Route::get('{survey}/questions', [SurveyController::class, 'questions'])->name('questions');
    Route::get('{survey}/aggregate', [SurveyController::class, 'aggregate'])->name('survey.getAggregate');
    Route::post('{survey}/answers', [SurveyController::class, 'saveAnswers'])->name('saveAnswers');
    Route::get('/result_burnout', [SurveyController::class, 'burnoutResult'])->name('burnoutResult')->middleware('role:hr,admin');
    Route::get('all', [SurveyController::class, 'all'])->name('all')->middleware('role:hr,admin,ceo');
    Route::get('templates', [SurveyController::class, 'getTemplates'])->name('templates')->middleware('role:hr,admin,ceo');
    Route::get('{survey}/information', [SurveyController::class, 'information'])->name('information')->middleware('role:hr,admin,ceo');
    Route::get('{survey}/workers', [SurveyController::class, 'workers'])->name('workers')->middleware('role:hr,admin,ceo');
    Route::get('{survey}/answers', [SurveyController::class, 'answers'])->name('answers')->middleware('role:hr,admin,ceo');
    Route::get('/questions/{question}/answers', [SurveyController::class, 'questionAnswers'])->name('questionAnswers')->middleware('role:hr,admin,ceo');
    Route::get('/filters', [SurveyController::class, 'filters'])->name('filters')->middleware('role:hr,admin,ceo');
    Route::get('{survey}/workers/filters', [SurveyController::class, 'workerFilters'])->name('workers.filters')->middleware('role:hr,admin,ceo');
    Route::get('answer-types', [SurveyController::class, 'answerTypes'])->name('answerTypes');
});
