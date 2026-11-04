{{--
    Formulario de alta de un material del inventario.
--}}
@extends('layouts.app')

@section('title', 'Nuevo material')
@section('page-title', 'Nuevo material')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('materiales.index') }}">Materiales</a></li>
            <li class="breadcrumb-item active" aria-current="page">Nuevo</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">Datos del material</div>
        <div class="card-body">
            <form method="POST" action="{{ route('materiales.store') }}">
                @csrf
                @include('materiales._form', ['material' => $material])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar material
                    </button>
                    <a href="{{ route('materiales.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
