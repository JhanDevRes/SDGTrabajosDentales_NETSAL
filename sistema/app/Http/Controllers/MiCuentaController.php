<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Support\CacheConsultas;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Estado de cuenta del odontólogo o clínica (portal del cliente).
 *
 * Es la vista que respalda la consulta pública del sitio web: el odontólogo
 * puede seguir un caso suelto con su código, pero para ver su historial
 * completo y sus totales debe iniciar sesión. Así los datos económicos y el
 * listado de todos sus pacientes nunca quedan expuestos en una consulta
 * anónima.
 *
 * Optimizaciones aplicadas (semana 11):
 *  1. Los cuatro totales se calculan con UNA consulta de agregación
 *     condicional sobre el ámbito del cliente (scopeParaUsuarioPortal).
 *  2. El historial se trae con columnas explícitas y las relaciones de entrega
 *     y cliente precargadas, evitando el problema N+1.
 *  3. Los totales se cachean por usuario; la caché se invalida sola cuando
 *     cambia una orden o una entrega.
 */
class MiCuentaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $resumen = CacheConsultas::recordar(
            CacheConsultas::GRUPO_PANEL,
            'mi-cuenta:'.$user->id,
            CacheConsultas::TTL_DEFECTO,
            fn (): array => $this->calcularResumen($user->id),
        );

        $ordenes = OrdenTrabajo::query()
            ->select([
                'id', 'codigo', 'cliente_id', 'paciente', 'tipo_trabajo', 'estado',
                'fecha_ingreso', 'fecha_entrega', 'precio',
            ])
            ->with([
                'cliente:id,nombre',
                'entrega:id,orden_trabajo_id,fecha,confirmado,tipo',
            ])
            ->paraUsuarioPortal($user->id)
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $clientes = Cliente::query()
            ->select(['id', 'tipo', 'nombre', 'telefono', 'email', 'direccion'])
            ->where('user_id', $user->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('micuenta.index', compact('resumen', 'ordenes', 'clientes'));
    }

    /**
     * Totales del cliente en una sola consulta.
     *
     * @return array<string, int|float>
     */
    private function calcularResumen(int $userId): array
    {
        $abiertos = OrdenTrabajo::ESTADOS_ABIERTOS;
        $marcadores = implode(',', array_fill(0, count($abiertos), '?'));

        $fila = OrdenTrabajo::query()
            ->paraUsuarioPortal($userId)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw(
                "SUM(CASE WHEN estado IN ({$marcadores}) THEN 1 ELSE 0 END) AS en_proceso",
                $abiertos
            )
            ->selectRaw('SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) AS entregadas', ['entregado'])
            ->selectRaw('SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) AS listas', ['listo'])
            ->selectRaw('SUM(CASE WHEN estado <> ? THEN precio ELSE 0 END) AS monto', ['anulado'])
            ->first();

        return [
            'total' => (int) ($fila->total ?? 0),
            'en_proceso' => (int) ($fila->en_proceso ?? 0),
            'entregadas' => (int) ($fila->entregadas ?? 0),
            'listas' => (int) ($fila->listas ?? 0),
            'monto' => round((float) ($fila->monto ?? 0), 2),
        ];
    }
}
