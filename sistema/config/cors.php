<?php

/*
|--------------------------------------------------------------------------
| Intercambio de recursos de origen cruzado (CORS)
|--------------------------------------------------------------------------
|
| El sitio web público es un HTML estático servido por Apache desde
| http://localhost/Netsal/ y el sistema Laravel se sirve desde
| http://localhost/Netsal/sistema/public/. Son orígenes distintos, así que el
| navegador aplica la política de mismo origen y bloquea las llamadas a la API
| si el servidor no las autoriza explícitamente.
|
| Solo se habilitan las rutas api/*, los métodos que realmente se usan y los
| orígenes del laboratorio. No se permite el envío de cookies
| (supports_credentials = false): la API pública es anónima y así se evita que
| un sitio externo pueda aprovechar una sesión abierta.
|
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    // Lista separada por comas en .env (CORS_ALLOWED_ORIGINS).
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost,http://127.0.0.1'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'X-Requested-With'],

    // Encabezados de diagnóstico que el sitio puede leer (los usa el modo depuración).
    'exposed_headers' => ['X-Consultas', 'X-Tiempo-Consultas'],

    'max_age' => 3600,

    'supports_credentials' => false,

];
