# Sistema web de gestión y seguimiento de trabajos dentales — NETSAL

Aplicación desarrollada en **Laravel 12** con arquitectura **MVC**, base de datos relacional y vistas **Blade** con Bootstrap 5. Forma parte del proyecto del curso de Programación Web para el Laboratorio Dental NETSAL (Cusco).

## Autores

| Integrante | Correo institucional |
| --- | --- |
| Vargas Cusi Jhande Emanuel | 76553002@continental.edu.pe |
| Cortez Huaman Mehul Dhruval | 61202602@continental.edu.pe |
| Luna Urquizo Javier Enrique | 61057682@continental.edu.pe |

**Curso:** Programación Web — Universidad Continental
**Docente:** Mg. Alfredo Collantes Mendoza

## Módulos implementados

| Módulo | Descripción |
| --- | --- |
| Autenticación y roles | Inicio y cierre de sesión con contraseñas cifradas y autorización por rol. |
| Usuarios | Alta, edición y desactivación de cuentas; asignación de rol (solo administrador). |
| Clientes | Registro de odontólogos y clínicas, búsqueda, edición y desactivación lógica. |
| Órdenes de trabajo | Registro, edición, anulación, código automático y fechas de ingreso/entrega. |
| Seguimiento de producción | Historial de estados: recibido, diseño, fresado, horno/acabado, control, listo y entregado. |
| Materiales e inventario | Costo unitario, stock, stock mínimo y alertas de reposición. |
| Consumo de materiales | Descuento automático de stock por orden y cálculo del costo del trabajo. |
| Entregas | Recojo o envío, confirmación y actualización automática del estado de la orden. |
| Reportes | Trabajos pendientes y atrasados, entregas del mes, stock bajo y margen estimado. |

## Roles y permisos

| Rol | Acceso |
| --- | --- |
| `administrador` | Todos los módulos, incluidos costos, reportes y anulación de órdenes. |
| `recepcion` | Clientes, órdenes, entregas y reportes. |
| `disenador` | Órdenes y actualización de estados de diseño. |
| `tecnico` | Órdenes, materiales, consumo y reportes. |
| `odontologo` | Consulta únicamente las órdenes de los clientes vinculados a su usuario. |

La autorización se aplica con el middleware `role` ([EnsureUserHasRole.php](./app/Http/Middleware/EnsureUserHasRole.php)).

## Modelo de datos

| Tabla | Contenido |
| --- | --- |
| `users` | Usuarios del sistema con `rol` y estado `activo`. |
| `clientes` | Odontólogos y clínicas; opcionalmente vinculados a un usuario del portal. |
| `ordenes_trabajo` | Orden con código único, cliente, paciente, tipo, estado, fechas y precio. |
| `seguimiento_estados` | Historial de estados con fecha, usuario y observación. |
| `materiales` | Inventario con costo, stock y stock mínimo. |
| `consumo_materiales` | Materiales usados por orden con costo unitario histórico. |
| `entregas` | Una entrega por orden: tipo, fecha, receptor y confirmación. |

Las migraciones están en [database/migrations](./database/migrations).

## Optimización de consultas (semana 11)

La capa de acceso a datos fue optimizada siguiendo las técnicas de la sesión. El detalle
completo, con código comparado y mediciones, está en
[docs/OPTIMIZACION_CONSULTAS.md](./docs/OPTIMIZACION_CONSULTAS.md) (también en Word:
`docs/OPTIMIZACION_CONSULTAS.docx`).

| Técnica | Dónde se aplica |
| --- | --- |
| Eager loading | `ClienteController`, `EntregaController`, `OrdenTrabajoController@show` |
| Selección de columnas | Todos los listados y catálogos de formularios |
| Query scopes | `app/Models` (pendientes, atrasadas, buscar, conCostoMateriales, ...) |
| Agregación condicional | `DashboardController` (4 contadores en 1 consulta) |
| Subconsulta correlacionada | `OrdenTrabajo::scopeConCostoMateriales` |
| Caché con invalidación | `app/Support/CacheConsultas.php` + trait `InvalidaCacheConsultas` |
| Índices compuestos | `2026_11_03_000000_add_indexes_for_query_optimization.php` |
| Procesamiento por lotes | `php artisan netsal:reporte-costos` |

### Herramientas de medición

```bash
php artisan netsal:diagnostico     # índices, EXPLAIN y comparación N+1
php artisan netsal:reporte-costos  # CSV de costos procesado por lotes
```

Con `DB_QUERY_HEADERS=true` en `.env`, cada respuesta incluye los encabezados
`X-Consultas` y `X-Tiempo-Consultas`. Las consultas que superan `DB_SLOW_QUERY_MS`
se registran en `storage/logs/laravel.log`.

## Puesta en marcha

Requisitos: PHP 8.2+, Composer y **MySQL/MariaDB** (por ejemplo, el MySQL de XAMPP).

1. Inicie MySQL y cree la base de datos:

```sql
CREATE DATABASE NetsalDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Configure y ejecute el sistema:

```bash
cd sistema
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

La conexión se configura en `.env` (`DB_CONNECTION=mysql`, `DB_DATABASE=NetsalDB`, usuario `root` sin contraseña por defecto en XAMPP). El sistema queda disponible en `http://127.0.0.1:8000`.

### Usuarios de demostración

| Correo | Contraseña | Rol |
| --- | --- | --- |
| `admin@netsal.test` | `password` | Administrador |
| `recepcion@netsal.test` | `password` | Recepción |
| `diseno@netsal.test` | `password` | Técnico de diseño |
| `tecnico@netsal.test` | `password` | Técnico de fresado y acabados |
| `odontologo@netsal.test` | `password` | Odontólogo (portal) |

> Cambie estas credenciales antes de cualquier despliegue real.

## Estructura del proyecto

```
sistema/
├── app/
│   ├── Http/Controllers/   # Controladores por módulo
│   ├── Http/Middleware/    # Autorización por rol
│   └── Models/             # Modelos Eloquent y relaciones
├── database/migrations/    # Esquema de la base de datos
├── database/seeders/       # Datos de demostración
├── resources/views/        # Vistas Blade
├── routes/web.php          # Rutas del sistema
└── public/css/             # Estilos del panel
```

## Notas de seguridad

- Consultas parametrizadas mediante Eloquent ORM (previene inyección SQL).
- Escapado automático de salida con Blade (previene XSS).
- Protección CSRF en todos los formularios.
- Contraseñas almacenadas con hash (`bcrypt`).
- Validación de datos en cada controlador y autorización por módulo/rol.
