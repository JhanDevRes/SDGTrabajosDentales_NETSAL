{{--
    Registro de una nueva orden de trabajo.
--}}
@extends('layouts.app')

@section('title', 'Nueva orden')
@section('page-title', 'Nueva orden de trabajo')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('ordenes.index') }}">Órdenes</a></li>
            <li class="breadcrumb-item active" aria-current="page">Nueva</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">Datos del trabajo dental</div>
        <div class="card-body">
            <form method="POST" action="{{ route('ordenes.store') }}">
                @csrf
                @include('ordenes._form', ['orden' => $orden, 'clientes' => $clientes, 'responsables' => $responsables])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Registrar orden
                    </button>
                    <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
