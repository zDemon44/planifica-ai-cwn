<?php

use Illuminate\Support\Facades\Route;

Route::view('/academico', 'academico')
    ->middleware(['auth', 'can:gestionar-docencia'])
    ->name('academico.catalogo');
