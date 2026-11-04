{{--
    Administración de usuarios y roles (solo administrador).
--}}
@extends('layouts.app')

@section('title', 'Usuarios')
@section('page-title', 'Usuarios del sistema')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="page-title">Usuarios y roles</h2>
            <p class="page-subtitle">Cuentas de acceso al sistema y permisos por rol.</p>
        </div>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuevo usuario
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('usuarios.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="q" class="form-label small text-muted mb-1">Buscar</label>
                    <input type="search" class="form-control" id="q" name="q" value="{{ $busqueda }}"
                           placeholder="Nombre o correo">
                </div>
                <div class="col-md-3">
                    <label for="rol" class="form-label small text-muted mb-1">Rol</label>
                    <select class="form-select" id="rol" name="rol">
                        <option value="">Todos los roles</option>
                        @foreach ($roles as $valor => $nombre)
                            <option value="{{ $valor }}" @selected(request('rol') === $valor)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="inactivos" id="inactivos" value="1"
                               {{ request()->boolean('inactivos') ? 'checked' : '' }}>
                        <label class="form-check-label" for="inactivos">Incluir usuarios desactivados</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $usuario->name }}</div>
                                @if ($usuario->is(auth()->user()))
                                    <small class="text-muted">Sesión actual</small>
                                @endif
                            </td>
                            <td>{{ $usuario->email }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $usuario->rolNombre() }}</span></td>
                            <td>
                                @if ($usuario->activo)
                                    <span class="badge bg-success-subtle text-success-emphasis">Activo</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Desactivado</span>
                                @endif
                            </td>
                            <td class="text-end table-actions">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if ($usuario->activo)
                                    <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="d-inline"
                                          onsubmit="return confirm('¿Desactivar este usuario?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar"
                                                @disabled($usuario->is(auth()->user()))>
                                            <i class="bi bi-person-dash"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('usuarios.activar', $usuario) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivar">
                                            <i class="bi bi-person-check"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No se encontraron usuarios con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($usuarios->hasPages())
            <div class="card-footer bg-white">
                {{ $usuarios->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
