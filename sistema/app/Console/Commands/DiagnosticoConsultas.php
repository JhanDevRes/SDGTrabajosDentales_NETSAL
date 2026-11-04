<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Entrega;
use App\Models\Material;
use App\Models\OrdenTrabajo;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Comando de diagnóstico de consultas (herramienta de análisis).
 *
 * Reúne en un solo lugar las tres evidencias que pide la rúbrica:
 *   1. Inventario de índices de las tablas del sistema.
 *   2. Plan de ejecución (EXPLAIN) de las consultas principales.
 *   3. Comparación real del problema N+1 frente a eager loading.
 *
 * Uso:  php artisan netsal:diagnostico
 */
class DiagnosticoConsultas extends Command
{
    protected $signature = 'netsal:diagnostico {--limite=20 : Número de registros usados en la prueba N+1}';

    protected $description = 'Analiza índices, planes de ejecución y el problema N+1 del sistema NETSAL';

    /** Tablas propias del sistema (se excluyen las internas de Laravel). */
    private const TABLAS = [
        'users',
        'clientes',
        'ordenes_trabajo',
        'seguimiento_estados',
        'materiales',
        'consumo_materiales',
        'entregas',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('DIAGNOSTICO DE CONSULTAS - NETSAL');
        $this->line('Base de datos: '.DB::connection()->getDatabaseName());
        $this->line('Motor: '.$this->versionMotor());
        $this->newLine();

        $this->inventarioIndices();
        $this->planesEjecucion();
        $this->compararN1();

        $this->newLine();
        $this->info('Diagnóstico completado. Los resultados se pueden citar en el informe técnico.');

        return self::SUCCESS;
    }

    /** Nombre y versión del motor de base de datos. */
    private function versionMotor(): string
    {
        try {
            $fila = DB::selectOne('SELECT VERSION() AS version');

            return (string) ($fila->version ?? 'desconocido');
        } catch (Throwable) {
            return 'desconocido';
        }
    }

    /** Lista los índices de cada tabla con su tipo y columnas. */
    private function inventarioIndices(): void
    {
        $this->line('1. INVENTARIO DE INDICES');

        $filas = [];

        foreach (self::TABLAS as $tabla) {
            $indices = DB::select("SHOW INDEX FROM `{$tabla}`");
            $agrupados = [];

            foreach ($indices as $indice) {
                $nombre = $indice->Key_name;
                $agrupados[$nombre]['columnas'][(int) $indice->Seq_in_index] = $indice->Column_name;
                $agrupados[$nombre]['unico'] = ((int) $indice->Non_unique) === 0;
            }

            foreach ($agrupados as $nombre => $datos) {
                ksort($datos['columnas']);

                $filas[] = [
                    $tabla,
                    $nombre,
                    implode(', ', $datos['columnas']),
                    $datos['unico'] ? 'UNIQUE' : 'INDEX',
                ];
            }
        }

        $this->table(['Tabla', 'Índice', 'Columnas', 'Tipo'], $filas);
        $this->newLine();
    }

    /**
     * Ejecuta EXPLAIN sobre las consultas más frecuentes del panel.
     * Un "type" distinto de ALL y una columna "key" con valor indican que la
     * consulta está usando un índice.
     */
    private function planesEjecucion(): void
    {
        $this->line('2. PLANES DE EJECUCION (EXPLAIN)');

        $consultas = [
            'Listado de órdenes por estado' => OrdenTrabajo::query()
                ->select(['id', 'codigo', 'cliente_id', 'estado', 'fecha_ingreso'])
                ->enEstado('listo')
                ->orderByDesc('fecha_ingreso')
                ->limit(10),

            'Órdenes pendientes por fecha de entrega' => OrdenTrabajo::query()
                ->select(['id', 'estado', 'fecha_entrega'])
                ->pendientes()
                ->orderBy('fecha_entrega')
                ->limit(10),

            'Órdenes atrasadas' => OrdenTrabajo::query()
                ->select(['id', 'estado', 'fecha_entrega'])
                ->atrasadas()
                ->limit(10),

            'Directorio de clientes activos' => Cliente::query()
                ->select(['id', 'nombre'])
                ->activos()
                ->orderBy('nombre')
                ->limit(10),

            'Inventario con stock bajo' => Material::query()
                ->select(['id', 'nombre', 'stock', 'stock_minimo'])
                ->activos()
                ->stockBajo()
                ->limit(10),

            'Entregas pendientes por fecha' => Entrega::query()
                ->select(['id', 'fecha', 'confirmado'])
                ->pendientes()
                ->orderBy('fecha')
                ->limit(10),
        ];

        $filas = [];

        foreach ($consultas as $titulo => $consulta) {
            foreach ($this->explicar($consulta) as $plan) {
                $filas[] = [
                    $titulo,
                    $plan->type ?? '-',
                    $plan->key ?? 'sin índice',
                    $plan->rows ?? '-',
                    $plan->Extra ?? '-',
                ];
            }
        }

        $this->table(['Consulta', 'type', 'key', 'rows', 'Extra'], $filas);
        $this->newLine();
    }

    /**
     * Devuelve el plan de ejecución de un query builder.
     *
     * @return list<object>
     */
    private function explicar(Builder $consulta): array
    {
        try {
            return DB::select('EXPLAIN '.$consulta->toSql(), $consulta->getBindings());
        } catch (Throwable $e) {
            return [(object) ['type' => 'error', 'key' => null, 'rows' => null, 'Extra' => $e->getMessage()]];
        }
    }

    /**
     * Demuestra el problema N+1 y su solución con eager loading.
     * Se habilita el registro de consultas, se ejecuta el mismo recorrido de
     * dos formas y se comparan los conteos.
     */
    private function compararN1(): void
    {
        $limite = max(1, (int) $this->option('limite'));

        $this->line("3. PROBLEMA N+1 vs EAGER LOADING (sobre {$limite} órdenes)");

        DB::flushQueryLog();
        DB::enableQueryLog();

        $ordenes = OrdenTrabajo::query()->select(['id', 'cliente_id'])->limit($limite)->get();

        foreach ($ordenes as $orden) {
            $orden->cliente?->nombre;
        }

        $sinEager = count(DB::getQueryLog());

        DB::flushQueryLog();

        $ordenes = OrdenTrabajo::query()
            ->select(['id', 'cliente_id'])
            ->with('cliente:id,nombre')
            ->limit($limite)
            ->get();

        foreach ($ordenes as $orden) {
            $orden->cliente?->nombre;
        }

        $conEager = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->table(
            ['Escenario', 'Consultas ejecutadas', 'Observación'],
            [
                ['Sin eager loading (N+1)', $sinEager, '1 consulta base + 1 por cada orden'],
                ['Con eager loading', $conEager, '1 consulta base + 1 consulta de la relación'],
            ]
        );

        if ($sinEager > $conEager) {
            $ahorro = $sinEager - $conEager;
            $this->info("Reducción: {$ahorro} consultas menos (".round(($ahorro / max($sinEager, 1)) * 100).'% menos).');
        }

        $this->newLine();
    }
}
