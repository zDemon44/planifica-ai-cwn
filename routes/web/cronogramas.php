<?php

use App\Http\Controllers\CronogramaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/cronogramas', [CronogramaController::class, 'index'])->name('cronogramas.index');
    Route::match(['put', 'patch'], '/cronogramas/{periodo}', [CronogramaController::class, 'update'])->name('cronogramas.update')->middleware('can:gestionar-docencia');
    Route::post('/cronogramas/{periodo}/dias-no-clase', [CronogramaController::class, 'storeDiaNoClase'])->name('cronogramas.dias.store')->middleware('can:gestionar-docencia');
    Route::delete('/cronogramas/{periodo}/dias-no-clase/{diaNoClase}', [CronogramaController::class, 'destroyDiaNoClase'])->name('cronogramas.dias.destroy')->middleware('can:gestionar-docencia');
});
