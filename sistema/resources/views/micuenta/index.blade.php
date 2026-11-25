{{--
    Estado de cuenta del odontólogo o clínica.

    Complementa la consulta pública del sitio: aquí el cliente autenticado ve
    el historial completo de sus trabajos, el resumen económico y los datos de
    sus clínicas. Solo se listan las órdenes de los clientes vinculados a su
    usuario (scopeParaUsuarioPortal).
--}}
@extends('layouts.app')

@section('title', 'Mi cuenta')
@section('page-title', 'Mi cuenta')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Historial de trabajos</h2>
            <p class="page-subtitle">Todos los casos enviados al laboratorio con su estado actual.</p>
        </div>
        <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-clipboard-check me-1"></i> Ver órdenes
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="stat-label">Trabajos totales</div>
                <div class="stat-value">{{ $resumen['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="stat-label">En proceso</div>
                <div class="stat-value">{{ $resumen['en_proceso'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="stat-label">Listos para entrega</div>
                <div class="stat-value">{{ $resumen['listas'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="stat-label">Monto de trabajos</div>
                <div class="stat-value">S/ {{ number_format($resumen['monto'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white fw-semibold">Órdenes</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Trabajo</th>
                        <th>Paciente</th>
                        <th>Ingreso</th>
                        <th>Entrega</th>
                        <th>Estado</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ordenes as $orden)
                        <tr>
                            <td class="fw-semibold">{{ $orden->codigo }}</td>
                            <td>{{ $orden->tipo_trabajo }}</td>
                            <td>{{ $orden->paciente ?? '—' }}</td>
                            <td class="text-nowrap">{{ $orden->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-nowrap">
                                {{ $orden->fecha_entrega?->format('d/m/Y') ?? '—' }}
                                @if ($orden->estaAtrasada())
                                    <span class="badge bg-danger-subtle text-danger-emphasis ms-1">Atrasada</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-estado {{ \App\Models\OrdenTrabajo::badgeClass($orden->estado) }}">{{ $orden->estadoNombre() }}</span>
                            </td>
                            <td class="text-end text-nowrap">S/ {{ number_format((float) $orden->precio, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('ordenes.show', $orden) }}" class="btn btn-sm btn-outline-primary"
                                   title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Aún no tiene trabajos registrados con el laboratorio.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white fw-semibold">Mis datos de contacto</div>
        <div class="card-body">
            @forelse ($clientes as $cliente)
                <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">{{ $cliente->nombre }}</div>
                            <small class="text-muted">{{ $cliente->tipoNombre() }}</small>
                        </div>
                        <div class="text-sm-end">
                            <div>{{ $cliente->telefono ?? 'Sin teléfono' }}</div>
                            <small class="text-muted">{{ $cliente->email ?? 'Sin correo' }}</small>
                        </div>
                    </div>
                    @if ($cliente->direccion)
                        <small class="text-muted d-block mt-1">{{ $cliente->direccion }}</small>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">No hay datos de contacto registrados.</p>
            @endforelse
            <p class="text-muted small mb-0 mt-3">
                Para actualizar estos datos, comuníquese con el laboratorio.
            </p>
        </div>
    </div>
@endsection
