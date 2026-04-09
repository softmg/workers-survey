<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.auth', 'api'])->prefix('api')->name('api.')->group(function () {
    require base_path('routes/api/surveys.php');
});
