{{--
    Registro de una nueva entrega para una orden sin entrega previa.
--}}
@extends('layouts.app')

@section('title', 'Nueva entrega')
@section('page-title', 'Nueva entrega')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('entregas.index') }}">Entregas</a></li>
            <li class="breadcrumb-item active" aria-current="page">Nueva</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">Datos de la entrega</div>
        <div class="card-body">
            <form method="POST" action="{{ route('entregas.store') }}">
                @csrf
                @include('entregas._form', ['entrega' => $entrega, 'ordenes' => $ordenes, 'tipos' => $tipos])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar entrega
                    </button>
                    <a href="{{ route('entregas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
