<?php

namespace App\Http\Controllers;

use App\Models\ConsumoMaterial;
use App\Models\Material;
use App\Models\OrdenTrabajo;
use App\Support\CacheConsultas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): View
    {
        // El detalle económico es información de gestión: solo administración.
        $verCostos = $request->user()->puedeVerCostos();

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

        $ordenesCostos = $verCostos
            ? OrdenTrabajo::query()
                ->select(['id', 'codigo', 'cliente_id', 'precio', 'fecha_ingreso'])
                ->with('cliente:id,nombre')
                ->conCostoMateriales()
                ->orderByDesc('fecha_ingreso')
                ->limit(15)
                ->get()
            : collect();

        $totales = $verCostos
            ? CacheConsultas::recordar(
                CacheConsultas::GRUPO_REPORTES,
                'totales',
                CacheConsultas::TTL_DEFECTO,
                fn (): array => $this->calcularTotales(),
            )
            : ['entregadas_mes' => 0, 'ingreso_estimado' => 0.0, 'costo_materiales' => 0.0];

        $resumen = [
            'pendientes' => $pendientes->count(),
            'atrasadas' => $atrasadas->count(),
            'entregadas_mes' => $totales['entregadas_mes'],
            'stock_bajo' => $stockBajo->count(),
            'ingreso_estimado' => $totales['ingreso_estimado'],
            'costo_materiales' => $totales['costo_materiales'],
        ];

        return view('reportes.index', compact('pendientes', 'atrasadas', 'stockBajo', 'ordenesCostos', 'resumen', 'verCostos'));
    }

    /**
     * Series de datos para los gráficos del reporte.
     *
     * Se calculan con GROUP BY en el motor y se devuelven listas ya ordenadas,
     * de modo que el navegador solo dibuja. El bloque económico se omite para
     * los roles que no pueden ver costos.
     */
    public function datos(Request $request): JsonResponse
    {
        $datos = CacheConsultas::recordar(
            CacheConsultas::GRUPO_PUBLICO,
            'graficos-reporte',
            CacheConsultas::TTL_PUBLICO,
            fn (): array => $this->series($request->user()->puedeVerCostos()),
        );

        return response()->json(['datos' => $datos], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    private function series(bool $incluirCostos): array
    {
        $desde = now()->startOfMonth()->subMonths(5);

        /* --- Órdenes por estado (se incluyen los estados sin registros) --- */
        $conteoEstados = OrdenTrabajo::query()
            ->selectRaw('estado, COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $porEstado = [];
        foreach (OrdenTrabajo::ESTADOS as $clave => $etiqueta) {
            $porEstado[] = [
                'estado' => $clave,
                'etiqueta' => $etiqueta,
                'total' => (int) ($conteoEstados[$clave] ?? 0),
            ];
        }

        /* --- Ingresadas y entregadas por mes (6 meses) --- */
        $ingresadas = OrdenTrabajo::query()
            ->where('fecha_ingreso', '>=', $desde->toDateString())
            ->selectRaw("DATE_FORMAT(fecha_ingreso, '%Y-%m') AS mes, COUNT(*) AS total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $entregadas = OrdenTrabajo::query()
            ->where('estado', 'entregado')
            ->where('updated_at', '>=', $desde->copy()->startOfMonth()->toDateTimeString())
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') AS mes, COUNT(*) AS total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        /* --- Tipos de trabajo más solicitados --- */
        $porTipo = OrdenTrabajo::query()
            ->selectRaw('tipo_trabajo AS tipo, COUNT(*) AS total')
            ->groupBy('tipo_trabajo')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($fila) => ['tipo' => $fila->tipo, 'total' => (int) $fila->total])
            ->all();

        /* --- Serie mensual --- */
        $meses = [];
        for ($i = 0; $i < 6; $i++) {
            $mes = $desde->copy()->addMonths($i);
            $clave = $mes->format('Y-m');
            $meses[$clave] = [
                'mes' => $clave,
                'etiqueta' => $mes->translatedFormat('M Y'),
                'ingresadas' => (int) ($ingresadas[$clave] ?? 0),
                'entregadas' => (int) ($entregadas[$clave] ?? 0),
            ];
        }

        $datos = [
            'por_estado' => $porEstado,
            'por_mes' => array_values($meses),
            'por_tipo' => $porTipo,
            'generado_en' => now()->toIso8601String(),
        ];

        if (! $incluirCostos) {
            return $datos;
        }

        /* --- Economía por mes: ingreso facturado y costo de materiales --- */
        $ingresos = OrdenTrabajo::query()
            ->where('estado', '!=', 'anulado')
            ->where('fecha_ingreso', '>=', $desde->toDateString())
            ->selectRaw("DATE_FORMAT(fecha_ingreso, '%Y-%m') AS mes, SUM(precio) AS total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $costos = ConsumoMaterial::query()
            ->join('ordenes_trabajo as o', 'o.id', '=', 'consumo_materiales.orden_trabajo_id')
            ->where('o.fecha_ingreso', '>=', $desde->toDateString())
            ->selectRaw("DATE_FORMAT(o.fecha_ingreso, '%Y-%m') AS mes, SUM(consumo_materiales.cantidad * consumo_materiales.costo_unitario) AS total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $economia = [];
        foreach (array_keys($meses) as $clave) {
            $ingreso = round((float) ($ingresos[$clave] ?? 0), 2);
            $costo = round((float) ($costos[$clave] ?? 0), 2);
            $economia[] = [
                'etiqueta' => $meses[$clave]['etiqueta'],
                'ingreso' => $ingreso,
                'costo' => $costo,
                'margen' => round($ingreso - $costo, 2),
            ];
        }

        $datos['economia'] = $economia;

        return $datos;
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
