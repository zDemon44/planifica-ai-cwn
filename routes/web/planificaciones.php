<?php

use App\Http\Controllers\PlanificacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/planificaciones', [PlanificacionController::class, 'index'])->name('planificaciones.index');
    Route::get('/planificaciones/create', [PlanificacionController::class, 'create'])->name('planificaciones.create');
    Route::post('/planificaciones', [PlanificacionController::class, 'store'])->name('planificaciones.store');
    Route::post('/planificaciones/{planificacion}/enviar', [PlanificacionController::class, 'submit'])->name('planificaciones.submit');
    Route::post('/planificaciones/{planificacion}/revisar', [PlanificacionController::class, 'review'])->name('planificaciones.review');
    Route::post('/planificaciones/{planificacion}/aprobar', [PlanificacionController::class, 'approve'])->name('planificaciones.approve');
    Route::post('/planificaciones/{planificacion}/devolver', [PlanificacionController::class, 'devolver'])->name('planificaciones.devolver');
    Route::get('/planificaciones/{planificacion}', [PlanificacionController::class, 'show'])->name('planificaciones.show');
    Route::get('/planificaciones/{planificacion}/edit', [PlanificacionController::class, 'edit'])->name('planificaciones.edit');
    Route::match(['put', 'patch'], '/planificaciones/{planificacion}', [PlanificacionController::class, 'update'])->name('planificaciones.update');
});
