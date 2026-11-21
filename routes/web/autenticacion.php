<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'create'])->name('home');
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest')->name('login.store');

Route::get('/dashboard', [PanelController::class, 'index'])->middleware('auth')->name('dashboard');

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
