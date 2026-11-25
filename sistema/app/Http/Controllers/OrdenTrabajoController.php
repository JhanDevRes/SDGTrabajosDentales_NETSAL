<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Material;
use App\Models\OrdenTrabajo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión de órdenes de trabajo (crear, listar, ver, editar, anular y cambiar estado).
 *
 * Optimizaciones aplicadas:
 *  1. Listado con columnas explícitas y solo la relación que se muestra
 *     (cliente). Antes se cargaba también "responsable" sin usarlo.
 *  2. Se eliminó el ORDER BY con CASE WHEN, que impedía usar índices y
 *     obligaba a ordenar toda la tabla en memoria.
 *  3. Los filtros usan los scopes del modelo (pendientes, atrasadas, buscar).
 *  4. El selector de clientes y responsables trae solo id y nombre.
 */
class OrdenTrabajoController extends Controller
{
    /** Columnas necesarias para dibujar el listado. */
    private const COLUMNAS_LISTADO = [
        'id',
        'codigo',
        'cliente_id',
        'paciente',
        'tipo_trabajo',
        'estado',
        'fecha_ingreso',
        'fecha_entrega',
        'precio',
    ];

    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->input('q'));
        $user = $request->user();

        $ordenes = OrdenTrabajo::query()
            ->select(self::COLUMNAS_LISTADO)
            ->with('cliente:id,nombre')
            ->when($user->esOdontologo(), fn ($query) => $query->paraUsuarioPortal($user->id))
            ->when($request->filled('estado'), fn ($query) => $query->enEstado((string) $request->input('estado')))
            ->when($request->filled('cliente_id'), fn ($query) => $query->where('cliente_id', $request->integer('cliente_id')))
            ->when($request->boolean('atrasadas'), fn ($query) => $query->atrasadas())
            ->when($busqueda !== '', fn ($query) => $query->buscar($busqueda))
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $estados = OrdenTrabajo::ESTADOS;

        // El odontólogo no necesita el filtro por cliente: solo ve los suyos.
        $clientes = $user->esOdontologo()
            ? collect()
            : Cliente::query()->select(['id', 'nombre'])->orderBy('nombre')->get();

        return view('ordenes.index', compact('ordenes', 'estados', 'clientes', 'busqueda'));
    }

    public function create(Request $request): View
    {
        $clientes = Cliente::query()
            ->select(['id', 'nombre', 'tipo'])
            ->activos()
            ->orderBy('nombre')
            ->get();

        $responsables = $this->responsables();

        $orden = new OrdenTrabajo([
            'fecha_ingreso' => now()->toDateString(),
            'estado' => 'recibido',
            'user_id' => $request->user()->id,
        ]);

        return view('ordenes.create', compact('clientes', 'responsables', 'orden'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['estado'] = 'recibido';

        if (empty($datos['user_id'])) {
            $datos['user_id'] = $request->user()->id;
        }

        // La orden y su primer seguimiento se guardan en una sola transacción.
        $orden = DB::transaction(function () use ($datos, $request) {
            $orden = OrdenTrabajo::create($datos);

            $orden->seguimientos()->create([
                'user_id' => $request->user()->id,
                'estado' => 'recibido',
                'fecha' => now(),
                'observacion' => 'Orden registrada en recepción.',
            ]);

            return $orden;
        });

        return redirect()
            ->route('ordenes.show', $orden)
            ->with('success', "Orden {$orden->codigo} registrada correctamente.");
    }

    public function show(Request $request, OrdenTrabajo $orden): View
    {
        $this->autorizarLectura($request, $orden);

        // Eager loading de todo lo que la vista necesita (evita N+1 en la línea de tiempo y consumos).
        $orden->load([
            'cliente:id,nombre,telefono,email',
            'responsable:id,name,rol',
            'seguimientos' => fn ($query) => $query->select(['id', 'orden_trabajo_id', 'user_id', 'estado', 'fecha', 'observacion']),
            'seguimientos.usuario:id,name',
            'consumos:id,orden_trabajo_id,material_id,cantidad,costo_unitario',
            'consumos.material:id,nombre,unidad',
            'entrega:id,orden_trabajo_id,user_id,tipo,fecha,receptor,confirmado,observacion',
            'entrega.usuario:id,name',
        ]);

        $estadosPermitidos = $request->user()->estadosPermitidos();
        $estados = OrdenTrabajo::ESTADOS;

        $materiales = $request->user()->puedeVerModulo('materiales')
            ? Material::query()
                ->select(['id', 'nombre', 'unidad', 'stock', 'costo'])
                ->activos()
                ->orderBy('nombre')
                ->get()
            : collect();

        return view('ordenes.show', compact('orden', 'estadosPermitidos', 'estados', 'materiales'));
    }

    public function edit(Request $request, OrdenTrabajo $orden): View
    {
        $this->autorizarLectura($request, $orden);

        // El cliente actual se incluye aunque esté inactivo para no perder la referencia.
        $clientes = Cliente::query()
            ->select(['id', 'nombre', 'tipo'])
            ->where(fn ($query) => $query->where('activo', true)->orWhere('id', $orden->cliente_id))
            ->orderBy('nombre')
            ->get();

        $responsables = $this->responsables();

        return view('ordenes.edit', compact('orden', 'clientes', 'responsables'));
    }

    public function update(Request $request, OrdenTrabajo $orden): RedirectResponse
    {
        $orden->update($this->validar($request));

        return redirect()
            ->route('ordenes.show', $orden)
            ->with('success', 'Orden actualizada correctamente.');
    }

    public function destroy(Request $request, OrdenTrabajo $orden): RedirectResponse
    {
        // Anular deja la orden fuera de la facturación: solo administración.
        if (! $request->user()->puedeAnular()) {
            abort(403, 'Solo administración puede anular una orden.');
        }

        if ($orden->estado === 'entregado') {
            return back()->with('error', 'No se puede anular una orden que ya fue entregada.');
        }

        // Baja lógica: la orden se conserva para el historial y los reportes.
        $orden->update(['estado' => 'anulado']);

        $orden->seguimientos()->create([
            'user_id' => $request->user()->id,
            'estado' => 'anulado',
            'fecha' => now(),
            'observacion' => 'Orden anulada.',
        ]);

        return redirect()
            ->route('ordenes.index')
            ->with('success', "Orden {$orden->codigo} anulada.");
    }

    public function cambiarEstado(Request $request, OrdenTrabajo $orden): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(OrdenTrabajo::ESTADOS))],
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // Autorización a nivel de dominio: cada rol avanza solo los estados que le corresponden.
        if (! in_array($datos['estado'], $user->estadosPermitidos(), true)) {
            abort(403, 'Su rol no puede registrar ese estado de producción.');
        }

        if ($orden->estado === 'anulado') {
            return back()->with('error', 'La orden está anulada y no admite cambios de estado.');
        }

        $orden->update(['estado' => $datos['estado']]);

        $orden->seguimientos()->create([
            'user_id' => $user->id,
            'estado' => $datos['estado'],
            'fecha' => now(),
            'observacion' => $datos['observacion'] ?: 'Cambio de estado a '.$orden->estadoNombre().'.',
        ]);

        if ($datos['estado'] === 'entregado' && $orden->entrega !== null) {
            $orden->entrega->update(['confirmado' => true]);
        }

        return back()->with('success', 'Estado actualizado a '.$orden->estadoNombre().'.');
    }

    /**
     * Verifica que un odontólogo solo abra órdenes de sus propios clientes.
     * Se usa exists() (una sola fila) en lugar de count().
     */
    private function autorizarLectura(Request $request, OrdenTrabajo $orden): void
    {
        $user = $request->user();

        if ($user->esOdontologo() && ! $user->clientes()->whereKey($orden->cliente_id)->exists()) {
            abort(403, 'No tiene acceso a esta orden de trabajo.');
        }
    }

    /** Personal que puede figurar como responsable (solo las columnas usadas). */
    private function responsables()
    {
        return User::query()
            ->select(['id', 'name', 'rol'])
            ->responsables()
            ->orderBy('name')
            ->get();
    }

    /**
     * Reglas de validación compartidas por store y update.
     *
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        $reglas = [
            'cliente_id' => ['required', 'exists:clientes,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'paciente' => ['nullable', 'string', 'max:120'],
            'tipo_trabajo' => ['required', 'string', 'max:100'],
            'fecha_ingreso' => ['required', 'date'],
            'fecha_entrega' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];

        // El precio lo definen administración y recepción. Diseño y fresado
        // registran el trabajo sin tocar el importe (ver User::puedeVerPrecios()).
        if ($request->user()->puedeVerPrecios()) {
            $reglas['precio'] = ['required', 'numeric', 'min:0', 'max:999999.99'];
        }

        $datos = $request->validate($reglas, [], [
            'cliente_id' => 'cliente',
            'user_id' => 'responsable',
            'paciente' => 'paciente',
            'tipo_trabajo' => 'tipo de trabajo',
            'fecha_ingreso' => 'fecha de ingreso',
            'fecha_entrega' => 'fecha de entrega',
            'precio' => 'precio',
            'notas' => 'notas',
        ]);

        if (! $request->user()->puedeVerPrecios()) {
            $datos['precio'] = $request->route('orden')?->precio ?? 0;
        }

        return $datos;
    }
}
