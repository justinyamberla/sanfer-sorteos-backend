<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActividadController;

Route::apiResource('actividades', ActividadController::class);
