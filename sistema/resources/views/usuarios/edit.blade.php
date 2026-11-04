{{--
    Edición de datos, rol y estado de un usuario.
--}}
@extends('layouts.app')

@section('title', 'Editar usuario')
@section('page-title', 'Editar usuario')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $usuario->name }}</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">Datos del usuario</div>
        <div class="card-body">
            <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
                @csrf
                @method('PUT')
                @include('usuarios._form', ['usuario' => $usuario, 'roles' => $roles])

                <div class="alert alert-light border small mt-4 mb-3">
                    Deje los campos de contraseña vacíos para conservar la contraseña actual.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Actualizar usuario
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
