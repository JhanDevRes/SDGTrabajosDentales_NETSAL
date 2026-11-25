<?php

namespace App\Http\Controllers;

use App\Models\SolicitudContacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bandeja de solicitudes recibidas desde el sitio web.
 *
 * Recepción y administración atienden aquí los contactos del formulario
 * público. El listado se filtra por estado y por texto, y cada solicitud se
 * marca como atendida o descartada dejando registro de quién lo hizo.
 *
 * Optimización: se pagina en la base (no se cargan todas las filas) y se usan
 * columnas explícitas; el contador de nuevas se resuelve con un único count
 * sobre el índice compuesto (estado, created_at).
 */
class SolicitudContactoController extends Controller
{
    public function index(Request $request): View
    {
        $estado = (string) $request->query('estado', '');
        $buscar = trim((string) $request->query('buscar', ''));

        $solicitudes = SolicitudContacto::query()
            ->select(['id', 'nombre', 'telefono', 'email', 'servicio', 'mensaje', 'estado', 'created_at', 'atendida_por'])
            ->with('responsable:id,name')
            ->when(in_array($estado, array_keys(SolicitudContacto::ESTADOS), true),
                fn ($query) => $query->enEstado($estado))
            ->when($buscar !== '', fn ($query) => $query->buscar($buscar))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $nuevas = SolicitudContacto::query()->nuevas()->count();

        return view('solicitudes.index', compact('solicitudes', 'nuevas', 'estado', 'buscar'));
    }

    /** Marca la solicitud como atendida por el usuario en sesión. */
    public function atender(Request $request, SolicitudContacto $solicitud): RedirectResponse
    {
        if ($solicitud->estado !== 'nueva') {
            return back()->with('error', 'La solicitud ya fue procesada.');
        }

        $solicitud->update([
            'estado' => 'atendida',
            'atendida_por' => $request->user()->id,
            'atendida_en' => now(),
        ]);

        return back()->with('success', 'Solicitud marcada como atendida.');
    }

    /** Marca la solicitud como descartada (spam o consulta duplicada). */
    public function descartar(Request $request, SolicitudContacto $solicitud): RedirectResponse
    {
        if ($solicitud->estado !== 'nueva') {
            return back()->with('error', 'La solicitud ya fue procesada.');
        }

        $solicitud->update([
            'estado' => 'descartada',
            'atendida_por' => $request->user()->id,
            'atendida_en' => now(),
        ]);

        return back()->with('success', 'Solicitud descartada.');
    }
}
