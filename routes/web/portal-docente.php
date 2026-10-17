<?php

use App\Http\Controllers\PortalDocenteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:portal-docente'])->group(function () {
    Route::get('/mis-cursos', [PortalDocenteController::class, 'index'])->name('portal-docente.index');
});
