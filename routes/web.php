<?php

use App\Http\Controllers\AgendaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AgendaController::class, 'index'])->name('agenda');
Route::post('/citas', [AgendaController::class, 'store'])->name('citas.store');
Route::post('/citas/{id}/estado', [AgendaController::class, 'cambiarEstado'])->name('citas.estado');
Route::post('/reiniciar', [AgendaController::class, 'reiniciar'])->name('citas.reiniciar');
