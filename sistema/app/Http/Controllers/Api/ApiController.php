<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Respuestas comunes de la API pública.
 *
 * Todas las respuestas usan el mismo sobre para que el sitio web pueda
 * interpretarlas sin casos especiales:
 *
 *   éxito → {"datos": {...}}
 *   error → {"error": {"codigo": "...", "mensaje": "..."}}
 *
 * Se envía JSON_UNESCAPED_UNICODE para que los acentos viajen legibles y no
 * como secuencias \uXXXX, lo que facilita depurar con curl o el navegador.
 */
abstract class ApiController extends Controller
{
    protected function ok(array $datos, int $estado = 200): JsonResponse
    {
        return response()->json(
            ['datos' => $datos],
            $estado,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    protected function error(string $codigo, string $mensaje, int $estado = 422): JsonResponse
    {
        return response()->json(
            ['error' => ['codigo' => $codigo, 'mensaje' => $mensaje]],
            $estado,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
}
