<?php

namespace App\Providers;

use App\Models\SolicitudContacto;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarLimitesDePeticiones();

        // Contador de solicitudes sin atender que se muestra en el menú lateral.
        // Se resuelve una sola vez por petición (no dentro de la plantilla) y
        // solo para los roles que tienen acceso a la bandeja.
        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();

            $view->with('solicitudesNuevas', $user !== null && $user->puedeVerModulo('solicitudes')
                ? SolicitudContacto::query()->nuevas()->count()
                : 0);
        });
    }

    /**
     * Límites de la API pública.
     *
     * Se definen por IP porque las rutas son anónimas. El objetivo es frenar el
     * sondeo automatizado sin molestar a un visitante normal:
     *
     *  - publico  → 30 consultas por minuto. Alcanza de sobra para que una
     *               persona revise varias órdenes y recargue la página.
     *  - contacto →  5 envíos por minuto. Un formulario real se envía una vez.
     *
     * Al superar el límite Laravel responde 429 Too Many Requests.
     */
    private function configurarLimitesDePeticiones(): void
    {
        RateLimiter::for('publico', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('contacto', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Inicio de sesión: 5 intentos por minuto por combinación de correo e IP.
        // Frena los ataques de fuerza bruta sin bloquear a un usuario que solo
        // se equivocó al escribir su contraseña.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
