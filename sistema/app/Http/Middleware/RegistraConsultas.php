<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Instrumentación de la capa de persistencia (herramienta de análisis).
 *
 * Para cada petición:
 *  1. Cuenta las consultas SQL ejecutadas y su tiempo total.
 *  2. Registra en el log las que superan `consultas.limite_lento_ms`.
 *  3. Expone dos encabezados de respuesta (X-Consultas y X-Tiempo-Consultas)
 *     que permiten comparar el antes y el después de una optimización desde
 *     las herramientas del navegador, sin instalar paquetes externos.
 *
 * Todo el trabajo se omite cuando `consultas.activo` es false, por lo que no
 * afecta a producción.
 */
class RegistraConsultas
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('consultas.activo')) {
            return $next($request);
        }

        $consultas = [];
        $limite = (int) config('consultas.limite_lento_ms', 100);

        DB::listen(function ($query) use (&$consultas): void {
            $consultas[] = [
                'sql' => $query->sql,
                'tiempo' => $query->time,
            ];
        });

        $response = $next($request);

        $total = array_sum(array_column($consultas, 'tiempo'));

        if (config('consultas.encabezados')) {
            $response->headers->set('X-Consultas', (string) count($consultas));
            $response->headers->set('X-Tiempo-Consultas', number_format($total, 2, '.', '').' ms');
        }

        foreach ($consultas as $consulta) {
            if ($consulta['tiempo'] >= $limite) {
                Log::warning('Consulta lenta detectada', [
                    'ruta' => $request->path(),
                    'ms' => round($consulta['tiempo'], 2),
                    'sql' => $consulta['sql'],
                ]);
            }
        }

        return $response;
    }
}
