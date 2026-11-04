<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Caché de resultados de consultas con invalidación por versión.
 *
 * Problema: al usar Cache::remember con claves fijas es necesario conocer
 * todas las claves para poder invalidarlas cuando cambian los datos.
 *
 * Solución: cada grupo de caché ("panel", "reportes", ...) mantiene un
 * contador de versión. La clave real incluye ese contador; cuando un modelo
 * cambia, se incrementa el contador y todas las claves anteriores quedan
 * obsoletas sin necesidad de borrarlas una por una. Es compatible con
 * cualquier driver de caché (file, database, redis) porque no usa tags.
 *
 * Uso:
 *   $datos = CacheConsultas::recordar('panel', "stats:{$id}", 300, fn () => ...);
 *   CacheConsultas::invalidar(CacheConsultas::GRUPO_PANEL);
 */
class CacheConsultas
{
    /** Grupo de indicadores del panel principal. */
    public const GRUPO_PANEL = 'panel';

    /** Grupo de reportes y estadísticas. */
    public const GRUPO_REPORTES = 'reportes';

    /**
     * Tiempo de vida por defecto de los resultados cacheados (segundos).
     * 300 s = 5 minutos: suficiente para aliviar la base de datos sin
     * mostrar información visiblemente desactualizada.
     */
    public const TTL_DEFECTO = 300;

    /**
     * Devuelve el valor cacheado del grupo o lo calcula y lo almacena.
     *
     * @param  string  $grupo  Grupo lógico usado para la invalidación.
     * @param  string  $clave  Identificador del resultado dentro del grupo.
     * @param  int|null  $segundos  Tiempo de vida; null usa TTL_DEFECTO.
     * @param  Closure(): mixed  $consulta  Consulta a ejecutar si no hay caché.
     */
    public static function recordar(string $grupo, string $clave, ?int $segundos, Closure $consulta): mixed
    {
        $version = self::version($grupo);

        return Cache::remember(
            "{$grupo}:v{$version}:{$clave}",
            $segundos ?? self::TTL_DEFECTO,
            $consulta
        );
    }

    /**
     * Invalida uno o más grupos incrementando su versión.
     * Se llama desde los eventos de los modelos (ver trait InvalidaCacheConsultas).
     */
    public static function invalidar(string ...$grupos): void
    {
        foreach ($grupos as $grupo) {
            Cache::forever(self::claveVersion($grupo), self::version($grupo) + 1);
        }
    }

    /**
     * Versión actual del grupo (1 si nunca se ha invalidado).
     */
    public static function version(string $grupo): int
    {
        return (int) Cache::get(self::claveVersion($grupo), 1);
    }

    private static function claveVersion(string $grupo): string
    {
        return "cache-version:{$grupo}";
    }
}
