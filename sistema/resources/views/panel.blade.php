{{--
    Panel principal: indicadores del laboratorio, órdenes recientes y alertas de stock.
--}}
@extends('layouts.app')

@section('title', 'Panel')
@section('page-title', 'Panel principal')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Bienvenido, {{ auth()->user()->name }}</h2>
            <p class="page-subtitle">
                Resumen de la producción del laboratorio · {{ now()->translatedFormat('d \d\e F \d\e Y') }}
            </p>
        </div>
        @unless (auth()->user()->esOdontologo())
            <a href="{{ route('ordenes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Nueva orden
            </a>
        @endunless
    </div>

    <div id="panel-indicadores"
         data-url="{{ route('panel.indicadores') }}"
         data-intervalo="60">

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Trabajos pendientes</div>
                            <div class="stat-value" data-indicador="pendientes" data-formato="entero">{{ $stats['pendientes'] }}</div>
                        </div>
                        <div class="stat-icon bg-soft-primary"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Trabajos atrasados</div>
                            <div class="stat-value text-danger" data-indicador="atrasadas" data-formato="entero">{{ $stats['atrasadas'] }}</div>
                        </div>
                        <div class="stat-icon bg-soft-danger"><i class="bi bi-exclamation-octagon"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Listos para entrega</div>
                            <div class="stat-value text-success" data-indicador="listas" data-formato="entero">{{ $stats['listas'] }}</div>
                        </div>
                        <div class="stat-icon bg-soft-success"><i class="bi bi-check2-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Entregados este mes</div>
                            <div class="stat-value" data-indicador="entregadas_mes" data-formato="entero">{{ $stats['entregadas_mes'] }}</div>
                        </div>
                        <div class="stat-icon bg-soft-info"><i class="bi bi-truck"></i></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Medidores de plazos: se actualizan solos cada minuto --}}
        <div class="card mb-4">
            <div class="card-body indicadores-banda">
                <div class="indicadores-medidor">
                    <div class="stat-label">Cumplimiento de plazos (180 días)</div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="indicadores-barra flex-grow-1">
                            <span id="indicador-barra" style="width: {{ max(0, min(100, $stats['cumplimiento'])) }}%"></span>
                        </div>
                        <strong class="text-nowrap" data-indicador="cumplimiento" data-formato="porcentaje">{{ $stats['entregas_medidas'] > 0 ? $stats['cumplimiento'].'%' : '—' }}</strong>
                    </div>
                    <small class="text-muted">
                        Sobre {{ $stats['entregas_medidas'] }} entrega(s) confirmada(s) en el periodo.
                    </small>
                </div>

                <div>
                    <div class="stat-label">Tiempo promedio de producción</div>
                    <div class="fs-4 fw-semibold" data-indicador="tiempo_promedio" data-formato="dias">{{ $stats['entregas_medidas'] > 0 ? $stats['tiempo_promedio'].' d' : '—' }}</div>
                    <small class="text-muted">Desde el ingreso hasta la entrega.</small>
                </div>

                <div class="indicadores-sello">
                    <span class="indicadores-pulso" id="indicador-pulso" title="Actualización automática cada minuto"></span>
                    <span id="indicador-sello">actualizado ahora</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary no-print" id="indicador-refrescar">
                        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="{{ auth()->user()->puedeVerModulo('materiales') ? 'col-lg-8' : 'col-12' }}">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-1"></i> Órdenes recientes</span>
                    <a href="{{ route('ordenes.index') }}" class="small text-decoration-none">Ver todas</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Trabajo</th>
                                <th>Estado</th>
                                <th>Entrega</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recientes as $orden)
                                <tr>
                                    <td>
                                        <a href="{{ route('ordenes.show', $orden) }}" class="fw-semibold text-decoration-none text-nowrap">
                                            {{ $orden->codigo }}
                                        </a>
                                    </td>
                                    <td>{{ $orden->cliente->nombre }}</td>
                                    <td>{{ $orden->tipo_trabajo }}</td>
                                    <td>
                                        <span class="badge-estado {{ \App\Models\OrdenTrabajo::badgeClass($orden->estado) }}">
                                            {{ $orden->estadoNombre() }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $orden->fecha_entrega?->format('d/m/Y') ?? 'Sin fecha' }}
                                        @if ($orden->estaAtrasada())
                                            <span class="badge bg-danger ms-1">Atrasada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Aún no hay órdenes registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (auth()->user()->puedeVerModulo('materiales'))
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-exclamation-triangle me-1"></i> Stock bajo
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($stockBajo as $material)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $material->nombre }}</div>
                                    <small class="text-muted">Mínimo: {{ $material->stock_minimo }} {{ $material->unidad }}</small>
                                </div>
                                <span class="badge bg-warning text-dark">{{ $material->stock }} {{ $material->unidad }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted small">No hay materiales con stock bajo.</li>
                        @endforelse
                    </ul>
                    <div class="card-footer bg-white text-end">
                        <a href="{{ route('materiales.index') }}" class="small text-decoration-none">Ver inventario</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/panel-indicadores.js') }}?v={{ filemtime(public_path('js/panel-indicadores.js')) }}"></script>
@endpush
