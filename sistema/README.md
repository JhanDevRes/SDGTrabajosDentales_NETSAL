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
| Reportes | Trabajos pendientes y atrasados, entregas del mes, stock bajo y margen estimado, con gráficos de órdenes por estado, evolución mensual y tipos de trabajo. |
| Panel con indicadores en vivo | Contadores, cumplimiento de plazos y tiempo promedio que se actualizan solos cada minuto sin recargar la página. |
| Solicitudes del sitio web | Bandeja de los contactos recibidos por la API pública, con aviso por correo y marcado de atendida/descartada. |
| Mi cuenta (portal) | Historial completo, totales y datos de contacto del odontólogo autenticado. |
| API pública | Cifras agregadas, seguimiento de una orden por código y recepción del formulario del sitio. |

## Roles y permisos

| Rol | Módulos | Precios de trabajos | Costos y margen | Anular / desactivar |
| --- | --- | --- | --- | --- |
| `administrador` | Todos | Sí | Sí | Sí |
| `recepcion` | Panel, clientes, órdenes, entregas, reportes, solicitudes | Sí | No | No |
| `disenador` | Panel, órdenes | No | No | No |
| `tecnico` | Panel, órdenes, materiales, reportes | No | No | No |
| `odontologo` | Panel, órdenes, mi cuenta (solo sus clientes) | Sí, solo lo suyo | No | No |

La diferencia entre administración y recepción no es solo de menús: recepción registra y
atiende al cliente, mientras que **administración concentra la información económica y las
operaciones que afectan la facturación** (anular una orden, desactivar un cliente, ver costo de
materiales y margen).

Esas reglas viven en el modelo `User` y se aplican en tres lugares a la vez —la vista, el
controlador y el middleware— para que no dependan de que un enlace esté oculto:

```php
public function puedeVerPrecios(): bool  // administrador, recepción y odontólogo
public function puedeVerCostos(): bool   // solo administrador
public function puedeAnular(): bool      // solo administrador
```

La autorización de módulos se aplica con el middleware `role`
([EnsureUserHasRole.php](./app/Http/Middleware/EnsureUserHasRole.php)).

### Acceso rápido de demostración

La pantalla de ingreso muestra botones con las cinco cuentas de prueba para facilitar la
revisión del proyecto. Se controla con una variable de entorno y **debe desactivarse antes de
publicar el sistema**:

```
MOSTRAR_ACCESO_DEMO=false
```

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

La capa de acceso a datos fue optimizada siguiendo las técnicas de la sesión: eager loading,
selección explícita de columnas, query scopes, agregación en la base de datos, índices
compuestos y caché con invalidación automática.

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
│   │   └── Api/            # Endpoints públicos para el sitio web
│   ├── Http/Middleware/    # Autorización por rol y trazado de consultas
│   ├── Mail/               # Avisos por correo
│   ├── Models/             # Modelos Eloquent y relaciones
│   └── Support/            # Caché de consultas versionada
├── config/
│   ├── cors.php            # Orígenes autorizados para la API pública
│   └── sitio.php           # Correo y zona horaria del sitio
├── database/migrations/    # Esquema de la base de datos
├── database/seeders/       # Datos de demostración
├── resources/views/        # Vistas Blade
├── routes/
│   ├── api.php             # API pública (sin sesión ni CSRF)
│   └── web.php             # Rutas del sistema (sesión y CSRF)
└── public/css/             # Estilos del panel
```

## Notas de seguridad

- Consultas parametrizadas mediante Eloquent ORM (previene inyección SQL).
- Escapado automático de salida con Blade (previene XSS).
- Protección CSRF en todos los formularios del sistema.
- Contraseñas almacenadas con hash (`bcrypt`).
- Validación de datos en cada controlador y autorización por módulo/rol.
- La API pública no expone datos de terceros: solo cifras agregadas y la orden consultada.
- La consulta de una orden exige código **y** correo o teléfono del cliente registrado.
- Límite de peticiones por IP: 30/min en consultas y 5/min en el formulario de contacto.
- El formulario de contacto usa un campo trampa y una marca de tiempo para frenar robots.

## API pública

| Método | Ruta | Middleware | Descripción |
| --- | --- | --- | --- |
| `GET` | `/api/publico/estadisticas` | `throttle:publico` | Cifras agregadas del laboratorio en caché de 60 s |
| `GET` | `/api/publico/ordenes/{codigo}` | `throttle:publico` | Estado, avance e historial de una orden |
| `POST` | `/api/publico/contacto` | `throttle:contacto` | Registra la solicitud y avisa por correo |

```bash
curl "http://localhost/Netsal/sistema/public/api/publico/estadisticas"
curl "http://localhost/Netsal/sistema/public/api/publico/ordenes/ORD-20261021-001?contacto=odontologo@netsal.test"
```

Las respuestas usan un sobre uniforme: `{"datos": {...}}` en caso de éxito y
`{"error": {"codigo": "...", "mensaje": "..."}}` en caso de error.

Los orígenes autorizados se configuran con `CORS_ALLOWED_ORIGINS` en `.env`.
