{{--
    Listado de órdenes de trabajo con filtros por estado, cliente y atrasos.
--}}
@extends('layouts.app')

@section('title', 'Órdenes de trabajo')
@section('page-title', 'Órdenes de trabajo')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Producción del laboratorio</h2>
            <p class="page-subtitle">Registro, seguimiento y control de los trabajos dentales.</p>
        </div>
        @unless (auth()->user()->esOdontologo())
            <a href="{{ route('ordenes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Nueva orden
            </a>
        @endunless
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('ordenes.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="q" class="form-label small text-muted mb-1">Buscar</label>
                    <input type="search" class="form-control" id="q" name="q" value="{{ $busqueda }}"
                           placeholder="Código, paciente, trabajo o cliente">
                </div>
                <div class="col-md-3">
                    <label for="estado" class="form-label small text-muted mb-1">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $valor => $nombre)
                            <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @unless (auth()->user()->esOdontologo())
                    <div class="col-md-2">
                        <label for="cliente_id" class="form-label small text-muted mb-1">Cliente</label>
                        <select class="form-select" id="cliente_id" name="cliente_id">
                            <option value="">Todos</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected((string) request('cliente_id') === (string) $cliente->id)>
                                    {{ $cliente->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                <div class="col-md-2">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="atrasadas" id="atrasadas" value="1"
                               {{ request()->boolean('atrasadas') ? 'checked' : '' }}>
                        <label class="form-check-label" for="atrasadas">Solo atrasadas</label>
                    </div>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-outline-primary" title="Filtrar">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Paciente</th>
                        <th>Trabajo</th>
                        <th>Estado</th>
                        <th>Ingreso</th>
                        <th>Entrega</th>
                        @if (auth()->user()->puedeVerPrecios())
                            <th class="text-end">Precio</th>
                        @endif
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ordenes as $orden)
                        <tr>
                            <td>
                                <a href="{{ route('ordenes.show', $orden) }}" class="fw-semibold text-decoration-none text-nowrap">
                                    {{ $orden->codigo }}
                                </a>
                            </td>
                            <td>{{ $orden->cliente->nombre }}</td>
                            <td>{{ $orden->paciente ?? '—' }}</td>
                            <td>{{ $orden->tipo_trabajo }}</td>
                            <td>
                                <span class="badge-estado {{ \App\Models\OrdenTrabajo::badgeClass($orden->estado) }}">
                                    {{ $orden->estadoNombre() }}
                                </span>
                            </td>
                            <td>{{ $orden->fecha_ingreso?->format('d/m/Y') }}</td>
                            <td>
                                {{ $orden->fecha_entrega?->format('d/m/Y') ?? '—' }}
                                @if ($orden->estaAtrasada())
                                    <span class="badge bg-danger ms-1">Atrasada</span>
                                @endif
                            </td>
                            @if (auth()->user()->puedeVerPrecios())
                                <td class="text-end money">S/ {{ number_format((float) $orden->precio, 2) }}</td>
                            @endif
                            <td class="text-end table-actions">
                                <a href="{{ route('ordenes.show', $orden) }}" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @unless (auth()->user()->esOdontologo())
                                    <a href="{{ route('ordenes.edit', $orden) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->puedeVerPrecios() ? 9 : 8 }}" class="text-center text-muted py-4">
                                No se encontraron órdenes con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ordenes->hasPages())
            <div class="card-footer bg-white">
                {{ $ordenes->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
