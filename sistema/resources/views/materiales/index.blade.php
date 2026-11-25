{{--
    Inventario de materiales con filtros de stock bajo y estado.
--}}
@extends('layouts.app')

@section('title', 'Materiales')
@section('page-title', 'Materiales e inventario')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Inventario del laboratorio</h2>
            <p class="page-subtitle">Materiales, costos unitarios y control de stock mínimo.</p>
        </div>
        <a href="{{ route('materiales.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuevo material
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('materiales.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="q" class="form-label small text-muted mb-1">Buscar</label>
                    <input type="search" class="form-control" id="q" name="q" value="{{ $busqueda }}"
                           placeholder="Nombre del material">
                </div>
                <div class="col-md-5">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="stock_bajo" id="stock_bajo" value="1"
                               {{ request()->boolean('stock_bajo') ? 'checked' : '' }}>
                        <label class="form-check-label" for="stock_bajo">Solo stock bajo</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="inactivos" id="inactivos" value="1"
                               {{ request()->boolean('inactivos') ? 'checked' : '' }}>
                        <label class="form-check-label" for="inactivos">Incluir materiales desactivados</label>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('materiales.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Unidad</th>
                        @if (auth()->user()->puedeVerCostos())
                            <th class="text-end">Costo unitario</th>
                        @endif
                        <th class="text-end">Stock</th>
                        <th class="text-end">Stock mínimo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($materiales as $material)
                        <tr>
                            <td class="fw-semibold">{{ $material->nombre }}</td>
                            <td>{{ $material->unidad }}</td>
                            @if (auth()->user()->puedeVerCostos())
                                <td class="text-end money">S/ {{ number_format((float) $material->costo, 2) }}</td>
                            @endif
                            <td class="text-end">
                                @if ($material->tieneStockBajo())
                                    <span class="badge bg-danger">{{ $material->stock }}</span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis">{{ $material->stock }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $material->stock_minimo }}</td>
                            <td>
                                @if ($material->activo)
                                    <span class="badge bg-success-subtle text-success-emphasis">Activo</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Desactivado</span>
                                @endif
                            </td>
                            <td class="text-end table-actions">
                                <a href="{{ route('materiales.edit', $material) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if ($material->activo)
                                    <form method="POST" action="{{ route('materiales.destroy', $material) }}" class="d-inline"
                                          onsubmit="return confirm('¿Desactivar este material del inventario?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('materiales.activar', $material) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivar">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->puedeVerCostos() ? 7 : 6 }}" class="text-center text-muted py-4">
                                No se encontraron materiales con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($materiales->hasPages())
            <div class="card-footer bg-white">
                {{ $materiales->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
