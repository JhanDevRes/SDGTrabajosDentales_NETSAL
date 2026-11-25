<?php

namespace App\Support;

use App\Models\Entrega;

/**
 * Indicadores de cumplimiento de plazos del laboratorio.
 *
 * Se calculan sobre entregas confirmadas de los últimos 180 días comparando la
 * fecha real de entrega con la fecha prevista en la orden. Se aíslan aquí
 * porque los usan tanto las estadísticas públicas del sitio web como el panel
 * del personal, y conviene que ambos midan exactamente lo mismo.
 *
 * Optimización: el cumplimiento y el tiempo medio se obtienen con UNA sola
 * consulta agregada (COUNT, SUM condicional y AVG con DATEDIFF); no se traen
 * filas a PHP.
 */
class Indicadores
{
    /** Días hacia atrás que se consideran para la medición. */
    public const VENTANA_DIAS = 180;

    /**
     * @param  int|null  $userId  Limita el cálculo a los clientes de ese usuario
     *                            (portal del odontólogo). Null = todo el laboratorio.
     * @return array{cumplimiento: float, promedio: float, entregas: int}
     */
    public static function plazos(?int $userId = null): array
    {
        $consulta = Entrega::query()
            ->join('ordenes_trabajo as o', 'o.id', '=', 'entregas.orden_trabajo_id')
            ->where('entregas.confirmado', true)
            ->where('entregas.fecha', '>=', now()->subDays(self::VENTANA_DIAS)->toDateString())
            ->whereNotNull('o.fecha_entrega');

        if ($userId !== null) {
            $consulta->whereIn('o.cliente_id', function ($sub) use ($userId): void {
                $sub->select('id')->from('clientes')->where('user_id', $userId);
            });
        }

        $fila = $consulta
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN entregas.fecha <= o.fecha_entrega THEN 1 ELSE 0 END) AS a_tiempo')
            ->selectRaw('AVG(DATEDIFF(entregas.fecha, o.fecha_ingreso)) AS promedio')
            ->first();

        $total = (int) ($fila->total ?? 0);
        $aTiempo = (int) ($fila->a_tiempo ?? 0);

        return [
            'entregas' => $total,
            'cumplimiento' => $total > 0 ? round($aTiempo * 100 / $total, 1) : 0.0,
            'promedio' => $total > 0 ? round((float) $fila->promedio, 1) : 0.0,
        ];
    }
}
