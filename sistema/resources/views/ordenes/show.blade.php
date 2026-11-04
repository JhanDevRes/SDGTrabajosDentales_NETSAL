{{--
    Detalle de la orden: información, seguimiento de producción, consumos, entrega y resumen económico.
--}}
@extends('layouts.app')

@section('title', $orden->codigo)
@section('page-title', 'Detalle de la orden '.$orden->codigo)

@section('content')
    @php
        $esOdontologo = auth()->user()->esOdontologo();
        $opcionesEstado = collect($estadosPermitidos)->reject(fn ($estado) => $estado === $orden->estado);
        $costoMateriales = $orden->costoMateriales();
    @endphp

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('ordenes.index') }}">Órdenes</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $orden->codigo }}</li>
        </ol>
    </nav>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h2 class="h4 mb-0">{{ $orden->codigo }}</h2>
                        <span class="badge-estado {{ \App\Models\OrdenTrabajo::badgeClass($orden->estado) }}">
                            {{ $orden->estadoNombre() }}
                        </span>
                        @if ($orden->estaAtrasada())
                            <span class="badge bg-danger">Entrega vencida</span>
                        @endif
                    </div>
                    <p class="text-muted mb-0">
                        {{ $orden->tipo_trabajo }} · {{ $orden->cliente->nombre }}
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @unless ($esOdontologo)
                        @if (! $orden->estaCerrada())
                            <a href="{{ route('ordenes.edit', $orden) }}" class="btn btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </a>
                        @endif
                    @endunless

                    @if (auth()->user()->puedeVerModulo('entregas') && ! $orden->entrega && $orden->estado !== 'anulado')
                        <a href="{{ route('entregas.create', ['orden' => $orden->id]) }}" class="btn btn-outline-success">
                            <i class="bi bi-truck me-1"></i> Registrar entrega
                        </a>
                    @endif

                    @if (! $esOdontologo && ! $orden->estaCerrada())
                        <form method="POST" action="{{ route('ordenes.destroy', $orden) }}"
                              onsubmit="return confirm('¿Anular esta orden de trabajo? La acción quedará registrada en el historial.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-x-circle me-1"></i> Anular
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle me-1"></i> Información del trabajo</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small">Cliente</div>
                            <div class="fw-semibold">{{ $orden->cliente->nombre }}</div>
                            <div class="small text-muted">
                                {{ $orden->cliente->telefono ?? 'Sin teléfono' }}
                                @if ($orden->cliente->email)
                                    · {{ $orden->cliente->email }}
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Paciente</div>
                            <div class="fw-semibold">{{ $orden->paciente ?? 'No registrado' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Responsable</div>
                            <div class="fw-semibold">{{ $orden->responsable->name ?? 'Sin asignar' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Precio del trabajo</div>
                            <div class="fw-semibold money">S/ {{ number_format((float) $orden->precio, 2) }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Fecha de ingreso</div>
                            <div class="fw-semibold">{{ $orden->fecha_ingreso?->format('d/m/Y') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Fecha de entrega</div>
                            <div class="fw-semibold">{{ $orden->fecha_entrega?->format('d/m/Y') ?? 'Sin programar' }}</div>
                        </div>
                        @if ($orden->notas)
                            <div class="col-12">
                                <div class="text-muted small">Notas</div>
                                <div>{{ $orden->notas }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-activity me-1"></i> Seguimiento de producción</span>
                    @unless ($esOdontologo)
                        @if ($orden->estado !== 'anulado' && $opcionesEstado->isNotEmpty())
                            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#formEstado" aria-expanded="false" aria-controls="formEstado">
                                <i class="bi bi-arrow-repeat me-1"></i> Actualizar estado
                            </button>
                        @endif
                    @endunless
                </div>

                @unless ($esOdontologo)
                    @if ($orden->estado !== 'anulado' && $opcionesEstado->isNotEmpty())
                        <div class="collapse" id="formEstado">
                            <div class="card-body border-bottom bg-light">
                                <form method="POST" action="{{ route('ordenes.estado', $orden) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-md-5">
                                        <label for="estado" class="form-label small">Nuevo estado</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            @foreach ($opcionesEstado as $estado)
                                                <option value="{{ $estado }}">{{ $estados[$estado] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="observacion" class="form-label small">Observación</label>
                                        <input type="text" class="form-control" id="observacion" name="observacion"
                                               maxlength="500" placeholder="Detalle del avance (opcional)">
                                    </div>
                                    <div class="col-md-2 d-grid">
                                        <button type="submit" class="btn btn-primary">Registrar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                @endunless

                <div class="card-body">
                    <ul class="timeline">
                        @forelse ($orden->seguimientos as $seguimiento)
                            <li>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge-estado {{ \App\Models\OrdenTrabajo::badgeClass($seguimiento->estado) }}">
                                        {{ $seguimiento->estadoNombre() }}
                                    </span>
                                    <small class="text-muted">
                                        {{ $seguimiento->fecha?->format('d/m/Y H:i') }}
                                        · {{ $seguimiento->usuario->name ?? 'Sistema' }}
                                    </small>
                                </div>
                                @if ($seguimiento->observacion)
                                    <div class="small mt-1">{{ $seguimiento->observacion }}</div>
                                @endif
                            </li>
                        @empty
                            <li class="text-muted">Aún no hay movimientos registrados.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            @unless ($esOdontologo)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam me-1"></i> Consumo de materiales</span>
                        <span class="small text-muted">
                            Costo total: <strong>S/ {{ number_format($costoMateriales, 2) }}</strong>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Costo unitario</th>
                                    <th class="text-end">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orden->consumos as $consumo)
                                    <tr>
                                        <td>{{ $consumo->material->nombre ?? 'Material eliminado' }}</td>
                                        <td class="text-end">{{ $consumo->cantidad }} {{ $consumo->material->unidad ?? '' }}</td>
                                        <td class="text-end money">S/ {{ number_format((float) $consumo->costo_unitario, 2) }}</td>
                                        <td class="text-end money">S/ {{ number_format($consumo->subtotal(), 2) }}</td>
                                        <td class="text-end">
                                            @unless ($orden->estaCerrada())
                                                <form method="POST"
                                                      action="{{ route('consumos.destroy', ['orden' => $orden, 'consumo' => $consumo]) }}"
                                                      onsubmit="return confirm('¿Eliminar este consumo y devolver el stock?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No se registró consumo de materiales.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (! $orden->estaCerrada() && $materiales->isNotEmpty())
                        <div class="card-body border-top bg-light">
                            <form method="POST" action="{{ route('consumos.store', $orden) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-7">
                                    <label for="material_id" class="form-label small">Material</label>
                                    <select class="form-select" id="material_id" name="material_id" required>
                                        <option value="">Seleccione un material</option>
                                        @foreach ($materiales as $material)
                                            <option value="{{ $material->id }}">
                                                {{ $material->nombre }} · stock {{ $material->stock }} {{ $material->unidad }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="cantidad" class="form-label small">Cantidad</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="cantidad"
                                           name="cantidad" value="{{ old('cantidad') }}" required>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-primary">Agregar</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endunless
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-truck me-1"></i> Entrega</div>
                <div class="card-body">
                    @if ($orden->entrega)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">{{ $orden->entrega->tipoNombre() }}</span>
                            @if ($orden->entrega->confirmado)
                                <span class="badge bg-success">Confirmada</span>
                            @else
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @endif
                        </div>
                        <div class="small text-muted mb-1">
                            Fecha: {{ $orden->entrega->fecha?->format('d/m/Y') }}
                        </div>
                        <div class="small text-muted">
                            Receptor: {{ $orden->entrega->receptor ?? 'No registrado' }}
                        </div>
                        @if ($orden->entrega->observacion)
                            <div class="small mt-2">{{ $orden->entrega->observacion }}</div>
                        @endif
                        @if (auth()->user()->puedeVerModulo('entregas'))
                            <a href="{{ route('entregas.edit', $orden->entrega) }}" class="btn btn-sm btn-outline-primary mt-3">
                                <i class="bi bi-pencil me-1"></i> Editar entrega
                            </a>
                        @endif
                    @else
                        <p class="text-muted small mb-0">Aún no se registró la entrega de este trabajo.</p>
                    @endif
                </div>
            </div>

            @unless ($esOdontologo)
                <div class="card">
                    <div class="card-header"><i class="bi bi-calculator me-1"></i> Resumen económico</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">Precio del trabajo</span>
                            <span class="money">S/ {{ number_format((float) $orden->precio, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">Costo de materiales</span>
                            <span class="money">S/ {{ number_format($costoMateriales, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-semibold">
                            <span>Margen estimado</span>
                            <span class="money">S/ {{ number_format((float) $orden->precio - $costoMateriales, 2) }}</span>
                        </div>
                    </div>
                </div>
            @endunless
        </div>
    </div>
@endsection
