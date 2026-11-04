<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registro de consultas
    |--------------------------------------------------------------------------
    |
    | Permite medir el comportamiento de la capa de persistencia durante el
    | desarrollo. En producción debe permanecer desactivado (false) porque
    | añade una pequeña sobrecarga por cada consulta ejecutada.
    |
    */

    'activo' => (bool) env('DB_QUERY_LOG', false),

    /*
    |--------------------------------------------------------------------------
    | Umbral de consulta lenta (milisegundos)
    |--------------------------------------------------------------------------
    |
    | Toda consulta que supere este tiempo se escribe en storage/logs/laravel.log
    | con nivel "warning". El valor sugerido por la cátedra es 100 ms.
    |
    */

    'limite_lento_ms' => (int) env('DB_SLOW_QUERY_MS', 100),

    /*
    |--------------------------------------------------------------------------
    | Encabezados de diagnóstico
    |--------------------------------------------------------------------------
    |
    | Cuando está activo, cada respuesta HTTP incluye X-Consultas y
    | X-Tiempo-Consultas. Son visibles en las herramientas del navegador y
    | sirven como evidencia de la reducción del número de consultas.
    |
    */

    'encabezados' => (bool) env('DB_QUERY_HEADERS', false),

];
