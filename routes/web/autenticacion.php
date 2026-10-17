<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalDocenteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'create'])->name('home');
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest')->name('login.store');

Route::get('/dashboard', function (Request $request, PortalDocenteController $portal) {
    if ($request->user()->esDocente()) {
        return $portal->panel($request);
    }

    return view('panel');
})->middleware('auth')->name('dashboard');

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
