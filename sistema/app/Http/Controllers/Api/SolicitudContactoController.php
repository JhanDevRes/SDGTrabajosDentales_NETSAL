<?php

namespace App\Http\Controllers\Api;

use App\Mail\SolicitudContactoRecibida;
use App\Models\SolicitudContacto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Recepción de las solicitudes del formulario público del sitio web.
 *
 * El sitio es estático y no comparte sesión con el sistema, así que no puede
 * enviar un token CSRF de Laravel. Para que el endpoint no se convierta en un
 * formulario abierto de spam se combinan cuatro barreras:
 *
 *  1. throttle:contacto  → 5 envíos por minuto y por IP.
 *  2. Campo trampa       → un input oculto ("sitio_web") que una persona no
 *                          ve ni rellena; si llega con valor, es un robot.
 *  3. Control de tiempo  → el formulario incluye la marca "_t" del momento en
 *                          que se cargó la página; si el envío ocurre en menos
 *                          de 2 segundos, se descarta.
 *  4. Validación estricta de tipos y longitudes, y guardado con Eloquent
 *     (consultas parametrizadas, sin SQL construido a mano).
 *
 * El correo de aviso se envía después de guardar: si el servidor de correo
 * falla, la solicitud ya está en la bandeja del sistema y no se pierde.
 */
class SolicitudContactoController extends ApiController
{
    /** Segundos mínimos entre la carga del formulario y el envío. */
    private const SEGUNDOS_MINIMOS = 2;

    public function __invoke(Request $request): JsonResponse
    {
        // Trampa para robots: se responde con éxito falso para no dar pistas.
        if (filled($request->input('sitio_web'))) {
            return $this->ok(['recibido' => true], 201);
        }

        $cargado = (int) $request->input('_t', 0);
        if ($cargado > 0 && (now()->getTimestampMs() - $cargado) < self::SEGUNDOS_MINIMOS * 1000) {
            return $this->ok(['recibido' => true], 201);
        }

        try {
            $datos = $request->validate([
                'nombre' => ['required', 'string', 'min:3', 'max:150'],
                'telefono' => ['required', 'string', 'min:6', 'max:30'],
                'email' => ['nullable', 'email', 'max:150'],
                'servicio' => ['nullable', 'string', 'max:100'],
                'mensaje' => ['required', 'string', 'min:10', 'max:1000'],
            ], [
                'nombre.required' => 'Ingrese su nombre o el de la clínica.',
                'telefono.required' => 'Ingrese un teléfono de contacto.',
                'mensaje.required' => 'Describa brevemente el caso.',
                'mensaje.min' => 'Describa el caso con al menos 10 caracteres.',
                'email.email' => 'El correo no tiene un formato válido.',
            ]);
        } catch (ValidationException $e) {
            return $this->error(
                'datos_invalidos',
                collect($e->errors())->flatten()->first() ?? 'Datos inválidos.',
                422
            );
        }

        $solicitud = SolicitudContacto::create([
            ...$datos,
            'estado' => 'nueva',
            'origen' => 'sitio',
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        $this->avisar($solicitud);

        return $this->ok([
            'recibido' => true,
            'referencia' => 'SC-'.str_pad((string) $solicitud->id, 4, '0', STR_PAD_LEFT),
        ], 201);
    }

    /** Avisa al laboratorio por correo sin interrumpir la respuesta al usuario. */
    private function avisar(SolicitudContacto $solicitud): void
    {
        $destino = (string) config('sitio.correo_solicitudes');

        if ($destino === '') {
            return;
        }

        try {
            Mail::to($destino)->send(new SolicitudContactoRecibida($solicitud));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el aviso de la solicitud de contacto', [
                'solicitud_id' => $solicitud->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
