<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inventario de materiales.
 *
 * Optimización aplicada: el listado selecciona solo las columnas visibles y
 * aprovecha los scopes activos(), stockBajo() y buscar() del modelo, que se
 * apoyan en el índice compuesto materiales(activo, nombre).
 */
class MaterialController extends Controller
{
    private const COLUMNAS = ['id', 'nombre', 'unidad', 'costo', 'stock', 'stock_minimo', 'activo'];

    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->input('q'));

        $materiales = Material::query()
            ->select(self::COLUMNAS)
            ->when($busqueda !== '', fn ($query) => $query->buscar($busqueda))
            ->when($request->boolean('stock_bajo'), fn ($query) => $query->stockBajo())
            ->when(! $request->boolean('inactivos'), fn ($query) => $query->activos())
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('materiales.index', compact('materiales', 'busqueda'));
    }

    public function create(): View
    {
        return view('materiales.create', ['material' => new Material(['unidad' => 'unidad', 'activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['activo'] = true;

        Material::create($datos);

        return redirect()->route('materiales.index')->with('success', 'Material registrado correctamente.');
    }

    public function edit(Material $material): View
    {
        return view('materiales.edit', compact('material'));
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['activo'] = $request->boolean('activo');

        $material->update($datos);

        return redirect()->route('materiales.index')->with('success', 'Material actualizado correctamente.');
    }

    /** Baja lógica: el material deja de estar disponible para nuevas órdenes. */
    public function destroy(Material $material): RedirectResponse
    {
        $material->update(['activo' => false]);

        return redirect()->route('materiales.index')->with('success', 'Material desactivado correctamente.');
    }

    public function activar(Material $material): RedirectResponse
    {
        $material->update(['activo' => true]);

        return redirect()->route('materiales.index')->with('success', 'Material reactivado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        $reglas = [
            'nombre' => ['required', 'string', 'max:150'],
            'unidad' => ['required', 'string', 'max:30'],
            'stock' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
        ];

        // El costo unitario es información económica interna: solo administración
        // lo define (ver User::puedeVerCostos()). Si otro rol edita el material,
        // el costo actual se conserva sin tocarse.
        if ($request->user()->puedeVerCostos()) {
            $reglas['costo'] = ['required', 'numeric', 'min:0'];
        }

        $datos = $request->validate($reglas, [], [
            'nombre' => 'nombre',
            'unidad' => 'unidad',
            'costo' => 'costo unitario',
            'stock' => 'stock',
            'stock_minimo' => 'stock mínimo',
        ]);

        if (! $request->user()->puedeVerCostos()) {
            $datos['costo'] = $request->route('material')?->costo ?? 0;
        }

        return $datos;
    }
}
