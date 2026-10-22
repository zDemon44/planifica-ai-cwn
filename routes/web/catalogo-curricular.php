<?php

use App\Http\Controllers\CatalogoCurricularController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:gestionar-docencia'])->group(function () {
    Route::get('/catalogo-curricular', [CatalogoCurricularController::class, 'index'])->name('catalogo-curricular.index');
    Route::post('/catalogo-curricular/importar', [CatalogoCurricularController::class, 'importar'])->name('catalogo-curricular.importar');
});
