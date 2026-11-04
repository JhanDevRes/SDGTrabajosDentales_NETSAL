<?php

namespace App\Console\Commands;

use App\Models\OrdenTrabajo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Exporta el costo de materiales por orden a un archivo CSV.
 *
 * Demuestra la técnica de chunking: en lugar de cargar todas las órdenes en
 * memoria con get(), se procesan en lotes de 200 usando chunkById, que además
 * aprovecha la clave primaria para recorrer la tabla de forma estable.
 *
 * Uso:  php artisan netsal:reporte-costos --limite=200
 */
class ReporteCostos extends Command
{
    protected $signature = 'netsal:reporte-costos {--limite=200 : Tamaño del lote de procesamiento}';

    protected $description = 'Genera un CSV con el costo de materiales y el margen por orden (procesamiento por lotes)';

    public function handle(): int
    {
        $tamanoLote = max(1, (int) $this->option('limite'));
        $ruta = 'reportes/costos-'.now()->format('Ymd-His').'.csv';

        $recurso = fopen('php://temp', 'w+');

        if ($recurso === false) {
            $this->error('No se pudo crear el archivo temporal.');

            return self::FAILURE;
        }

        fputcsv($recurso, ['codigo', 'cliente', 'estado', 'precio', 'costo_materiales', 'margen']);

        $procesadas = 0;

        OrdenTrabajo::query()
            ->select(['id', 'codigo', 'cliente_id', 'estado', 'precio'])
            ->with('cliente:id,nombre')
            ->conCostoMateriales()
            ->chunkById($tamanoLote, function ($ordenes) use ($recurso, &$procesadas): void {
                foreach ($ordenes as $orden) {
                    $precio = (float) $orden->precio;
                    $costo = (float) $orden->costo_materiales;

                    fputcsv($recurso, [
                        $orden->codigo,
                        $orden->cliente?->nombre,
                        $orden->estado,
                        number_format($precio, 2, '.', ''),
                        number_format($costo, 2, '.', ''),
                        number_format($precio - $costo, 2, '.', ''),
                    ]);

                    $procesadas++;
                }
            }, 'id');

        rewind($recurso);
        Storage::disk('local')->put($ruta, stream_get_contents($recurso));
        fclose($recurso);

        $this->info("Órdenes procesadas en lotes de {$tamanoLote}: {$procesadas}");
        $this->line('Archivo generado: '.Storage::disk('local')->path($ruta));

        return self::SUCCESS;
    }
}
