<?php

use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\MateriaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:gestionar-docencia'])->group(function () {
    Route::get('/docentes', [DocenteController::class, 'index'])->name('docentes.index');
    Route::get('/docentes/create', [DocenteController::class, 'create'])->name('docentes.create');
    Route::post('/docentes', [DocenteController::class, 'store'])->name('docentes.store');
    Route::get('/docentes/{docente}', [DocenteController::class, 'show'])->name('docentes.show');
    Route::get('/docentes/{docente}/edit', [DocenteController::class, 'edit'])->name('docentes.edit');
    Route::match(['put', 'patch'], '/docentes/{docente}', [DocenteController::class, 'update'])->name('docentes.update');
    Route::delete('/docentes/{docente}', [DocenteController::class, 'destroy'])->name('docentes.destroy');

    Route::get('/materias', [MateriaController::class, 'index'])->name('materias.index');
    Route::get('/materias/create', [MateriaController::class, 'create'])->name('materias.create');
    Route::post('/materias', [MateriaController::class, 'store'])->name('materias.store');
    Route::get('/materias/{materia}', [MateriaController::class, 'show'])->name('materias.show');
    Route::get('/materias/{materia}/edit', [MateriaController::class, 'edit'])->name('materias.edit');
    Route::match(['put', 'patch'], '/materias/{materia}', [MateriaController::class, 'update'])->name('materias.update');
    Route::delete('/materias/{materia}', [MateriaController::class, 'destroy'])->name('materias.destroy');

    Route::get('/asignaciones', [AsignacionController::class, 'index'])->name('asignaciones.index');
    Route::get('/asignaciones/create', [AsignacionController::class, 'create'])->name('asignaciones.create');
    Route::post('/asignaciones', [AsignacionController::class, 'store'])->name('asignaciones.store');
    Route::get('/asignaciones/{asignacion}', [AsignacionController::class, 'show'])->name('asignaciones.show');
    Route::get('/asignaciones/{asignacion}/edit', [AsignacionController::class, 'edit'])->name('asignaciones.edit');
    Route::match(['put', 'patch'], '/asignaciones/{asignacion}', [AsignacionController::class, 'update'])->name('asignaciones.update');
    Route::delete('/asignaciones/{asignacion}', [AsignacionController::class, 'destroy'])->name('asignaciones.destroy');
});
