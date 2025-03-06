<?php

use App\Http\Controllers\PlatoController;
use Illuminate\Support\Facades\Route;

    Route::post('platos', [PlatoController::class, 'store']);  
    Route::put('platos/{id}', [PlatoController::class, 'update']); 
    Route::delete('platos/{id}', [PlatoController::class, 'destroy']); 
Route::get('platos', [PlatoController::class, 'index']); 
Route::get('platos/{id}', [PlatoController::class, 'show']); 
