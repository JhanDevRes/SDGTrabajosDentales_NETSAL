<?php

namespace App\Models\Concerns;

use App\Support\CacheConsultas;

/**
 * Invalida automáticamente la caché de consultas cuando el modelo cambia.
 *
 * Se engancha a los eventos saved y deleted de Eloquent, de modo que
 * cualquier alta, edición o baja (incluidas las bajas lógicas por campo
 * "activo") obliga a recalcular los indicadores cacheados en la siguiente
 * petición. Evita mostrar contadores desactualizados sin tener que limpiar
 * la caché manualmente.
 */
trait InvalidaCacheConsultas
{
    protected static function bootInvalidaCacheConsultas(): void
    {
        static::saved(fn () => CacheConsultas::invalidar(
            CacheConsultas::GRUPO_PANEL,
            CacheConsultas::GRUPO_REPORTES,
        ));

        static::deleted(fn () => CacheConsultas::invalidar(
            CacheConsultas::GRUPO_PANEL,
            CacheConsultas::GRUPO_REPORTES,
        ));
    }
}
