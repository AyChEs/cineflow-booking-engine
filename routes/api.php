<?php

use App\Http\Controllers\Api\PeliculaApiController;
use Illuminate\Support\Facades\Route;

Route::get('/cartelera/filtros', [PeliculaApiController::class, 'filtros'])->name('api.cartelera.filtros');
Route::get('/cines', [PeliculaApiController::class, 'cines'])->name('api.cines.index');
Route::get('/categorias', [PeliculaApiController::class, 'categorias'])->name('api.categorias.index');
Route::get('/cartelera', [PeliculaApiController::class, 'index'])->name('api.cartelera.index');
Route::get('/cartelera/{id}', [PeliculaApiController::class, 'show'])
    ->whereNumber('id')
    ->name('api.cartelera.show');