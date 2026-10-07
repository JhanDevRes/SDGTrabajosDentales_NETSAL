# NETSAL — Sitio web y sistema de gestión dental

Prototipo de sitio web estático para el consultorio dental **NETSAL**, que incluye la
landing page pública y una simulación del sistema de gestión interno.

## Estado del proyecto

Avance de prototipado funcional: no requiere proceso de compilación ni dependencias.
Basta con abrir `index.html` en el navegador.

## Estructura

```
.
├── index.html          # Página pública del consultorio
├── sistema.html        # Simulación del sistema de gestión (login + panel)
├── robots.txt          # Reglas de indexación para buscadores
├── sitemap.xml         # Mapa del sitio para buscadores
├── assets/
│   ├── images/         # Imágenes del sitio
│   └── logo/           # Logotipo
├── css/                # Hojas de estilo por sección y componentes
├── data/
│   └── site.js         # Fuente de datos del contenido (const site)
└── js/                 # Módulos de comportamiento (IIFE)
```

## Módulos de JavaScript

| Archivo          | Responsabilidad                                           |
| ---------------- | --------------------------------------------------------- |
| `navbar.js`      | Navegación, scroll spy y botón "volver arriba"            |
| `slider.js`      | Carrusel de contenido                                     |
| `faq.js`         | Acordeón de preguntas frecuentes                          |
| `animations.js`  | Animaciones de aparición y filtro de galería              |
| `whatsapp.js`    | Botón flotante de WhatsApp                                |
| `lightbox.js`    | Visor de imágenes ampliadas                               |
| `interactivo.js` | Interacciones adicionales                                 |
| `main.js`        | Poblamiento del DOM a partir de `data/site.js`            |
| `sistema.js`     | Lógica del sistema prototipado (login, órdenes, reportes) |

## Acceso al sistema (demo)

- Todos los usuarios de demostración usan la contraseña `123456`.
- La sesión y las órdenes se guardan en `sessionStorage`; el correo recordado en `localStorage`.

## Tecnologías

HTML5, CSS3 (variables y diseño responsivo) y JavaScript sin dependencias externas.

## Autor

JhanDevRes — <https://github.com/JhanDevRes>
