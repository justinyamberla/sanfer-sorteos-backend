<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ImagenActividadController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ActividadController;

Route::get('/actividad/actual', [ActividadController::class, 'ultimaActividadActiva']);
Route::post('/actividades/{id}', [ActividadController::class, 'update'])->name('actividades.update');

Route::get('/actividades/actual/pedidos', [PedidoController::class, 'porActividadActiva']);
Route::get('/actividades/{id}/pedidos', [PedidoController::class, 'porActividad']);

Route::apiResource('actividades', ActividadController::class);
Route::apiResource('pedidos', PedidoController::class);
Route::post('/pedidos/offline', [PedidoController::class, 'storeOffline']);
Route::patch('/pedidos/{id}/estado', [PedidoController::class, 'actualizarEstado']);
