/* ============================================================
   js/consulta.js
   Seguimiento público de una orden de trabajo.

   El odontólogo ingresa el código de la orden y el correo o teléfono con el
   que está registrado; el sistema devuelve el estado, el avance y la línea de
   tiempo del caso.

   Nota de seguridad: los textos que llegan del servidor se insertan con
   textContent y no con innerHTML. Así, aunque un dato almacenado contuviera
   etiquetas HTML, el navegador las muestra como texto y nunca las ejecuta
   (protección contra XSS en el lado del cliente).
   ============================================================ */

(function () {
  "use strict";

  function crear(tag, clase, texto) {
    var nodo = document.createElement(tag);
    if (clase) nodo.className = clase;
    if (texto !== undefined && texto !== null) nodo.textContent = texto;
    return nodo;
  }

  function formatearFecha(valor) {
    if (!valor) return "—";
    var fecha = new Date(valor.length <= 10 ? valor + "T00:00:00" : valor);
    if (isNaN(fecha.getTime())) return "—";
    return fecha.toLocaleDateString("es-PE", {
      day: "2-digit",
      month: "short",
      year: "numeric"
    });
  }

  function formatearFechaHora(valor) {
    if (!valor) return "";
    var fecha = new Date(valor);
    if (isNaN(fecha.getTime())) return "";
    return fecha.toLocaleDateString("es-PE", {
      day: "2-digit",
      month: "short",
      year: "numeric"
    }) + " · " + fecha.toLocaleTimeString("es-PE", { hour: "2-digit", minute: "2-digit" });
  }

  function filaDato(etiqueta, valor) {
    var fila = crear("div", "consulta-dato");
    fila.appendChild(crear("span", "consulta-dato-etiqueta", etiqueta));
    fila.appendChild(crear("span", "consulta-dato-valor", valor));
    return fila;
  }

  function construirResultado(datos) {
    var bloque = crear("div", "consulta-resultado-card");

    /* Cabecera: código y estado */
    var cabecera = crear("div", "consulta-resultado-cabecera");
    var identidad = crear("div");
    identidad.appendChild(crear("span", "consulta-resultado-etiqueta", "Orden de trabajo"));
    identidad.appendChild(crear("strong", "consulta-resultado-codigo", datos.codigo));
    cabecera.appendChild(identidad);

    var distintivos = crear("div", "consulta-resultado-distintivos");
    distintivos.appendChild(crear("span", "consulta-estado consulta-estado-" + datos.estado, datos.estado_nombre));
    if (datos.atrasada) distintivos.appendChild(crear("span", "consulta-aviso", "Fuera de plazo"));
    cabecera.appendChild(distintivos);
    bloque.appendChild(cabecera);

    /* Barra de avance */
    var avance = crear("div", "consulta-avance");
    var barra = crear("div", "consulta-barra");
    var relleno = crear("span");
    relleno.style.width = Math.max(0, Math.min(100, Number(datos.avance) || 0)) + "%";
    barra.appendChild(relleno);
    avance.appendChild(barra);
    avance.appendChild(crear("span", "consulta-avance-texto", (Number(datos.avance) || 0) + "% completado"));
    bloque.appendChild(avance);

    /* Datos del trabajo */
    var rejilla = crear("div", "consulta-datos");
    rejilla.appendChild(filaDato("Trabajo", datos.tipo_trabajo || "—"));
    rejilla.appendChild(filaDato("Paciente", datos.paciente || "—"));
    rejilla.appendChild(filaDato("Ingreso", formatearFecha(datos.fecha_ingreso)));
    rejilla.appendChild(filaDato("Entrega prevista", formatearFecha(datos.fecha_entrega)));
    bloque.appendChild(rejilla);

    /* Línea de tiempo */
    if (datos.historial && datos.historial.length) {
      bloque.appendChild(crear("h3", "consulta-subtitulo", "Historial de producción"));
      var linea = crear("ol", "consulta-linea");
      datos.historial.forEach(function (paso) {
        var item = crear("li", "consulta-paso");
        item.appendChild(crear("span", "consulta-paso-estado", paso.estado_nombre));
        item.appendChild(crear("span", "consulta-paso-fecha", formatearFechaHora(paso.fecha)));
        if (paso.observacion) {
          item.appendChild(crear("span", "consulta-paso-nota", paso.observacion));
        }
        linea.appendChild(item);
      });
      bloque.appendChild(linea);
    }

    /* Entrega */
    if (datos.entrega) {
      bloque.appendChild(crear("h3", "consulta-subtitulo", "Entrega"));
      var entrega = crear("div", "consulta-datos");
      entrega.appendChild(filaDato("Tipo", datos.entrega.tipo_nombre || "—"));
      entrega.appendChild(filaDato("Fecha", formatearFecha(datos.entrega.fecha)));
      entrega.appendChild(filaDato("Receptor", datos.entrega.receptor || "—"));
      entrega.appendChild(filaDato("Confirmada", datos.entrega.confirmado ? "Sí" : "Pendiente"));
      bloque.appendChild(entrega);
    }

    return bloque;
  }

  function iniciar() {
    var form = document.getElementById("consulta-form");
    if (!form) return;

    var codigo = document.getElementById("consulta-codigo");
    var contacto = document.getElementById("consulta-contacto");
    var resultado = document.getElementById("consulta-resultado");
    var error = document.getElementById("consulta-error");
    var boton = form.querySelector('button[type="submit"]');
    var contenidoBoton = boton ? boton.innerHTML : "";

    function mostrarError(mensaje) {
      if (!error) return;
      error.textContent = mensaje;
      error.classList.add("is-visible");
      if (resultado) resultado.innerHTML = "";
    }

    function ocultarError() {
      if (error) error.classList.remove("is-visible");
    }

    /* Diagnóstico inmediato: los dos motivos habituales de que "no cargue"
       son abrir la página sin servidor o que el navegador tenga una copia
       vieja de los scripts. Se avisa antes de que el usuario escriba nada. */
    if (window.location.protocol === "file:") {
      mostrarError(
        "Esta página se abrió como archivo. Ábrala desde el servidor local (http://localhost/Netsal/) para consultar el sistema."
      );

      var enlacePortal = document.getElementById("consulta-portal-link");
      if (enlacePortal) {
        enlacePortal.href = "http://localhost/Netsal/sistema/public/login";
      }
    } else if (!window.NetsalApi) {
      mostrarError("No se pudo cargar el cliente del sistema. Recargue la página con Ctrl + F5.");
    }

    form.addEventListener("submit", function (evento) {
      evento.preventDefault();
      ocultarError();

      if (!codigo.value.trim() || !contacto.value.trim()) {
        mostrarError("Ingrese el código de la orden y su correo o teléfono registrado.");
        return;
      }

      var api = window.NetsalApi;
      if (!api) {
        mostrarError("El cliente del sistema no está disponible. Recargue la página con Ctrl + F5.");
        return;
      }

      if (boton) {
        boton.disabled = true;
        boton.innerHTML = "Consultando…";
      }

      api
        .consultarOrden(codigo.value, contacto.value)
        .then(function (datos) {
          if (!resultado) return;
          resultado.innerHTML = "";
          resultado.appendChild(construirResultado(datos));
        })
        .catch(function (fallo) {
          mostrarError(fallo.message);
        })
        .finally(function () {
          if (boton) {
            boton.disabled = false;
            boton.innerHTML = contenidoBoton;
          }
        });
    });
  }

  document.addEventListener("DOMContentLoaded", iniciar);
})();
