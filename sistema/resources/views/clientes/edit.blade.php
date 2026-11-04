{{--
    Formulario de edición de un cliente existente.
--}}
@extends('layouts.app')

@section('title', 'Editar cliente')
@section('page-title', 'Editar cliente')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $cliente->nombre }}</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">Datos del cliente</div>
        <div class="card-body">
            <form method="POST" action="{{ route('clientes.update', $cliente) }}">
                @csrf
                @method('PUT')
                @include('clientes._form', ['cliente' => $cliente, 'usuariosPortal' => $usuariosPortal])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Actualizar cliente
                    </button>
                    <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
