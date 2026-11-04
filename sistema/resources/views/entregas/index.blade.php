{{--
    Registro de entregas con filtros por tipo y estado de confirmación.
--}}
@extends('layouts.app')

@section('title', 'Entregas')
@section('page-title', 'Entregas')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Registro de entregas</h2>
            <p class="page-subtitle">Recojos en laboratorio y envíos a los clientes.</p>
        </div>
        <a href="{{ route('entregas.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nueva entrega
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('entregas.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="q" class="form-label small text-muted mb-1">Buscar</label>
                    <input type="search" class="form-control" id="q" name="q" value="{{ $busqueda }}"
                           placeholder="Código, paciente o cliente">
                </div>
                <div class="col-md-3">
                    <label for="tipo" class="form-label small text-muted mb-1">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo">
                        <option value="">Todos</option>
                        @foreach ($tipos as $valor => $nombre)
                            <option value="{{ $valor }}" @selected(request('tipo') === $valor)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="confirmado" class="form-label small text-muted mb-1">Confirmación</label>
                    <select class="form-select" id="confirmado" name="confirmado">
                        <option value="">Todas</option>
                        <option value="1" @selected(request('confirmado') === '1')>Confirmadas</option>
                        <option value="0" @selected(request('confirmado') === '0')>Pendientes</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('entregas.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Receptor</th>
                        <th>Registrado por</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entregas as $entrega)
                        <tr>
                            <td>
                                <a href="{{ route('ordenes.show', $entrega->orden) }}" class="fw-semibold text-decoration-none text-nowrap">
                                    {{ $entrega->orden->codigo }}
                                </a>
                                <div class="small text-muted">{{ $entrega->orden->paciente ?? 'Sin paciente' }}</div>
                            </td>
                            <td>{{ $entrega->orden->cliente->nombre }}</td>
                            <td>{{ $entrega->tipoNombre() }}</td>
                            <td>{{ $entrega->fecha?->format('d/m/Y') }}</td>
                            <td>{{ $entrega->receptor ?? '—' }}</td>
                            <td>{{ $entrega->usuario->name ?? '—' }}</td>
                            <td>
                                @if ($entrega->confirmado)
                                    <span class="badge bg-success">Confirmada</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-end table-actions">
                                @unless ($entrega->confirmado)
                                    <form method="POST" action="{{ route('entregas.confirmar', $entrega) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Confirmar entrega">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </form>
                                @endunless
                                <a href="{{ route('entregas.edit', $entrega) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('entregas.destroy', $entrega) }}" class="d-inline"
                                      onsubmit="return confirm('¿Anular este registro de entrega?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Anular">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No se encontraron entregas con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entregas->hasPages())
            <div class="card-footer bg-white">
                {{ $entregas->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
