<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\OrdenTrabajo;
use App\Models\User;
use App\Support\CacheConsultas;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel principal del sistema.
 *
 * Optimizaciones aplicadas:
 *  1. Los cuatro contadores se obtienen con UNA sola consulta usando
 *     agregación condicional (SUM(CASE WHEN ...)) en lugar de cuatro COUNT.
 *  2. El resultado se almacena en caché por 5 minutos. La caché se invalida
 *     automáticamente cuando cambia una orden, un material o una entrega
 *     (trait InvalidaCacheConsultas).
 *  3. Las órdenes recientes se consultan con columnas explícitas y la
 *     relación del cliente precargada (evita el problema N+1).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $esPortal = $user->esOdontologo();

        // Clave de caché: los odontólogos ven solo sus cifras, el personal ve las globales.
        $ambito = $esPortal ? 'portal:'.$user->id : 'personal';

        $stats = CacheConsultas::recordar(
            CacheConsultas::GRUPO_PANEL,
            'indicadores:'.$ambito,
            CacheConsultas::TTL_DEFECTO,
            fn (): array => $this->calcularIndicadores($user),
        );

        $recientes = OrdenTrabajo::query()
            ->select(['id', 'codigo', 'cliente_id', 'tipo_trabajo', 'estado', 'fecha_entrega', 'fecha_ingreso'])
            ->with('cliente:id,nombre')
            ->when($esPortal, fn ($query) => $query->paraUsuarioPortal($user->id))
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $stockBajo = $user->puedeVerModulo('materiales')
            ? Material::query()
                ->select(['id', 'nombre', 'unidad', 'stock', 'stock_minimo'])
                ->activos()
                ->stockBajo()
                ->orderBy('stock')
                ->limit(5)
                ->get()
            : collect();

        return view('panel', compact('stats', 'recientes', 'stockBajo'));
    }

    /**
     * Calcula los cuatro indicadores con una única consulta agregada.
     *
     * @return array<string, int>
     */
    private function calcularIndicadores(User $user): array
    {
        $abiertos = OrdenTrabajo::ESTADOS_ABIERTOS;
        $marcadores = implode(',', array_fill(0, count($abiertos), '?'));

        $fila = OrdenTrabajo::query()
            ->when($user->esOdontologo(), fn ($query) => $query->paraUsuarioPortal($user->id))
            ->selectRaw(
                "SUM(CASE WHEN estado IN ({$marcadores}) THEN 1 ELSE 0 END) AS pendientes",
                $abiertos
            )
            ->selectRaw(
                "SUM(CASE WHEN estado IN ({$marcadores}) AND fecha_entrega IS NOT NULL AND fecha_entrega < ? THEN 1 ELSE 0 END) AS atrasadas",
                [...$abiertos, now()->toDateString()]
            )
            ->selectRaw('SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) AS listas', ['listo'])
            ->selectRaw(
                'SUM(CASE WHEN estado = ? AND updated_at >= ? AND updated_at < ? THEN 1 ELSE 0 END) AS entregadas_mes',
                ['entregado', now()->startOfMonth()->toDateTimeString(), now()->startOfMonth()->addMonth()->toDateTimeString()]
            )
            ->first();

        return [
            'pendientes' => (int) ($fila->pendientes ?? 0),
            'atrasadas' => (int) ($fila->atrasadas ?? 0),
            'listas' => (int) ($fila->listas ?? 0),
            'entregadas_mes' => (int) ($fila->entregadas_mes ?? 0),
        ];
    }
}
