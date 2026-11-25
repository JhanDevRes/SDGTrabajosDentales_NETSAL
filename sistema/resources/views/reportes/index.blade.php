{{--
    Reportes de producción y económicos, con opción de impresión.
--}}
@extends('layouts.app')

@section('title', 'Reportes')
@section('page-title', 'Reportes')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Indicadores del laboratorio</h2>
            <p class="page-subtitle">Trabajos pendientes, atrasados, entregas, costos y stock bajo.</p>
        </div>
        <button type="button" class="btn btn-outline-secondary no-print" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Imprimir reporte
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-value">{{ $resumen['pendientes'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label">Atrasados</div>
                    <div class="stat-value text-danger">{{ $resumen['atrasadas'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label">Entregados este mes</div>
                    <div class="stat-value text-success">{{ $resumen['entregadas_mes'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label">Materiales con stock bajo</div>
                    <div class="stat-value text-warning">{{ $resumen['stock_bajo'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Gráficos: series calculadas con GROUP BY en MySQL ===== --}}
    <div id="reportes-graficos" data-url="{{ route('reportes.datos') }}">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h3 class="h6 text-uppercase text-muted fw-bold mb-0" style="letter-spacing: .06em;">Estadísticas del periodo</h3>
            <div class="d-flex align-items-center gap-2 no-print">
                <small class="text-muted" id="graficos-sello"></small>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="graficos-refrescar">
                    <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="card grafico-card">
                    <div class="card-header">Órdenes por estado</div>
                    <div class="card-body">
                        <div class="grafico-lienzo">
                            <canvas id="grafico-estados" aria-label="Distribución de órdenes por estado" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card grafico-card">
                    <div class="card-header">Ingresadas y entregadas por mes</div>
                    <div class="card-body">
                        <div class="grafico-lienzo">
                            <canvas id="grafico-meses" aria-label="Órdenes ingresadas y entregadas por mes" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card grafico-card">
                    <div class="card-header">Tipos de trabajo más solicitados</div>
                    <div class="card-body">
                        <div class="grafico-lienzo chico">
                            <canvas id="grafico-tipos" aria-label="Tipos de trabajo más solicitados" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            @if ($verCostos)
                <div class="col-lg-7">
                    <div class="card grafico-card">
                        <div class="card-header">Ingreso y costo de materiales por mes</div>
                        <div class="card-body">
                            <div class="grafico-lienzo chico">
                                <canvas id="grafico-economia" aria-label="Ingreso facturado y costo de materiales por mes" role="img"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="col-lg-7">
                    <div class="card grafico-card">
                        <div class="card-header">Alcance de su perfil</div>
                        <div class="card-body">
                            <p class="mb-2 small">
                                Este reporte muestra la producción del laboratorio. La información
                                económica (ingreso facturado, costo de materiales y margen) está
                                reservada a administración.
                            </p>
                            <p class="mb-0 small text-muted">
                                Si necesita una cifra económica para su trabajo, solicítela a
                                administración.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($verCostos)
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-cash-coin me-1"></i> Resumen económico</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Ingreso estimado (órdenes no anuladas)</div>
                    <div class="fs-4 fw-bold money">S/ {{ number_format($resumen['ingreso_estimado'], 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Costo de materiales consumidos</div>
                    <div class="fs-4 fw-bold money">S/ {{ number_format($resumen['costo_materiales'], 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Margen bruto estimado</div>
                    <div class="fs-4 fw-bold money text-success">
                        S/ {{ number_format($resumen['ingreso_estimado'] - $resumen['costo_materiales'], 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header text-danger">
                    <i class="bi bi-exclamation-octagon me-1"></i> Trabajos atrasados ({{ $atrasadas->count() }})
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Entrega</th>
                                <th class="text-end">Días de atraso</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($atrasadas as $orden)
                                <tr>
                                    <td>
                                        <a href="{{ route('ordenes.show', $orden) }}" class="fw-semibold text-decoration-none text-nowrap">
                                            {{ $orden->codigo }}
                                        </a>
                                    </td>
                                    <td>{{ $orden->cliente->nombre }}</td>
                                    <td>{{ $orden->fecha_entrega?->format('d/m/Y') }}</td>
                                    <td class="text-end">
                                        {{ abs((int) $orden->fecha_entrega->copy()->startOfDay()->diffInDays(now()->startOfDay())) }}
                                    </td>
                                    <td>
                                        <span class="badge-estado {{ \App\Models\OrdenTrabajo::badgeClass($orden->estado) }}">
                                            {{ $orden->estadoNombre() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No hay trabajos atrasados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-hourglass-split me-1"></i> Trabajos pendientes ({{ $pendientes->count() }})
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Trabajo</th>
                                <th>Entrega</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendientes as $orden)
                                <tr>
                                    <td>
                                        <a href="{{ route('ordenes.show', $orden) }}" class="fw-semibold text-decoration-none text-nowrap">
                                            {{ $orden->codigo }}
                                        </a>
                                    </td>
                                    <td>{{ $orden->cliente->nombre }}</td>
                                    <td>{{ $orden->tipo_trabajo }}</td>
                                    <td>{{ $orden->fecha_entrega?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                                    <td>
                                        <span class="badge-estado {{ \App\Models\OrdenTrabajo::badgeClass($orden->estado) }}">
                                            {{ $orden->estadoNombre() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No hay trabajos pendientes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header text-warning">
                    <i class="bi bi-box-seam me-1"></i> Materiales con stock bajo
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th class="text-end">Stock</th>
                                <th class="text-end">Mínimo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockBajo as $material)
                                <tr>
                                    <td>{{ $material->nombre }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-danger">{{ $material->stock }} {{ $material->unidad }}</span>
                                    </td>
                                    <td class="text-end">{{ $material->stock_minimo }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Todo el inventario está sobre el mínimo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($verCostos)
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-calculator me-1"></i> Costos recientes por trabajo
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Costo</th>
                                <th class="text-end">Margen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ordenesCostos as $orden)
                                @php($costo = (float) $orden->costo_materiales)
                                <tr>
                                    <td>
                                        <a href="{{ route('ordenes.show', $orden) }}" class="text-decoration-none">
                                            {{ $orden->codigo }}
                                        </a>
                                        <div class="small text-muted">{{ $orden->cliente->nombre }}</div>
                                    </td>
                                    <td class="text-end money">S/ {{ number_format((float) $orden->precio, 2) }}</td>
                                    <td class="text-end money">S/ {{ number_format($costo, 2) }}</td>
                                    <td class="text-end money {{ (float) $orden->precio - $costo < 0 ? 'text-danger' : 'text-success' }}">
                                        S/ {{ number_format((float) $orden->precio - $costo, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Sin datos de costos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/reportes-graficos.js') }}?v={{ filemtime(public_path('js/reportes-graficos.js')) }}"></script>
@endpush
