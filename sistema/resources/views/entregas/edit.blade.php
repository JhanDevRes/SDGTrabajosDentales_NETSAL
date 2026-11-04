{{--
    Edición y confirmación de una entrega registrada.
--}}
@extends('layouts.app')

@section('title', 'Editar entrega')
@section('page-title', 'Editar entrega')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('entregas.index') }}">Entregas</a></li>
            <li class="breadcrumb-item"><a href="{{ route('ordenes.show', $entrega->orden) }}">{{ $entrega->orden->codigo }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Editar</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">Datos de la entrega</div>
        <div class="card-body">
            <form method="POST" action="{{ route('entregas.update', $entrega) }}">
                @csrf
                @method('PUT')
                @include('entregas._form', ['entrega' => $entrega, 'ordenes' => $ordenes, 'tipos' => $tipos])

                <div class="alert alert-light border small mt-4 mb-3">
                    Al confirmar la entrega, la orden de trabajo pasa automáticamente al estado <strong>Entregado</strong>.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Actualizar entrega
                    </button>
                    <a href="{{ route('entregas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
