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
| `main.js` | Poblamiento del DOM a partir de `data/site.js` |

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
| Reportes | Trabajos pendientes y atrasados, entregas del mes, stock bajo y margen estimado |

### Roles

| Rol | Acceso |
| --- | --- |
| `administrador` | Todos los módulos, costos, reportes y anulación de órdenes |
| `recepcion` | Clientes, órdenes, entregas y reportes |
| `disenador` | Órdenes y estados de diseño |
| `tecnico` | Órdenes, materiales, consumo y reportes |
| `odontologo` | Solo las órdenes de los clientes vinculados a su usuario |

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

## Tecnologías

- **Sitio público:** HTML5, CSS3 (variables y diseño responsivo) y JavaScript sin
  dependencias externas.
- **Sistema de gestión:** PHP 8.2, Laravel 12, Eloquent ORM, Blade, Bootstrap 5 y
  MySQL/MariaDB.

## Documentación

| Documento | Contenido |
| --- | --- |
| [sistema/README.md](sistema/README.md) | Módulos, roles, modelo de datos y puesta en marcha |
