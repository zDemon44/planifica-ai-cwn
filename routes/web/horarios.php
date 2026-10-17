<?php

use App\Http\Controllers\HorarioController;
use App\Http\Controllers\RecreoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
    Route::get('/horarios/create', [HorarioController::class, 'create'])->name('horarios.create');
    Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');
    Route::get('/horarios/{horario}', [HorarioController::class, 'show'])->name('horarios.show');
    Route::get('/horarios/{horario}/edit', [HorarioController::class, 'edit'])->name('horarios.edit');
    Route::match(['put', 'patch'], '/horarios/{horario}', [HorarioController::class, 'update'])->name('horarios.update');
});

Route::middleware(['auth', 'can:gestionar-docencia'])->group(function () {
    Route::get('/recreos', [RecreoController::class, 'index'])->name('recreos.index');
    Route::get('/recreos/create', [RecreoController::class, 'create'])->name('recreos.create');
    Route::post('/recreos', [RecreoController::class, 'store'])->name('recreos.store');
    Route::get('/recreos/{recreo}/edit', [RecreoController::class, 'edit'])->name('recreos.edit');
    Route::match(['put', 'patch'], '/recreos/{recreo}', [RecreoController::class, 'update'])->name('recreos.update');
});
