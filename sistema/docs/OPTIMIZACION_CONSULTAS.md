# Optimización de consultas en el sistema NETSAL

**Asignatura:** Programación Web
**Unidad 3:** Optimización de consulta CRUD en el framework
**Semana 11:** Herramientas y técnicas de optimización de consultas en Laravel
**Docente:** Mg. Alfredo Collantes Mendoza
**Proyecto:** Sistema web de gestión y seguimiento de trabajos dentales — Laboratorio Dental NETSAL (Cusco)
**Integrantes:** Vargas Cusi Jhande Emanuel · Cortez Huaman Mehul Dhruval · Luna Urquizo Javier Enrique

---

## 1. Resumen

Este documento describe el proceso de optimización de la capa de persistencia del sistema
NETSAL, desarrollado con Laravel 12, Eloquent y MySQL/MariaDB. Se partió de una aplicación
funcional pero con consultas redundantes: contadores calculados con cuatro consultas
separadas, listados que provocaban el problema N+1, sumas de costos resueltas en PHP y una
tabla de órdenes sin índices para sus filtros principales.

Las técnicas aplicadas fueron: _eager loading_, selección explícita de columnas, _query
scopes_, agregación condicional en SQL, subconsultas correlacionadas, reescritura de
predicados, caché de resultados con invalidación automática y creación de índices
compuestos. Además se incorporaron herramientas de medición propias del proyecto (un
middleware de conteo de consultas y un comando de diagnóstico con EXPLAIN) que permiten
verificar el comportamiento antes y después de cada cambio.

El resultado principal: en una prueba con 305 órdenes, el listado de clientes pasó de 12 a 3
consultas por página, los indicadores del panel de 4 a 1, y el cálculo del costo total de
materiales de 5.88 ms a 0.54 ms, sin modificar la funcionalidad ni la apariencia del sistema.

---

## 2. Introducción

### 2.1 Contexto

El laboratorio dental NETSAL registra órdenes de trabajo, controla su avance por etapas de
producción (recibido, diseño, fresado, horno/acabado, control, listo y entregado), administra
un inventario de materiales y registra entregas. Toda esa información se consulta a diario
desde el panel administrativo.

A medida que crece el número de órdenes, las consultas mal construidas se vuelven el
principal cuello de botella: cada página puede ejecutar decenas de consultas pequeñas,
transferir filas que no se muestran y recorrer tablas completas sin índices.

### 2.2 Objetivo general

Aplicar técnicas de optimización de consultas en el CRUD del sistema NETSAL y demostrar su
efectividad mediante mediciones antes/después.

### 2.3 Objetivos específicos

1. Identificar consultas redundantes y casos de N+1 en los módulos del sistema.
2. Reescribir las consultas usando las capacidades de Eloquent y de MySQL.
3. Crear índices alineados con los filtros y ordenamientos reales del panel.
4. Incorporar caché de resultados con invalidación automática.
5. Construir herramientas de medición reproducibles (EXPLAIN, conteo y registro de consultas).
6. Documentar cada técnica con su código y su efecto medido.

### 2.4 Alcance

Se optimizaron los módulos de panel principal, clientes, órdenes de trabajo, materiales,
entregas y reportes. No se modificó el modelo de datos funcional ni las reglas de negocio; los
cambios son de acceso a datos, de esquema (índices) y de instrumentación.

---

## 3. Arquitectura del sistema

El sistema mantiene el patrón MVC solicitado en la consigna:

| Capa | Implementación | Ubicación |
| --- | --- | --- |
| Modelo | Eloquent, relaciones, scopes y reglas de negocio | `app/Models` |
| Vista | Plantillas Blade con Bootstrap 5 | `resources/views` |
| Controlador | Controladores por módulo, validación y autorización | `app/Http/Controllers` |
| Rutas y seguridad | Rutas con middleware de sesión y de rol | `routes/web.php`, `app/Http/Middleware` |
| Base de datos | MySQL/MariaDB con migraciones versionadas | `database/migrations` |

Tablas del sistema: `users`, `clientes`, `ordenes_trabajo`, `seguimiento_estados`,
`materiales`, `consumo_materiales` y `entregas`.

Las optimizaciones se concentraron en tres puntos: los controladores (qué se consulta), los
modelos (cómo se consulta) y la base de datos (qué índices existen).

---

## 4. Diagnóstico inicial

### 4.1 Ciclo de trabajo aplicado

Se siguió el ciclo propuesto en la sesión:

1. **Identificar**: revisión de cada acción del panel y conteo de consultas.
2. **Analizar**: EXPLAIN sobre las consultas principales y revisión de índices.
3. **Optimizar**: reescritura de consultas, índices y caché.
4. **Validar**: medición con datos reales y verificación funcional.
5. **Monitorear**: middleware y registro de consultas lentas.

### 4.2 Problemas encontrados

| # | Problema | Dónde ocurría | Efecto |
| --- | --- | --- | --- |
| 1 | Cuatro `COUNT` separados para los indicadores del panel | `DashboardController` | 4 consultas donde bastaba 1 |
| 2 | Relación `usuario` no precargada en el directorio de clientes | `clientes/index` | 1 consulta extra por cada cliente vinculado (N+1) |
| 3 | Relación `responsable` precargada sin usarse en el listado | `OrdenTrabajoController@index` | Consulta y memoria desperdiciadas |
| 4 | `ORDER BY CASE WHEN ...` en el listado de órdenes | `OrdenTrabajoController@index` | `filesort` sobre toda la tabla, sin uso de índice |
| 5 | Dos consultas casi idénticas para pendientes y atrasadas | `ReporteController` | Trabajo duplicado sobre la misma tabla |
| 6 | Suma del costo de materiales en PHP | `ReporteController` | Transferencia de todas las filas de `consumo_materiales` |
| 7 | `pluck()` para aislar clientes del odontólogo | `DashboardController` | Una ida y vuelta adicional a la base |
| 8 | `whereDate()` en el filtro de atrasadas | `OrdenTrabajo::scopeAtrasadas` | `DATE(columna)` impide usar el índice |
| 9 | Sin índices para filtros compuestos | `ordenes_trabajo`, `materiales`, `users` | Escaneos completos al crecer el volumen |

### 4.3 Evidencia del problema N+1

Con 5 órdenes registradas, un recorrido que accede al cliente de cada orden ejecuta
1 consulta base + 1 por cada orden:

```
Sin eager loading: 6 consultas para 5 órdenes
Con eager loading: 2 consultas para cualquier cantidad de órdenes
```

El comando `php artisan netsal:diagnostico` reproduce esta comparación en cualquier momento.

---

## 5. Técnicas aplicadas

A continuación se documenta cada técnica con el código anterior, el código nuevo y su efecto.
Todas las modificaciones están comentadas en el código fuente para que cualquier integrante
del equipo pueda entenderlas.

### 5.1 Eager loading (solución al problema N+1)

**Antes** — `ClienteController@index` no precargaba la relación que la vista utiliza:

```php
$clientes = Cliente::query()
    ->withCount('ordenes')
    ->orderBy('nombre')
    ->paginate(10);
// La vista hace $cliente->usuario->email => una consulta por fila
```

**Después** — se precarga la relación con las columnas necesarias:

```php
$clientes = Cliente::query()
    ->select(['id', 'tipo', 'nombre', 'telefono', 'email', 'direccion', 'user_id', 'activo'])
    ->with('usuario:id,email')
    ->withCount('ordenes')
    ->orderBy('nombre')
    ->paginate(10);
```

También se ajustó el detalle de la orden para cargar en una sola operación todo lo que la
vista necesita:

```php
$orden->load([
    'cliente:id,nombre,telefono,email',
    'responsable:id,name,rol',
    'seguimientos.usuario:id,name',
    'consumos.material:id,nombre,unidad',
    'entrega.usuario:id,name',
]);
```

**Efecto medido:** listado de clientes con 10 filas vinculadas: de 12 a 3 consultas.

### 5.2 Selección explícita de columnas

Se reemplazó `SELECT *` por la lista de columnas que realmente se muestran, tanto en los
listados como en los selectores de formularios.

```php
// Antes: se traían todas las columnas y la relación no utilizada
OrdenTrabajo::query()->with(['cliente', 'responsable']);

// Después: columnas visibles y solo la relación que se dibuja
OrdenTrabajo::query()
    ->select(['id', 'codigo', 'cliente_id', 'paciente', 'tipo_trabajo',
              'estado', 'fecha_ingreso', 'fecha_entrega', 'precio'])
    ->with('cliente:id,nombre');
```

El mismo criterio se aplicó a los catálogos de apoyo:

```php
Cliente::query()->select(['id', 'nombre', 'tipo'])->activos()->orderBy('nombre')->get();
User::query()->select(['id', 'name', 'rol'])->responsables()->orderBy('name')->get();
```

**Efecto:** menor tráfico de datos entre MySQL y PHP y menos memoria por petición.

### 5.3 Query scopes (lógica reutilizable)

Los filtros frecuentes se encapsularon en el modelo, evitando repetir SQL en los
controladores y garantizando que todos usen el mismo criterio.

```php
// app/Models/OrdenTrabajo.php
public function scopePendientes(Builder $query): Builder
{
    return $query->whereIn('estado', self::ESTADOS_ABIERTOS);
}

public function scopeAtrasadas(Builder $query): Builder
{
    return $query->pendientes()
        ->whereNotNull('fecha_entrega')
        ->where('fecha_entrega', '<', now()->toDateString());
}

public function scopeParaUsuarioPortal(Builder $query, int $userId): Builder
{
    return $query->whereIn('cliente_id', function ($sub) use ($userId): void {
        $sub->select('id')->from('clientes')->where('user_id', $userId);
    });
}
```

Scopes disponibles:

| Modelo | Scopes |
| --- | --- |
| `OrdenTrabajo` | `pendientes`, `atrasadas`, `delCliente`, `paraUsuarioPortal`, `enEstado`, `buscar`, `conCostoMateriales` |
| `Cliente` | `activos`, `buscar` |
| `Material` | `activos`, `stockBajo`, `buscar` |
| `Entrega` | `confirmadas`, `pendientes`, `deTipo` |
| `User` | `activos`, `responsables`, `delPortal` |
| `ConsumoMaterial` | `deOrden` |

### 5.4 Agregación condicional en la base de datos

**Antes** — cuatro consultas para cuatro números:

```php
$stats = [
    'pendientes' => OrdenTrabajo::pendientes()->count(),
    'atrasadas' => OrdenTrabajo::atrasadas()->count(),
    'listas' => OrdenTrabajo::where('estado', 'listo')->count(),
    'entregadas_mes' => OrdenTrabajo::where('estado', 'entregado')->count(),
];
```

**Después** — una sola consulta con `SUM(CASE WHEN ...)`:

```php
$fila = OrdenTrabajo::query()
    ->selectRaw("SUM(CASE WHEN estado IN ({$marcadores}) THEN 1 ELSE 0 END) AS pendientes", $abiertos)
    ->selectRaw("SUM(CASE WHEN estado IN ({$marcadores}) AND fecha_entrega < ? THEN 1 ELSE 0 END) AS atrasadas", [...$abiertos, $hoy])
    ->selectRaw("SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) AS listas", ['listo'])
    ->selectRaw('SUM(CASE WHEN estado = ? AND updated_at >= ? AND updated_at < ? THEN 1 ELSE 0 END) AS entregadas_mes',
        ['entregado', $inicioMes, $finMes])
    ->first();
```

Los valores se enlazan como parámetros, por lo que se mantiene la protección contra
inyección SQL.

**Efecto medido:** de 4 a 1 consulta en el panel principal.

### 5.5 Subconsultas correlacionadas

El costo de materiales por trabajo se calculaba cargando todos los consumos:

```php
// Antes: se cargan todos los consumos y se suman en PHP
$ordenes = OrdenTrabajo::with(['cliente', 'consumos'])->take(15)->get();
foreach ($ordenes as $orden) {
    $orden->consumos->sum(fn ($c) => $c->cantidad * $c->costo_unitario);
}
```

Ahora la base de datos devuelve la suma ya calculada:

```php
// app/Models/OrdenTrabajo.php
public function scopeConCostoMateriales(Builder $query): Builder
{
    $subconsulta = ConsumoMaterial::query()
        ->selectRaw('COALESCE(SUM(cantidad * costo_unitario), 0)')
        ->whereColumn('orden_trabajo_id', 'ordenes_trabajo.id');

    return $query->addSelect(['costo_materiales' => $subconsulta]);
}
```

**Efecto:** de 3 a 2 consultas y sin transferir cientos de filas de consumos.

### 5.6 Reescribir predicados para no bloquear índices

```php
// Antes: DATE(fecha_entrega) impide usar el índice de la columna
->whereDate('fecha_entrega', '<', now()->toDateString())

// Después: comparación directa sobre la columna DATE
->where('fecha_entrega', '<', now()->toDateString())
```

También se reemplazó el ordenamiento con función:

```php
// Antes: obliga a MySQL a ordenar toda la tabla (filesort)
->orderByRaw("case when estado in ('entregado', 'anulado') then 1 else 0 end")

// Después: ordenamiento sobre columnas indexadas
->orderByDesc('fecha_ingreso')->orderByDesc('id')
```

### 5.7 `exists()` en lugar de `count()`

La verificación de acceso de un odontólogo a una orden se resolvió con `exists()`, que se
detiene en la primera fila:

```php
if ($user->esOdontologo() && ! $user->clientes()->whereKey($orden->cliente_id)->exists()) {
    abort(403, 'No tiene acceso a esta orden de trabajo.');
}
```

### 5.8 Evitar consultas duplicadas en reportes

**Antes** — dos consultas sobre la misma tabla:

```php
$pendientes = OrdenTrabajo::pendientes()->with('cliente')->get();
$atrasadas  = OrdenTrabajo::atrasadas()->with('cliente')->get();
```

**Después** — una consulta y separación en memoria:

```php
$abiertas = OrdenTrabajo::pendientes()->with('cliente:id,nombre')->orderBy('fecha_entrega')->get();
$atrasadas = $abiertas->filter(fn ($orden) => $orden->estaAtrasada())->values();
```

### 5.9 Sustituir `pluck()` por subconsulta

**Antes**:

```php
$ids = $user->clientes()->pluck('id')->all();   // consulta 1
OrdenTrabajo::whereIn('cliente_id', $ids)->get(); // consulta 2
```

**Después**:

```php
OrdenTrabajo::paraUsuarioPortal($user->id)->get(); // una sola consulta con subconsulta
```

### 5.10 Procesamiento por lotes (chunking)

Para escenarios de exportación se incorporó `chunkById`, que recorre la tabla por la clave
primaria en lotes y mantiene estable el uso de memoria:

```php
OrdenTrabajo::query()
    ->select(['id', 'codigo', 'cliente_id', 'estado', 'precio'])
    ->with('cliente:id,nombre')
    ->conCostoMateriales()
    ->chunkById(200, function ($ordenes) use ($recurso) {
        foreach ($ordenes as $orden) {
            // escribir la fila del reporte
        }
    }, 'id');
```

Se expone mediante el comando:

```bash
php artisan netsal:reporte-costos --limite=200
```

### 5.11 Paginación y límites

Todos los listados usan `paginate()` con `withQueryString()` para conservar los filtros. Los
bloques del panel y de reportes usan `limit()` (6 y 15 registros respectivamente) porque son
resúmenes y no necesitan el conjunto completo.

---

## 6. Índices en la base de datos

### 6.1 Criterio

Se indexaron las columnas usadas en `WHERE`, `JOIN` y `ORDER BY` de las consultas reales, con
índices compuestos cuando la consulta combina filtro y ordenamiento. El nombre de cada índice
en la migración documenta el caso que atiende.

### 6.2 Índices creados

Migración: `database/migrations/2026_11_03_000000_add_indexes_for_query_optimization.php`

| Tabla | Índice | Columnas | Consulta que beneficia |
| --- | --- | --- | --- |
| `clientes` | `clientes_activo_nombre_index` | activo, nombre | Directorio de clientes activos |
| `ordenes_trabajo` | `ordenes_estado_fecha_entrega_index` | estado, fecha_entrega | Pendientes y atrasadas |
| `ordenes_trabajo` | `ordenes_cliente_estado_index` | cliente_id, estado | Historial por cliente y aislamiento por rol |
| `ordenes_trabajo` | `ordenes_fecha_ingreso_index` | fecha_ingreso | Listado ordenado por ingreso |
| `ordenes_trabajo` | `ordenes_estado_updated_index` | estado, updated_at | Entregados del mes |
| `materiales` | `materiales_activo_nombre_index` | activo, nombre | Inventario activo ordenado por nombre |
| `users` | `users_rol_activo_index` | rol, activo | Selector de responsables y portal |
| `entregas` | `entregas_confirmado_fecha_index` | confirmado, fecha | Entregas pendientes por fecha |

### 6.3 Verificación con EXPLAIN

Salida del comando `php artisan netsal:diagnostico` (MariaDB 10.4.32):

| Consulta | type | key | Filas examinadas |
| --- | --- | --- | --- |
| Listado de órdenes por estado | ref | `ordenes_trabajo_estado_index` | 1 |
| Órdenes pendientes por fecha de entrega | index | `ordenes_estado_fecha_entrega_index` | 5 |
| Órdenes atrasadas | index | `ordenes_estado_fecha_entrega_index` | 5 |
| Directorio de clientes activos | ref | `clientes_activo_nombre_index` | 3 |
| Entregas pendientes por fecha | ref | `entregas_confirmado_fecha_index` | 1 |
| Inventario con stock bajo | ALL | sin índice | 6 |

**Limitación conocida:** la comparación entre dos columnas
(`stock <= stock_minimo`) no puede resolverse con un índice B-Tree. El inventario es una tabla
pequeña y de baja frecuencia de consulta, por lo que se documenta la alternativa (columna
generada persistente con índice) sin implementarla.

### 6.4 Recomendaciones de mantenimiento

```sql
-- Revisar índices de una tabla
SHOW INDEX FROM ordenes_trabajo;

-- Ver el plan de una consulta concreta
EXPLAIN SELECT * FROM ordenes_trabajo WHERE estado = 'listo';

-- Desfragmentar tablas con muchas actualizaciones
OPTIMIZE TABLE ordenes_trabajo;
```

---

## 7. Caché de consultas

### 7.1 Estrategia

Los indicadores del panel y los totales del reporte cambian con poca frecuencia pero se
consultan en cada visita. Se almacenan durante 5 minutos (300 segundos).

La invalidación se resolvió con **versionado de claves**, compatible con el driver `database`
configurado en el proyecto (que no soporta _tags_):

| Archivo | Responsabilidad |
| --- | --- |
| `app/Support/CacheConsultas.php` | Calcula y almacena resultados con clave versionada |
| `app/Models/Concerns/InvalidaCacheConsultas.php` | Invalida al guardar o eliminar un modelo |

```php
// Lectura
$stats = CacheConsultas::recordar(
    CacheConsultas::GRUPO_PANEL,
    'indicadores:'.$ambito,
    CacheConsultas::TTL_DEFECTO,
    fn (): array => $this->calcularIndicadores($user),
);

// Invalidación automática (trait aplicado a los modelos)
static::saved(fn () => CacheConsultas::invalidar(
    CacheConsultas::GRUPO_PANEL,
    CacheConsultas::GRUPO_REPORTES,
));
```

### 7.2 Modelos que invalidan la caché

`OrdenTrabajo`, `SeguimientoEstado`, `Entrega`, `ConsumoMaterial`, `Material`, `Cliente` y
`User`. De este modo, cualquier alta, edición o baja lógica refresca los indicadores en la
siguiente petición, sin intervención manual.

### 7.3 Consideración sobre la visibilidad de los datos

El odontólogo del portal ve solo sus propias cifras. Por eso la clave incluye el ámbito
(`personal` o `portal:{id}`), evitando que un usuario reciba datos cacheados de otro.

---

## 8. Herramientas de análisis

### 8.1 Middleware de instrumentación

`app/Http/Middleware/RegistraConsultas.php` cuenta las consultas de cada petición, registra en
el log las que superan el umbral y publica dos encabezados de respuesta.

Configuración en `config/consultas.php` y en `.env`:

```
DB_QUERY_LOG=true        # activa la instrumentación (false en producción)
DB_SLOW_QUERY_MS=100     # umbral de consulta lenta
DB_QUERY_HEADERS=true    # expone X-Consultas y X-Tiempo-Consultas
```

Ejemplo de respuesta:

```
X-Consultas: 3
X-Tiempo-Consultas: 1.10 ms
```

### 8.2 Comando de diagnóstico

```bash
php artisan netsal:diagnostico
```

Devuelve tres bloques: inventario de índices, planes de ejecución (EXPLAIN) y comparación
N+1 frente a eager loading. Es la evidencia reproducible que se puede presentar en la
sustentación.

### 8.3 Registro de consultas lentas

Toda consulta que supere `DB_SLOW_QUERY_MS` se escribe en `storage/logs/laravel.log`:

```
[warning] Consulta lenta detectada {"ruta":"ordenes","ms":132.4,"sql":"select ..."}
```

Complementa al _slow query log_ de MySQL, que se activa en `my.ini`:

```ini
[mysqld]
slow_query_log = 1
slow_query_log_file = "C:/xampp/mysql/data/mysql-slow.log"
long_query_time = 1
log_queries_not_using_indexes = 1
```

---

## 9. Resultados medidos

### 9.1 Comparación antes/después

Prueba ejecutada sobre un conjunto temporal de **305 órdenes, 307 consumos y 63 clientes**
(creado y revertido dentro de una transacción, sin alterar los datos del sistema):

| Escenario | Consultas antes | Consultas después | Tiempo antes (ms) | Tiempo después (ms) |
| --- | --- | --- | --- | --- |
| Indicadores del panel | 4 | 1 | 1.01 | 0.97 |
| Listado de clientes (10 filas) | 12 | 3 | 6.52 | 1.31 |
| Listado de órdenes (10 filas) | 4 | 3 | 1.53 | 1.31 |
| Reporte: costos por trabajo (15) | 3 | 2 | 1.73 | 1.07 |
| Aislamiento por odontólogo | 2 | 1 | 3.63 | 4.75 |
| Costo total de materiales | 1 | 1 | 5.88 | 0.54 |

Observaciones:

- El listado de clientes reduce sus consultas en 75 % y su tiempo en 80 %.
- El costo total de materiales mantiene una sola consulta, pero deja de transferir todas las
  filas: el motor devuelve un único valor y el tiempo cae cerca de diez veces.
- En el aislamiento por odontólogo el tiempo varía por la latencia de la conexión; la mejora
  estructural es la eliminación de una ida y vuelta.

### 9.2 Consultas por página en el entorno de demostración

Medido con los encabezados `X-Consultas` (incluye las consultas internas de sesión):

| Página | Consultas |
| --- | --- |
| Panel | 5 |
| Clientes | 3 |
| Órdenes | 4 |
| Materiales | 2 |
| Entregas | 5 |
| Reportes | 7 |
| Usuarios | 2 |

---

## 10. Checklist de optimización

- [x] Todas las relaciones usadas en vistas se cargan con eager loading.
- [x] Los listados seleccionan solo las columnas que se muestran.
- [x] Las consultas frecuentes tienen índices acordes a sus filtros.
- [x] La lógica repetida está encapsulada en scopes.
- [x] Los contadores se calculan con agregación en la base de datos.
- [x] Las sumas económicas se resuelven con `SUM()` y no en PHP.
- [x] Se evita `whereDate()` y `ORDER BY` con funciones sobre columnas indexadas.
- [x] Se implementó caché con invalidación automática para datos poco volátiles.
- [x] Existe una herramienta para ejecutar EXPLAIN y medir N+1.
- [x] El registro de consultas lentas está disponible y documentado.
- [x] Los procesos masivos se ejecutan por lotes.

---

## 11. Guía de uso para el equipo

### 11.1 Puesta en marcha

```bash
cd sistema
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

### 11.2 Verificar el estado de las optimizaciones

```bash
php artisan netsal:diagnostico        # índices, EXPLAIN y N+1
php artisan netsal:reporte-costos     # procesamiento por lotes (CSV en storage/app/reportes)
```

Con `DB_QUERY_HEADERS=true`, las herramientas del navegador muestran `X-Consultas` y
`X-Tiempo-Consultas` en cada respuesta.

### 11.3 Dónde está cada cosa

| Tema | Archivo |
| --- | --- |
| Índices | `database/migrations/2026_11_03_000000_add_indexes_for_query_optimization.php` |
| Scopes y reglas | `app/Models/OrdenTrabajo.php`, `Cliente.php`, `Material.php`, `Entrega.php`, `User.php` |
| Agregaciones del panel | `app/Http/Controllers/DashboardController.php` |
| Reportes | `app/Http/Controllers/ReporteController.php` |
| Caché | `app/Support/CacheConsultas.php`, `app/Models/Concerns/InvalidaCacheConsultas.php` |
| Instrumentación | `app/Http/Middleware/RegistraConsultas.php`, `config/consultas.php` |
| Diagnóstico | `app/Console/Commands/DiagnosticoConsultas.php` |
| Lotes | `app/Console/Commands/ReporteCostos.php` |
| Estilos del panel | `public/css/sistema.css` |

### 11.4 Reglas para nuevas consultas

1. Si la vista recorre una relación, cargarla con `with()`.
2. Si solo se muestran algunas columnas, usar `select()`.
3. Si el filtro se repite, crear un scope en el modelo.
4. Si se necesita un total, calcularlo con `SUM()`/`COUNT()` en SQL.
5. Si se recorren muchos registros, usar `chunkById()` o `cursor()`.
6. Antes de dar por terminada una pantalla, revisar `X-Consultas` y ejecutar EXPLAIN.

---

## 12. Cumplimiento de la rúbrica del proyecto

| Requisito de la consigna | Evidencia |
| --- | --- |
| Arquitectura MVC con framework | Laravel 12 con modelos, controladores y vistas Blade |
| Operaciones CRUD completas | Clientes, órdenes, materiales, entregas y usuarios |
| Autenticación y autorización | Login con hash y middleware de rol por módulo |
| Diseño responsive | Panel adaptable con barra lateral colapsable y tablas desplazables |
| Seguridad web básica | Eloquent con parámetros enlazados, escape de Blade y CSRF |
| Documentación técnica del código | Docblocks en español en modelos, controladores, comandos y estilos |
| Optimización de consultas (semana 11) | Este documento y el código referenciado |

---

## 13. Distribución del trabajo

| Integrante | Responsabilidad principal | Evidencia en el repositorio |
| --- | --- | --- |
| Vargas Cusi Jhande Emanuel | Módulo de órdenes y seguimiento de producción | `OrdenTrabajoController`, `OrdenTrabajo`, vistas `ordenes/*` |
| Cortez Huaman Mehul Dhruval | Clientes, materiales y entregas | `ClienteController`, `MaterialController`, `EntregaController` |
| Luna Urquizo Javier Enrique | Reportes, caché y herramientas de análisis | `ReporteController`, `CacheConsultas`, comandos artisan |

La distribución se refleja en el historial de Git del repositorio, según lo solicitado en la
consigna.

---

## 14. Conclusiones

1. La mayor parte del costo de las consultas no estaba en la base de datos sino en la forma de
   consultarla: relaciones no precargadas, contadores repetidos y sumas en PHP.
2. El eager loading y la selección de columnas producen mejoras inmediatas y medibles sin
   cambiar la lógica del negocio.
3. Los índices compuestos deben diseñarse a partir de las consultas reales; indexar sin medir
   solo agrega costo de escritura.
4. La caché con invalidación automática es viable incluso con el driver `database`, siempre
   que la clave incluya una versión y el ámbito del usuario.
5. Contar con herramientas propias de medición (EXPLAIN, conteo de consultas y registro de
   lentas) permite sostener la optimización como práctica continua y no como una tarea
   aislada.

---

## 15. Referencias

- Laravel. (s. f.). *Eloquent ORM*. https://laravel.com/docs/eloquent
- Laravel. (s. f.). *Database: Query Builder*. https://laravel.com/docs/queries
- Laravel. (s. f.). *Cache*. https://laravel.com/docs/cache
- Oracle. (s. f.). *MySQL 8.0 Reference Manual: Optimization*. https://dev.mysql.com/doc/refman/8.0/en/optimization.html
- MariaDB Foundation. (s. f.). *EXPLAIN*. https://mariadb.com/kb/en/explain/
- Bootstrap. (s. f.). *Bootstrap 5 documentation*. https://getbootstrap.com/docs/5.3/

---

*Documento elaborado para la semana 11 del curso de Programación Web. Las mediciones
corresponden al entorno local del equipo (XAMPP, PHP 8.2, MariaDB 10.4).*
