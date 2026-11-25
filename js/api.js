/* ============================================================
   js/api.js
   Cliente de la API pública del sistema NETSAL.

   El sitio es un HTML estático servido por Apache y el sistema Laravel
   responde en otra ruta (y por tanto en otro origen). Por eso todas las
   llamadas pasan por aquí: un solo lugar donde se define la dirección base,
   el tiempo máximo de espera y el formato de los errores.

   Contrato de la API (ver routes/api.php):
     GET  /api/publico/estadisticas
     GET  /api/publico/ordenes/{codigo}?contacto=...
     POST /api/publico/contacto

   Todas las respuestas llegan como { datos: {...} } o
   { error: { codigo, mensaje } }.
   ============================================================ */

window.NetsalApi = (function () {
  "use strict";

  var config = (typeof site !== "undefined" && site.api) ? site.api : {};

  /* Ruta relativa por defecto: funciona tanto en http://localhost/Netsal/
     como en cualquier subcarpeta del servidor del laboratorio. */
  var BASE = (config.base || "sistema/public/api/publico").replace(/\/+$/, "");
  var ESPERA_MAXIMA = config.timeout || 8000;

  /* Motivo más común de "no carga": abrir index.html con doble clic.
     Con el protocolo file:// no hay servidor y las rutas relativas apuntan a
     archivos inexistentes, así que se avisa de forma explícita. */
  function abiertoComoArchivo() {
    return window.location.protocol === "file:";
  }

  /* ===== Utilidades ===== */

  function construirUrl(ruta, parametros) {
    var destino = new URL(BASE + "/" + ruta, document.baseURI);

    if (parametros) {
      Object.keys(parametros).forEach(function (clave) {
        var valor = parametros[clave];
        if (valor !== undefined && valor !== null && valor !== "") {
          destino.searchParams.set(clave, valor);
        }
      });
    }

    return destino.toString();
  }

  /**
   * Ejecuta una petición y normaliza la respuesta.
   * Resuelve con el objeto "datos" o rechaza con un Error que lleva el
   * código y el mensaje que envió el servidor, listo para mostrar.
   */
  function pedir(ruta, opciones) {
    opciones = opciones || {};

    if (abiertoComoArchivo()) {
      var archivo = new Error(
        "El sitio se abrió como archivo. Ábralo desde http://localhost/Netsal/ para consultar el sistema."
      );
      archivo.codigo = "protocolo_archivo";
      return Promise.reject(archivo);
    }

    var controlador = typeof AbortController !== "undefined" ? new AbortController() : null;
    var temporizador = null;

    var ajustes = {
      method: opciones.metodo || "GET",
      headers: { Accept: "application/json" },
      cache: "no-store"
    };

    if (opciones.cuerpo) {
      ajustes.headers["Content-Type"] = "application/json";
      ajustes.body = JSON.stringify(opciones.cuerpo);
    }

    if (controlador) {
      ajustes.signal = controlador.signal;
      temporizador = window.setTimeout(function () {
        controlador.abort();
      }, ESPERA_MAXIMA);
    }

    return fetch(construirUrl(ruta, opciones.parametros), ajustes)
      .then(function (respuesta) {
        return respuesta
          .json()
          .catch(function () {
            return {};
          })
          .then(function (cuerpo) {
            if (respuesta.ok) {
              return cuerpo.datos || {};
            }

            var error = new Error(
              (cuerpo.error && cuerpo.error.mensaje) ||
                "No se pudo completar la consulta. Intente nuevamente."
            );
            error.codigo = (cuerpo.error && cuerpo.error.codigo) || "error_servidor";
            error.estado = respuesta.status;
            throw error;
          });
      })
      .catch(function (error) {
        if (error.name === "AbortError") {
          var tiempo = new Error("El sistema tardó demasiado en responder. Intente nuevamente.");
          tiempo.codigo = "tiempo_agotado";
          throw tiempo;
        }
        if (error.codigo) {
          throw error;
        }
        var red = new Error("No se pudo conectar con el sistema del laboratorio.");
        red.codigo = "sin_conexion";
        throw red;
      })
      .finally(function () {
        if (temporizador) window.clearTimeout(temporizador);
      });
  }

  /* ===== Formato de cifras ===== */

  function numero(valor) {
    var n = Number(valor) || 0;
    return n.toLocaleString("es-PE");
  }

  function porcentaje(valor) {
    var n = Number(valor);
    if (!isFinite(n) || n <= 0) return "—";
    return (Math.round(n * 10) / 10).toLocaleString("es-PE") + "%";
  }

  function dias(valor) {
    var n = Number(valor);
    if (!isFinite(n) || n <= 0) return "—";
    return (Math.round(n * 10) / 10).toLocaleString("es-PE") + " d";
  }

  /* ===== API pública ===== */

  return {
    estadisticas: function () {
      return pedir("estadisticas");
    },

    consultarOrden: function (codigo, contacto) {
      return pedir("ordenes/" + encodeURIComponent(String(codigo || "").trim()), {
        parametros: { contacto: String(contacto || "").trim() }
      });
    },

    enviarSolicitud: function (datos) {
      return pedir("contacto", { metodo: "POST", cuerpo: datos });
    },

    numero: numero,
    porcentaje: porcentaje,
    dias: dias
  };
})();
