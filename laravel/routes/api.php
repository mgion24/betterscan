<?php

use App\Http\Controllers\Api\Internal\EscaneoCallbackController;
use Illuminate\Support\Facades\Route;

/*
 * =============================================================
 * Rutas internas: solo el motor FastAPI las llama, autenticadas
 * mediante el middleware "internal-token" (Bearer).
 *
 * No tienen sesión ni CSRF (api routes son stateless).
 * =============================================================
 */

Route::prefix('internal')->middleware('internal-token')->group(function () {

    Route::post('/escaneo/{escaneo}/estado',     [EscaneoCallbackController::class, 'actualizarEstado']);
    Route::post('/escaneo/{escaneo}/resultados', [EscaneoCallbackController::class, 'guardarResultados']);

});
