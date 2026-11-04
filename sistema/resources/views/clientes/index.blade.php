{{--
    Directorio de clientes. Muestra el conteo de órdenes y permite buscar y desactivar.
--}}
@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Clientes')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Directorio de clientes</h2>
            <p class="page-subtitle">Odontólogos y clínicas que solicitan trabajos al laboratorio.</p>
        </div>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuevo cliente
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('clientes.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="q" class="form-label small text-muted mb-1">Buscar</label>
                    <input type="search" class="form-control" id="q" name="q" value="{{ $busqueda }}"
                           placeholder="Nombre, correo o teléfono">
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="inactivos" id="inactivos" value="1"
                               {{ request()->boolean('inactivos') ? 'checked' : '' }}>
                        <label class="form-check-label" for="inactivos">Incluir clientes desactivados</label>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Contacto</th>
                        <th>Dirección</th>
                        <th class="text-center">Órdenes</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $cliente->nombre }}</div>
                                @if ($cliente->usuario)
                                    <small class="text-muted">Portal: {{ $cliente->usuario->email }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $cliente->tipoNombre() }}</span>
                            </td>
                            <td>
                                <div>{{ $cliente->telefono ?? '—' }}</div>
                                <small class="text-muted">{{ $cliente->email ?? 'Sin correo' }}</small>
                            </td>
                            <td>{{ $cliente->direccion ?? '—' }}</td>
                            <td class="text-center">{{ $cliente->ordenes_count }}</td>
                            <td>
                                @if ($cliente->activo)
                                    <span class="badge bg-success-subtle text-success-emphasis">Activo</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Desactivado</span>
                                @endif
                            </td>
                            <td class="text-end table-actions">
                                <a href="{{ route('ordenes.index', ['cliente_id' => $cliente->id]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Ver órdenes">
                                    <i class="bi bi-clipboard2-pulse"></i>
                                </a>
                                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if ($cliente->activo)
                                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" class="d-inline"
                                          onsubmit="return confirm('¿Desactivar este cliente? Sus órdenes se conservarán.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar">
                                            <i class="bi bi-person-dash"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('clientes.activar', $cliente) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivar">
                                            <i class="bi bi-person-check"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No se encontraron clientes con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($clientes->hasPages())
            <div class="card-footer bg-white">
                {{ $clientes->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
