<?php

use App\Http\Controllers\ImagenActividadController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActividadController;

Route::apiResource('actividades', ActividadController::class);
Route::post('/actividades/{id}', [ActividadController::class, 'update'])->name('actividades.update');
Route::get('/actividad/actual', [ActividadController::class, 'ultimaActividadActiva']);
