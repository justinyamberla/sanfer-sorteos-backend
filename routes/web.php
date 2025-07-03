<?php

use App\Http\Controllers\ImagenActividadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/media/actividades/{actividad_id}/{uuid}.{extension}', [ImagenActividadController::class, 'verImagen']);
