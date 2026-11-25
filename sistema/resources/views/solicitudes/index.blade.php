{{--
    Bandeja de solicitudes del sitio web.

    Recepción y administración ven aquí cada contacto enviado desde el
    formulario público, filtran por estado o texto y lo marcan como atendido o
    descartado. El envío original entra por POST /api/publico/contacto.
--}}
@extends('layouts.app')

@section('title', 'Solicitudes')
@section('page-title', 'Solicitudes del sitio web')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Solicitudes recibidas</h2>
            <p class="page-subtitle">
                Contactos enviados desde el formulario público.
                @if ($nuevas > 0)
                    <span class="badge bg-primary-subtle text-primary-emphasis ms-1">{{ $nuevas }} sin atender</span>
                @endif
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('solicitudes.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="buscar" class="form-label small text-muted mb-1">Buscar</label>
                    <input type="search" class="form-control" id="buscar" name="buscar" value="{{ $buscar }}"
                           placeholder="Nombre, correo o teléfono">
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label small text-muted mb-1">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        @foreach (\App\Models\SolicitudContacto::ESTADOS as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($estado === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('solicitudes.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Contacto</th>
                        <th>Trabajo solicitado</th>
                        <th>Mensaje</th>
                        <th>Recibida</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitudes as $solicitud)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $solicitud->nombre }}</div>
                                <small class="text-muted d-block">{{ $solicitud->telefono }}</small>
                                @if ($solicitud->email)
                                    <small class="text-muted">{{ $solicitud->email }}</small>
                                @endif
                            </td>
                            <td>{{ $solicitud->servicio ?? 'No especificado' }}</td>
                            <td class="text-wrap" style="max-width: 320px;">{{ $solicitud->mensaje }}</td>
                            <td class="text-nowrap">
                                {{ $solicitud->created_at->timezone(config('sitio.zona_horaria'))->format('d/m/Y H:i') }}
                                @if ($solicitud->responsable)
                                    <small class="d-block text-muted">{{ $solicitud->responsable->name }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $solicitud->estado === 'nueva' ? 'bg-primary-subtle text-primary-emphasis' : ($solicitud->estado === 'atendida' ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis') }}">
                                    {{ $solicitud->estadoNombre() }}
                                </span>
                            </td>
                            <td class="text-end table-actions">
                                @if ($solicitud->estado === 'nueva')
                                    <form method="POST" action="{{ route('solicitudes.atender', $solicitud) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar atendida">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('solicitudes.descartar', $solicitud) }}" class="d-inline"
                                          onsubmit="return confirm('¿Descartar esta solicitud?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Descartar">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">Procesada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay solicitudes con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($solicitudes->hasPages())
            <div class="card-footer bg-white">
                {{ $solicitudes->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
