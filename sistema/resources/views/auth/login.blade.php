{{--
    Pantalla de inicio de sesión.

    Distribución en dos columnas: identidad institucional a la izquierda y
    formulario a la derecha. El formulario incluye el token CSRF de Laravel y
    conserva el correo ingresado cuando la validación falla.
--}}
@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="login-page">
        <section class="login-brand-panel">
            <div>
                <div class="brand-name">NETSAL</div>
                <div class="brand-sub">Laboratorio Dental · Cusco</div>
            </div>

            <p class="brand-text">
                Sistema de gestión y seguimiento de trabajos dentales.
                Registro de órdenes, control de producción, inventario y reportes.
            </p>

            <ul class="brand-list">
                <li>Órdenes de trabajo y seguimiento de producción</li>
                <li>Clientes, materiales y control de inventario</li>
                <li>Entregas, costos y reportes de gestión</li>
            </ul>

            <div class="brand-meta">
                Acceso restringido al personal autorizado del laboratorio.
            </div>
        </section>

        <section class="login-form-panel">
            <div class="login-form-wrapper">
                <h2>Iniciar sesión</h2>
                <p class="login-hint">Ingrese sus credenciales institucionales.</p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password"
                               required autocomplete="current-password">
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="remember">Mantener la sesión iniciada</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Ingresar
                    </button>
                </form>

                <p class="text-muted small mt-4 mb-0">
                    &copy; {{ date('Y') }} Laboratorio Dental NETSAL. Todos los derechos reservados.
                </p>
            </div>
        </section>
    </div>
@endsection
