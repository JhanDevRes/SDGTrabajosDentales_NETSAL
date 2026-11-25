<?php

namespace App\Http\Controllers\Api;

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Support\CacheConsultas;
use App\Support\Indicadores;
use Illuminate\Http\JsonResponse;

/**
 * Estadísticas abiertas del laboratorio para el sitio web.
 *
 * Solo se publican cifras agregadas: ningún dato de un cliente ni de una
 * orden concreta sale por este endpoint.
 *
 * Optimización aplicada (tema de la semana 11):
 *  - Los tres contadores de órdenes se resuelven con UNA consulta usando
 *    agregación condicional SUM(CASE WHEN ...), en lugar de tres COUNT.
 *  - El cumplimiento de plazos y el tiempo medio de producción se calculan
 *    con AVG y DATEDIFF dentro del motor, sin traer filas a PHP.
 *  - El resultado completo se guarda en caché 60 segundos (GRUPO_PUBLICO) y se
 *    invalida automáticamente cuando cambia una orden o una entrega.
 *  - En total: 3 consultas por petición fría, 0 cuando la caché responde.
 */
class EstadisticaController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $datos = CacheConsultas::recordar(
            CacheConsultas::GRUPO_PUBLICO,
            'estadisticas-sitio',
            CacheConsultas::TTL_PUBLICO,
            fn (): array => $this->calcular(),
        );

        $datos['actualizado_en'] = now()->toIso8601String();

        return $this->ok($datos);
    }

    /**
     * @return array<string, int|float>
     */
    private function calcular(): array
    {
        $abiertos = OrdenTrabajo::ESTADOS_ABIERTOS;
        $marcadores = implode(',', array_fill(0, count($abiertos), '?'));

        $ordenes = OrdenTrabajo::query()
            ->selectRaw('SUM(CASE WHEN estado <> ? THEN 1 ELSE 0 END) AS procesadas', ['anulado'])
            ->selectRaw(
                "SUM(CASE WHEN estado IN ({$marcadores}) THEN 1 ELSE 0 END) AS activas",
                $abiertos
            )
            ->selectRaw(
                'SUM(CASE WHEN estado = ? AND updated_at >= ? AND updated_at < ? THEN 1 ELSE 0 END) AS entregadas_mes',
                ['entregado', now()->startOfMonth()->toDateTimeString(), now()->startOfMonth()->addMonth()->toDateTimeString()]
            )
            ->first();

        $clientes = Cliente::query()->where('activo', true)->count();

        $plazos = Indicadores::plazos();

        return [
            'ordenes_procesadas' => (int) ($ordenes->procesadas ?? 0),
            'ordenes_activas' => (int) ($ordenes->activas ?? 0),
            'entregadas_mes' => (int) ($ordenes->entregadas_mes ?? 0),
            'clientes_activos' => (int) $clientes,
            'cumplimiento_plazo' => $plazos['cumplimiento'],
            'tiempo_promedio_dias' => $plazos['promedio'],
        ];
    }
}
