{{--
    Edición de los datos de una orden de trabajo.
--}}
@extends('layouts.app')

@section('title', 'Editar orden')
@section('page-title', 'Editar orden de trabajo')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('ordenes.index') }}">Órdenes</a></li>
            <li class="breadcrumb-item"><a href="{{ route('ordenes.show', $orden) }}">{{ $orden->codigo }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Editar</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">Datos del trabajo dental</div>
        <div class="card-body">
            <form method="POST" action="{{ route('ordenes.update', $orden) }}">
                @csrf
                @method('PUT')
                @include('ordenes._form', ['orden' => $orden, 'clientes' => $clientes, 'responsables' => $responsables])

                <div class="alert alert-light border small mt-4 mb-3">
                    El estado de producción se actualiza desde el detalle de la orden para conservar el historial de seguimiento.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Actualizar orden
                    </button>
                    <a href="{{ route('ordenes.show', $orden) }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
