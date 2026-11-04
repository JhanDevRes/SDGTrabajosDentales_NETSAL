<?php

namespace App\Http\Controllers;

use App\Models\ConsumoMaterial;
use App\Models\Material;
use App\Models\OrdenTrabajo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Consumo de materiales por orden de trabajo.
 *
 * Cada consumo descuenta stock del material y guarda el costo unitario del
 * momento. Ambas operaciones se ejecutan en una transacción para que el
 * inventario y el histórico nunca queden desalineados.
 */
class ConsumoMaterialController extends Controller
{
    public function store(Request $request, OrdenTrabajo $orden): RedirectResponse
    {
        $datos = $request->validate([
            'material_id' => ['required', 'exists:materiales,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
        ], [], [
            'material_id' => 'material',
            'cantidad' => 'cantidad',
        ]);

        if ($orden->estaCerrada()) {
            return back()->with('error', 'No se puede registrar consumo en una orden cerrada.');
        }

        $material = Material::query()
            ->select(['id', 'nombre', 'unidad', 'costo', 'stock', 'activo'])
            ->findOrFail($datos['material_id']);

        if (! $material->activo) {
            return back()->with('error', 'El material seleccionado está desactivado.');
        }

        if ((float) $material->stock < (float) $datos['cantidad']) {
            return back()->with('error', "Stock insuficiente de {$material->nombre}. Disponible: {$material->stock} {$material->unidad}.");
        }

        DB::transaction(function () use ($orden, $material, $datos) {
            $orden->consumos()->create([
                'material_id' => $material->id,
                'cantidad' => $datos['cantidad'],
                'costo_unitario' => $material->costo,
            ]);

            $material->decrement('stock', $datos['cantidad']);
        });

        return back()->with('success', 'Consumo de material registrado correctamente.');
    }

    /** Elimina el consumo y devuelve la cantidad al inventario. */
    public function destroy(OrdenTrabajo $orden, ConsumoMaterial $consumo): RedirectResponse
    {
        if ($consumo->orden_trabajo_id !== $orden->id) {
            abort(404);
        }

        DB::transaction(function () use ($consumo) {
            $consumo->material->increment('stock', $consumo->cantidad);
            $consumo->delete();
        });

        return back()->with('success', 'Consumo eliminado y stock restaurado.');
    }
}
