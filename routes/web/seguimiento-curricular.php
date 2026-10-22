<?php

use App\Http\Controllers\SeguimientoCurricularController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/seguimiento-curricular', [SeguimientoCurricularController::class, 'index'])
        ->name('seguimiento-curricular.index');
});
