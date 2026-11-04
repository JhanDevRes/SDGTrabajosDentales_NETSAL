<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConsumoMaterialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\OrdenTrabajoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.store');
});

Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('panel', DashboardController::class)->name('panel');

    Route::middleware('role:administrador,recepcion')->group(function () {
        Route::resource('clientes', ClienteController::class)->except('show');
        Route::post('clientes/{cliente}/activar', [ClienteController::class, 'activar'])->name('clientes.activar');
    });

    Route::get('ordenes', [OrdenTrabajoController::class, 'index'])->name('ordenes.index');

    Route::middleware('role:administrador,recepcion,disenador,tecnico')->group(function () {
        Route::get('ordenes/create', [OrdenTrabajoController::class, 'create'])->name('ordenes.create');
        Route::post('ordenes', [OrdenTrabajoController::class, 'store'])->name('ordenes.store');
        Route::get('ordenes/{orden}/edit', [OrdenTrabajoController::class, 'edit'])->name('ordenes.edit');
        Route::put('ordenes/{orden}', [OrdenTrabajoController::class, 'update'])->name('ordenes.update');
        Route::delete('ordenes/{orden}', [OrdenTrabajoController::class, 'destroy'])->name('ordenes.destroy');
        Route::post('ordenes/{orden}/estado', [OrdenTrabajoController::class, 'cambiarEstado'])->name('ordenes.estado');
    });

    Route::get('ordenes/{orden}', [OrdenTrabajoController::class, 'show'])->name('ordenes.show');

    Route::middleware('role:administrador,recepcion,tecnico')->group(function () {
        Route::post('ordenes/{orden}/consumos', [ConsumoMaterialController::class, 'store'])->name('consumos.store');
        Route::delete('ordenes/{orden}/consumos/{consumo}', [ConsumoMaterialController::class, 'destroy'])->name('consumos.destroy');
    });

    Route::middleware('role:administrador,tecnico')->group(function () {
        Route::resource('materiales', MaterialController::class)
            ->except('show')
            ->parameters(['materiales' => 'material']);
        Route::post('materiales/{material}/activar', [MaterialController::class, 'activar'])->name('materiales.activar');
    });

    Route::middleware('role:administrador,recepcion')->group(function () {
        Route::resource('entregas', EntregaController::class)->except('show');
        Route::post('entregas/{entrega}/confirmar', [EntregaController::class, 'confirmar'])->name('entregas.confirmar');
    });

    Route::get('reportes', [ReporteController::class, 'index'])
        ->middleware('role:administrador,recepcion,tecnico')
        ->name('reportes.index');

    Route::middleware('role:administrador')->group(function () {
        Route::resource('usuarios', UserController::class)
            ->except('show')
            ->parameters(['usuarios' => 'usuario']);
        Route::post('usuarios/{usuario}/activar', [UserController::class, 'activar'])->name('usuarios.activar');
    });
});
