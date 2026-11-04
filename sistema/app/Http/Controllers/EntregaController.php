<?php

namespace App\Http\Controllers;

use App\Models\Entrega;
use App\Models\OrdenTrabajo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Registro y confirmación de entregas.
 *
 * Optimización aplicada: el listado precarga orden.cliente y usuario con las
 * columnas necesarias. Al confirmar una entrega, la orden pasa a "entregado"
 * y se registra el movimiento en el seguimiento (trazabilidad completa).
 */
class EntregaController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->input('q'));

        $entregas = Entrega::query()
            ->select(['id', 'orden_trabajo_id', 'user_id', 'tipo', 'fecha', 'receptor', 'confirmado'])
            ->with([
                'orden:id,codigo,paciente,cliente_id',
                'orden.cliente:id,nombre',
                'usuario:id,name',
            ])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->whereHas('orden', function ($sub) use ($busqueda) {
                    $sub->where('codigo', 'like', $busqueda.'%')
                        ->orWhere('paciente', 'like', "%{$busqueda}%")
                        ->orWhereHas('cliente', fn ($cliente) => $cliente->where('nombre', 'like', "%{$busqueda}%"));
                });
            })
            ->when($request->filled('tipo'), fn ($query) => $query->deTipo((string) $request->input('tipo')))
            ->when($request->filled('confirmado'), fn ($query) => $query->where('confirmado', $request->boolean('confirmado')))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $tipos = Entrega::TIPOS;

        return view('entregas.index', compact('entregas', 'tipos', 'busqueda'));
    }

    public function create(Request $request): View
    {
        return view('entregas.create', [
            'entrega' => new Entrega([
                'tipo' => 'recojo',
                'fecha' => now()->toDateString(),
                'orden_trabajo_id' => $request->integer('orden'),
            ]),
            'ordenes' => $this->ordenesDisponibles($request->integer('orden')),
            'tipos' => Entrega::TIPOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['user_id'] = $request->user()->id;
        $datos['confirmado'] = $request->boolean('confirmado');

        $entrega = Entrega::create($datos);
        $this->sincronizarEstadoOrden($entrega);

        return redirect()
            ->route('entregas.index')
            ->with('success', 'Entrega registrada correctamente.');
    }

    public function edit(Entrega $entrega): View
    {
        return view('entregas.edit', [
            'entrega' => $entrega,
            'ordenes' => $this->ordenesDisponibles($entrega->orden_trabajo_id),
            'tipos' => Entrega::TIPOS,
        ]);
    }

    public function update(Request $request, Entrega $entrega): RedirectResponse
    {
        $datos = $this->validar($request, $entrega->id);
        $datos['user_id'] = $request->user()->id;
        $datos['confirmado'] = $request->boolean('confirmado');

        $entrega->update($datos);
        $this->sincronizarEstadoOrden($entrega);

        return redirect()
            ->route('entregas.index')
            ->with('success', 'Entrega actualizada correctamente.');
    }

    public function destroy(Entrega $entrega): RedirectResponse
    {
        $entrega->delete();

        return redirect()
            ->route('entregas.index')
            ->with('success', 'Registro de entrega anulado.');
    }

    public function confirmar(Request $request, Entrega $entrega): RedirectResponse
    {
        $entrega->update([
            'confirmado' => true,
            'user_id' => $request->user()->id,
        ]);

        $this->sincronizarEstadoOrden($entrega);

        return back()->with('success', 'Entrega confirmada correctamente.');
    }

    /** Refleja la confirmación de la entrega en el estado de la orden. */
    private function sincronizarEstadoOrden(Entrega $entrega): void
    {
        if (! $entrega->confirmado) {
            return;
        }

        $orden = $entrega->orden;

        if ($orden === null || in_array($orden->estado, ['entregado', 'anulado'], true)) {
            return;
        }

        $orden->update(['estado' => 'entregado']);

        $orden->seguimientos()->create([
            'user_id' => $entrega->user_id,
            'estado' => 'entregado',
            'fecha' => now(),
            'observacion' => 'Entrega confirmada ('.$entrega->tipoNombre().').',
        ]);
    }

    /**
     * Órdenes que aún no tienen entrega y no están anuladas.
     * Se usan whereDoesntHave (subconsulta EXISTS) en lugar de cargar todas
     * las entregas y filtrar en memoria.
     */
    private function ordenesDisponibles(?int $ordenActual = null)
    {
        return OrdenTrabajo::query()
            ->select(['id', 'codigo', 'cliente_id', 'paciente', 'fecha_ingreso'])
            ->with('cliente:id,nombre')
            ->where(function ($query) use ($ordenActual) {
                $query->whereDoesntHave('entrega')
                    ->orWhere('id', $ordenActual);
            })
            ->where(function ($query) use ($ordenActual) {
                $query->where('estado', '!=', 'anulado')
                    ->orWhere('id', $ordenActual);
            })
            ->orderByDesc('fecha_ingreso')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?int $entregaId = null): array
    {
        return $request->validate([
            'orden_trabajo_id' => [
                'required',
                'exists:ordenes_trabajo,id',
                Rule::unique('entregas', 'orden_trabajo_id')->ignore($entregaId),
            ],
            'tipo' => ['required', Rule::in(array_keys(Entrega::TIPOS))],
            'fecha' => ['required', 'date'],
            'receptor' => ['nullable', 'string', 'max:150'],
            'confirmado' => ['sometimes', 'boolean'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ], [
            'orden_trabajo_id.unique' => 'La orden seleccionada ya tiene una entrega registrada.',
        ], [
            'orden_trabajo_id' => 'orden de trabajo',
            'tipo' => 'tipo de entrega',
            'fecha' => 'fecha',
            'receptor' => 'receptor',
            'observacion' => 'observación',
        ]);
    }
}
