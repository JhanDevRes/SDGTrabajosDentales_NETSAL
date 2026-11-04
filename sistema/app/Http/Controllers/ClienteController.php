<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Directorio de clientes (odontólogos y clínicas).
 *
 * Optimización aplicada: el listado precarga la relación "usuario" que la
 * vista usa para mostrar el correo del portal. Antes, cada fila disparaba una
 * consulta adicional (problema N+1). Además se seleccionan solo las columnas
 * visibles y se usa withCount para el total de órdenes.
 */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->input('q'));

        $clientes = Cliente::query()
            ->select(['id', 'tipo', 'nombre', 'telefono', 'email', 'direccion', 'user_id', 'activo'])
            ->with('usuario:id,email')
            ->withCount('ordenes')
            ->when($busqueda !== '', fn ($query) => $query->buscar($busqueda))
            ->when(! $request->boolean('inactivos'), fn ($query) => $query->activos())
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'busqueda'));
    }

    public function create(): View
    {
        return view('clientes.create', [
            'cliente' => new Cliente(['tipo' => 'odontologo', 'activo' => true]),
            'usuariosPortal' => $this->usuariosPortal(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['activo'] = true;

        Cliente::create($datos);

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', [
            'cliente' => $cliente,
            'usuariosPortal' => $this->usuariosPortal(),
        ]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['activo'] = $request->boolean('activo');

        $cliente->update($datos);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    /** Baja lógica: conserva las órdenes y el historial del cliente. */
    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->update(['activo' => false]);

        return redirect()->route('clientes.index')->with('success', 'Cliente desactivado. Sus órdenes se conservan en el historial.');
    }

    public function activar(Cliente $cliente): RedirectResponse
    {
        $cliente->update(['activo' => true]);

        return redirect()->route('clientes.index')->with('success', 'Cliente reactivado correctamente.');
    }

    /**
     * Usuarios del portal disponibles para vincular al cliente.
     * Se seleccionan solo las columnas que muestra el selector.
     */
    private function usuariosPortal()
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->delPortal()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', 'in:odontologo,clinica'],
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'user_id' => ['nullable', 'exists:users,id'],
        ], [], [
            'tipo' => 'tipo de cliente',
            'nombre' => 'nombre',
            'telefono' => 'teléfono',
            'email' => 'correo',
            'direccion' => 'dirección',
            'user_id' => 'usuario del portal',
        ]);
    }
}
