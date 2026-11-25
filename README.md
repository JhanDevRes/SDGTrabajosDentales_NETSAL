# NETSAL — Sitio web y sistema de gestión dental

Sitio público del **Laboratorio Dental NETSAL (Cusco)** y sistema de gestión de trabajos
dentales desarrollado con Laravel. El sitio presenta los servicios del laboratorio y el
sistema permite registrar órdenes de producción, hacer seguimiento de cada trabajo,
controlar el inventario de materiales y administrar las entregas.

**Curso:** Programación Web — Universidad Continental
**Docente:** Mg. Alfredo Collantes Mendoza

## Autores

| Integrante | Correo institucional |
| --- | --- |
| Vargas Cusi Jhande Emanuel | 76553002@continental.edu.pe |
| Cortez Huaman Mehul Dhruval | 61202602@continental.edu.pe |
| Luna Urquizo Javier Enrique | 61057682@continental.edu.pe |

## Estado del proyecto

| Componente | Estado | Requisitos |
| --- | --- | --- |
| Sitio público | Funcional | Ninguno; se abre `index.html` en el navegador |
| Sistema de gestión | Funcional | PHP 8.2, Composer y MySQL/MariaDB |

El sistema reemplaza a la antigua simulación estática (`sistema.html`), que fue retirada
del repositorio.

## Cómo abrir el sitio

El sitio **debe abrirse desde el servidor local**, no con doble clic sobre el archivo:

```
http://localhost/Netsal/                    ← sitio público
http://localhost/Netsal/sistema/public/login  ← sistema de gestión
```

Si abre `index.html` como archivo (`file:///...`), el navegador no tiene servidor: la consulta
de casos y el enlace al portal no funcionan, y la página muestra un aviso explicándolo.
Con Apache y MySQL encendidos en XAMPP, ambas rutas funcionan.

## Estructura

```
.
├── index.html          # Página pública del laboratorio
├── robots.txt          # Reglas de indexación para buscadores
├── sitemap.xml         # Mapa del sitio para buscadores
├── assets/
│   ├── images/         # Imágenes del sitio
│   └── logo/           # Logotipo
├── css/                # Hojas de estilo por sección y componentes
├── data/
│   └── site.js         # Fuente de datos del contenido (const site)
├── js/                 # Módulos de comportamiento (IIFE)
└── sistema/            # Aplicación Laravel 12 (arquitectura MVC)
```

## Módulos de JavaScript del sitio público

| Archivo | Responsabilidad |
| --- | --- |
| `navbar.js` | Navegación, scroll spy y botón "volver arriba" |
| `slider.js` | Carrusel de contenido |
| `faq.js` | Acordeón de preguntas frecuentes |
| `animations.js` | Animaciones de aparición y filtro de galería |
| `whatsapp.js` | Botón flotante de WhatsApp |
| `lightbox.js` | Visor de imágenes ampliadas |
| `interactivo.js` | Interacciones adicionales |
| `main.js` | Poblamiento del DOM a partir de `data/site.js` y cifras del sistema |
| `api.js` | Cliente de la API pública (estadísticas, consulta de casos y contacto) |
| `consulta.js` | Seguimiento del estado de una orden por código |

## API pública y consulta de casos

El sitio es estático y el sistema Laravel vive en otra ruta del mismo servidor, por lo que se
comunican mediante una API pública con CORS restringido:

| Método | Ruta | Función |
| --- | --- | --- |
| `GET` | `/api/publico/estadisticas` | Cifras agregadas del laboratorio (sin datos de clientes) |
| `GET` | `/api/publico/ordenes/{codigo}?contacto=` | Estado, avance e historial de una orden |
| `POST` | `/api/publico/contacto` | Registro de una solicitud del formulario público |

La consulta de una orden exige, además del código, el correo o el teléfono del cliente
registrado; así el código correlativo no permite ver trabajos ajenos. Las solicitudes quedan
en la bandeja **Solicitudes** del sistema y se avisan por correo al laboratorio.

## Sistema de gestión

Aplicación Laravel 12 con arquitectura MVC, vistas Blade con Bootstrap 5 y base de datos
MySQL. Su documentación técnica está en [sistema/README.md](sistema/README.md).

### Módulos

| Módulo | Funcionalidad |
| --- | --- |
| Autenticación y usuarios | Inicio de sesión con contraseñas cifradas; alta, edición y baja de cuentas |
| Clientes | Odontólogos y clínicas con datos de contacto y baja lógica |
| Órdenes de trabajo | Registro, edición, anulación, código automático y fechas de ingreso y entrega |
| Seguimiento de producción | Historial de estados: recibido, diseño, fresado, horno/acabado, control, listo y entregado |
| Materiales e inventario | Costo unitario, stock, stock mínimo y alertas de reposición |
| Consumo de materiales | Descuento automático de stock por orden y cálculo del costo del trabajo |
| Entregas | Recojo o envío, confirmación y actualización automática del estado de la orden |
| Reportes | Trabajos pendientes y atrasados, entregas del mes, stock bajo y margen estimado, con gráficos de producción y economía |
| Panel en vivo | Indicadores, cumplimiento de plazos y tiempo promedio que se actualizan solos cada minuto |
| Solicitudes del sitio web | Bandeja de contactos recibidos desde el formulario público, con aviso por correo |
| Mi cuenta (portal) | Historial completo y totales del odontólogo o clínica autenticada |

### Roles

| Rol | Acceso |
| --- | --- |
| `administrador` | Todos los módulos, costos, reportes, anulación de órdenes y solicitudes |
| `recepcion` | Clientes, órdenes, entregas, reportes y solicitudes del sitio web |
| `disenador` | Órdenes y estados de diseño |
| `tecnico` | Órdenes, materiales, consumo y reportes |
| `odontologo` | Solo las órdenes de los clientes vinculados a su usuario, más **Mi cuenta** |

### Optimización de consultas

La capa de acceso a datos fue optimizada aplicando eager loading, selección explícita de
columnas, query scopes, agregación en la base de datos, índices compuestos y caché con
invalidación automática. Incluye herramientas de medición propias:

```bash
php artisan netsal:diagnostico     # índices, EXPLAIN y comparación N+1
php artisan netsal:reporte-costos  # exportación por lotes
```

## Cómo ejecutar el sistema

Requisitos: PHP 8.2+, Composer y MySQL/MariaDB (por ejemplo, el MySQL de XAMPP).

1. Crear la base de datos:

```sql
CREATE DATABASE NetsalDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Instalar y ejecutar:

```bash
cd sistema
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

3. Ingresar en `http://127.0.0.1:8000` con alguno de los usuarios de demostración
   (contraseña `password`): `admin@netsal.test`, `recepcion@netsal.test`,
   `diseno@netsal.test`, `tecnico@netsal.test`, `odontologo@netsal.test`.

   La pantalla de acceso incluye botones de entrada rápida para esas cuentas. Es un apoyo
   temporal para revisar el proyecto: se desactiva con `MOSTRAR_ACCESO_DEMO=false` en `.env`.

## Tecnologías

- **Sitio público:** HTML5, CSS3 (variables y diseño responsivo) y JavaScript sin
  dependencias externas.
- **Sistema de gestión:** PHP 8.2, Laravel 12, Eloquent ORM, Blade, Bootstrap 5 y
  MySQL/MariaDB.

## Documentación

| Documento | Contenido |
| --- | --- |
| [sistema/README.md](sistema/README.md) | Módulos, roles, modelo de datos y puesta en marcha |
