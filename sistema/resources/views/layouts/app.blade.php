{{--
    Plantilla principal del panel.

    Estructura: barra lateral de navegación, cabecera con el usuario activo y
    área de contenido. Los enlaces del menú se muestran según el rol mediante
    User::puedeVerModulo(), la misma regla que protege las rutas.
--}}
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel') | NETSAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/sistema.css') }}?v={{ filemtime(public_path('css/sistema.css')) }}">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-name">NETSAL</div>
                <span class="brand-sub">Laboratorio Dental · Cusco</span>
            </div>

            <nav class="nav flex-column">
                <span class="nav-section">General</span>

                <a class="nav-link {{ request()->routeIs('panel') ? 'active' : '' }}" href="{{ route('panel') }}">
                    <i class="bi bi-grid-1x2"></i> Panel
                </a>

                @if (auth()->user()->puedeVerModulo('clientes'))
                    <a class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                @endif

                <a class="nav-link {{ request()->routeIs('ordenes.*') ? 'active' : '' }}" href="{{ route('ordenes.index') }}">
                    <i class="bi bi-clipboard-check"></i> Órdenes de trabajo
                </a>

                @if (auth()->user()->puedeVerModulo('materiales'))
                    <a class="nav-link {{ request()->routeIs('materiales.*') ? 'active' : '' }}" href="{{ route('materiales.index') }}">
                        <i class="bi bi-box-seam"></i> Materiales
                    </a>
                @endif

                @if (auth()->user()->puedeVerModulo('entregas'))
                    <a class="nav-link {{ request()->routeIs('entregas.*') ? 'active' : '' }}" href="{{ route('entregas.index') }}">
                        <i class="bi bi-truck"></i> Entregas
                    </a>
                @endif

                @if (auth()->user()->puedeVerModulo('solicitudes'))
                    <a class="nav-link {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}" href="{{ route('solicitudes.index') }}">
                        <i class="bi bi-inbox"></i> Solicitudes
                        @if (($solicitudesNuevas ?? 0) > 0)
                            <span class="badge bg-primary ms-auto">{{ $solicitudesNuevas }}</span>
                        @endif
                    </a>
                @endif

                @if (auth()->user()->puedeVerModulo('reportes'))
                    <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                        <i class="bi bi-bar-chart"></i> Reportes
                    </a>
                @endif

                @if (auth()->user()->puedeVerModulo('mi-cuenta'))
                    <a class="nav-link {{ request()->routeIs('mi-cuenta') ? 'active' : '' }}" href="{{ route('mi-cuenta') }}">
                        <i class="bi bi-person-vcard"></i> Mi cuenta
                    </a>
                @endif

                @if (auth()->user()->puedeVerModulo('usuarios'))
                    <span class="nav-section">Administración</span>

                    <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
                        <i class="bi bi-person-gear"></i> Usuarios
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <a href="{{ url('../../index.html') }}">
                    <i class="bi bi-arrow-up-right-square me-1"></i> Ir al sitio web
                </a>
            </div>
        </aside>

        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <div class="app-main">
            <header class="topbar">
                <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" id="sidebarToggle" aria-label="Abrir menú">
                    <i class="bi bi-list"></i>
                </button>

                <h1 class="page-heading">@yield('page-title', 'Panel')</h1>

                <div class="ms-auto d-flex align-items-center gap-3">
                    <div class="text-end d-none d-sm-block">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role">{{ auth()->user()->rolNombre() }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-right me-1"></i> Salir
                        </button>
                    </form>
                </div>
            </header>

            <main class="content">
                {{-- Mensajes de resultado de las operaciones --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                {{-- Errores de validación devueltos por los formularios --}}
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-semibold mb-1">Revise los datos ingresados:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Control del menú lateral en pantallas pequeñas.
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const toggle = document.getElementById('sidebarToggle');

        if (toggle) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
