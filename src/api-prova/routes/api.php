<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AtendimentoController;

Route::get('/healthz', [AtendimentoController::class, 'healthz']);
Route::post('/senhas', [AtendimentoController::class, 'emitir']);
Route::get('/senhas/proxima', [AtendimentoController::class, 'proxima']);
Route::post('/senhas/{codigo}/concluir', [AtendimentoController::class, 'concluir']);
Route::post('/senhas/{codigo}/rechamar', [AtendimentoController::class, 'rechamar']);
Route::post('/senhas/{codigo}/cancelar', [AtendimentoController::class, 'cancelar']);
Route::get('/painel', [AtendimentoController::class, 'painel']);
