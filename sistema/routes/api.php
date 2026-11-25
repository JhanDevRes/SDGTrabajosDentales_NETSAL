<?php

use App\Http\Controllers\Api\ConsultaOrdenController;
use App\Http\Controllers\Api\EstadisticaController;
use App\Http\Controllers\Api\SolicitudContactoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API pública del sitio web
|--------------------------------------------------------------------------
|
| Estas rutas NO usan sesión ni CSRF: las consume el sitio estático, que corre
| en otro origen. La protección se apoya en:
|
|   - throttle:publico  → 30 peticiones por minuto y por IP (consultas).
|   - throttle:contacto →  5 envíos por minuto y por IP (formulario).
|   - Validación y consultas parametrizadas de Laravel.
|   - Respuestas con solo los datos de la orden consultada.
|
*/

Route::prefix('publico')->group(function (): void {
    Route::get('estadisticas', EstadisticaController::class)
        ->middleware('throttle:publico')
        ->name('api.publico.estadisticas');

    Route::get('ordenes/{codigo}', ConsultaOrdenController::class)
        ->middleware('throttle:publico')
        ->name('api.publico.ordenes');

    Route::post('contacto', SolicitudContactoController::class)
        ->middleware('throttle:contacto')
        ->name('api.publico.contacto');
});
