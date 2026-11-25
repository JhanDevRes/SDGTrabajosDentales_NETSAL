<?php

namespace App\Http\Controllers\Api;

use App\Models\OrdenTrabajo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Consulta pública del estado de una orden de trabajo.
 *
 * El odontólogo puede seguir su caso sin iniciar sesión, pero el código por sí
 * solo no basta: es correlativo (OT-0001, OT-0002...) y cualquiera podría
 * recorrerlo. Por eso se exige además el correo o el teléfono con el que el
 * cliente está registrado en el laboratorio.
 *
 * Protecciones:
 *  - Validación de los dos campos con los límites de la tabla.
 *  - Mensaje único para "código inexistente" y "contacto que no coincide":
 *    así la respuesta no confirma si un código existe.
 *  - Límite de peticiones por IP (throttle:publico) para frenar el sondeo.
 *  - Solo se devuelven los datos de esa orden; nunca listados.
 *
 * Optimización: la orden se busca por el índice único de `codigo` y las
 * relaciones se cargan con eager loading (cliente, seguimientos y entrega),
 * de modo que la consulta completa cuesta 4 consultas fijas, no N+1.
 */
class ConsultaOrdenController extends ApiController
{
    public function __invoke(Request $request, string $codigo): JsonResponse
    {
        try {
            $datos = $request->validate([
                'contacto' => ['required', 'string', 'max:150'],
            ], [
                'contacto.required' => 'Ingrese el correo o teléfono registrado.',
            ]);
        } catch (ValidationException $e) {
            return $this->error(
                'datos_invalidos',
                collect($e->errors())->flatten()->first() ?? 'Datos inválidos.',
                422
            );
        }

        $codigo = mb_strtoupper(trim($codigo));

        if ($codigo === '' || mb_strlen($codigo) > 20) {
            return $this->error('datos_invalidos', 'El código de la orden no es válido.', 422);
        }

        $orden = OrdenTrabajo::query()
            ->select([
                'id', 'codigo', 'cliente_id', 'paciente', 'tipo_trabajo', 'estado',
                'fecha_ingreso', 'fecha_entrega', 'created_at', 'updated_at',
            ])
            ->with([
                'cliente:id,nombre,tipo,email,telefono',
                'seguimientos' => fn ($query) => $query
                    ->select(['id', 'orden_trabajo_id', 'estado', 'fecha', 'observacion'])
                    ->reorder('fecha')
                    ->orderBy('id'),
                'entrega:id,orden_trabajo_id,tipo,fecha,receptor,confirmado',
            ])
            ->where('codigo', $codigo)
            ->first();

        if ($orden === null || $orden->cliente === null || ! $orden->cliente->coincideConContacto($datos['contacto'])) {
            return $this->error(
                'no_encontrada',
                'No encontramos una orden con ese código y esos datos de contacto. Verifique la información o comuníquese con el laboratorio.',
                404
            );
        }

        return $this->ok([
            'codigo' => $orden->codigo,
            'estado' => $orden->estado,
            'estado_nombre' => $orden->estadoNombre(),
            'avance' => $orden->avance(),
            'cerrada' => $orden->estaCerrada(),
            'atrasada' => $orden->estaAtrasada(),
            'tipo_trabajo' => $orden->tipo_trabajo,
            'paciente' => $orden->paciente,
            'cliente' => $orden->cliente->nombre,
            'fecha_ingreso' => $orden->fecha_ingreso?->toDateString(),
            'fecha_entrega' => $orden->fecha_entrega?->toDateString(),
            'historial' => $orden->seguimientos->map(fn ($paso) => [
                'estado' => $paso->estado,
                'estado_nombre' => $paso->estadoNombre(),
                'fecha' => $paso->fecha?->toIso8601String(),
                'observacion' => $paso->observacion,
            ])->all(),
            'entrega' => $orden->entrega === null ? null : [
                'tipo' => $orden->entrega->tipo,
                'tipo_nombre' => $orden->entrega->tipoNombre(),
                'fecha' => $orden->entrega->fecha?->toDateString(),
                'receptor' => $orden->entrega->receptor,
                'confirmado' => (bool) $orden->entrega->confirmado,
            ],
        ]);
    }
}
