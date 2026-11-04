<?php

namespace App\Http\Controllers;

use App\Models\ConsumoMaterial;
use App\Models\Material;
use App\Models\OrdenTrabajo;
use App\Support\CacheConsultas;
use Illuminate\View\View;

/**
 * Reportes de producción y económicos.
 *
 * Optimizaciones aplicadas:
 *  1. Los trabajos pendientes y atrasados se obtienen con UNA consulta y se
 *     separan en memoria con estaAtrasada(); antes se ejecutaban dos consultas
 *     casi idénticas sobre la misma tabla.
 *  2. El costo de materiales por trabajo se calcula con una subconsulta
 *     correlacionada (scopeConCostoMateriales) en lugar de cargar todos los
 *     consumos y sumarlos en PHP.
 *  3. Las cifras globales (entregadas del mes, ingreso y costo) se cachean
 *     porque cambian poco y son costosas de calcular.
 */
class ReporteController extends Controller
{
    public function index(): View
    {
        // Una sola consulta: órdenes abiertas ordenadas por fecha de entrega.
        $abiertas = OrdenTrabajo::query()
            ->select(['id', 'codigo', 'cliente_id', 'paciente', 'tipo_trabajo', 'estado', 'fecha_entrega', 'precio'])
            ->with('cliente:id,nombre')
            ->pendientes()
            ->orderBy('fecha_entrega')
            ->orderBy('id')
            ->get();

        $atrasadas = $abiertas->filter(fn (OrdenTrabajo $orden) => $orden->estaAtrasada())->values();
        $pendientes = $abiertas;

        $stockBajo = Material::query()
            ->select(['id', 'nombre', 'unidad', 'stock', 'stock_minimo'])
            ->activos()
            ->stockBajo()
            ->orderBy('stock')
            ->get();

        $ordenesCostos = OrdenTrabajo::query()
            ->select(['id', 'codigo', 'cliente_id', 'precio', 'fecha_ingreso'])
            ->with('cliente:id,nombre')
            ->conCostoMateriales()
            ->orderByDesc('fecha_ingreso')
            ->limit(15)
            ->get();

        $totales = CacheConsultas::recordar(
            CacheConsultas::GRUPO_REPORTES,
            'totales',
            CacheConsultas::TTL_DEFECTO,
            fn (): array => $this->calcularTotales(),
        );

        $resumen = [
            'pendientes' => $pendientes->count(),
            'atrasadas' => $atrasadas->count(),
            'entregadas_mes' => $totales['entregadas_mes'],
            'stock_bajo' => $stockBajo->count(),
            'ingreso_estimado' => $totales['ingreso_estimado'],
            'costo_materiales' => $totales['costo_materiales'],
        ];

        return view('reportes.index', compact('pendientes', 'atrasadas', 'stockBajo', 'ordenesCostos', 'resumen'));
    }

    /**
     * Cifras globales del reporte (una consulta por métrica, en caché).
     *
     * @return array{entregadas_mes: int, ingreso_estimado: float, costo_materiales: float}
     */
    private function calcularTotales(): array
    {
        $entregadasMes = OrdenTrabajo::query()
            ->where('estado', 'entregado')
            ->where('updated_at', '>=', now()->startOfMonth()->toDateTimeString())
            ->where('updated_at', '<', now()->startOfMonth()->addMonth()->toDateTimeString())
            ->count();

        $ingreso = (float) OrdenTrabajo::query()
            ->where('estado', '!=', 'anulado')
            ->sum('precio');

        // Suma calculada por el motor de base de datos (no se transfieren filas).
        $filaCosto = ConsumoMaterial::query()
            ->selectRaw('COALESCE(SUM(cantidad * costo_unitario), 0) AS total')
            ->first();

        return [
            'entregadas_mes' => $entregadasMes,
            'ingreso_estimado' => round($ingreso, 2),
            'costo_materiales' => round((float) ($filaCosto->total ?? 0), 2),
        ];
    }
}
