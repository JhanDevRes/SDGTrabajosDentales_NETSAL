/* ============================================================================
   public/js/panel-indicadores.js

   Mantiene los indicadores del panel al día sin recargar la página.

   - Al cargar, anima cada cifra desde 0 hasta su valor real.
   - Cada 60 segundos consulta panel/indicadores (JSON) y actualiza los
     números, la barra de cumplimiento y el sello de "actualizado".
   - El botón "Actualizar" fuerza una consulta en el momento.

   El endpoint usa la misma caché que el panel, así que una actualización
   automática normalmente no genera consultas nuevas a la base de datos.
   ============================================================================ */

(function () {
  "use strict";

  var raiz = document.getElementById("panel-indicadores");
  if (!raiz) return;

  var url = raiz.dataset.url;
  var intervalo = (Number(raiz.dataset.intervalo) || 60) * 1000;
  var boton = document.getElementById("indicador-refrescar");
  var sello = document.getElementById("indicador-sello");
  var pulso = document.getElementById("indicador-pulso");
  var barra = document.getElementById("indicador-barra");
  var temporizador = null;
  var sinAnimacion =
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* --- Animación de cifras --- */

  function animar(elemento, destino) {
    var desde = Number(elemento.dataset.valor || 0);
    var inicio = null;
    var duracion = 600;

    if (sinAnimacion) {
      elemento.textContent = destino.toLocaleString("es-PE");
      elemento.dataset.valor = destino;
      return;
    }

    function paso(momento) {
      if (inicio === null) inicio = momento;
      var avance = Math.min((momento - inicio) / duracion, 1);
      var valor = desde + (destino - desde) * avance;
      elemento.textContent = Math.round(valor).toLocaleString("es-PE");
      if (avance < 1) {
        window.requestAnimationFrame(paso);
      } else {
        elemento.dataset.valor = destino;
      }
    }

    window.requestAnimationFrame(paso);
  }

  function pintar(datos) {
    Object.keys(datos).forEach(function (clave) {
      var nodo = raiz.querySelector('[data-indicador="' + clave + '"]');
      if (!nodo) return;

      var valor = Number(datos[clave]) || 0;
      var formato = nodo.dataset.formato || "entero";

      if (formato === "entero") {
        animar(nodo, valor);
      } else if (formato === "porcentaje") {
        nodo.textContent = datos.entregas_medidas > 0 ? valor.toLocaleString("es-PE") + "%" : "—";
      } else if (formato === "dias") {
        nodo.textContent = datos.entregas_medidas > 0 ? valor.toLocaleString("es-PE") + " d" : "—";
      }
    });

    if (barra && datos.cumplimiento !== undefined) {
      barra.style.width = Math.max(0, Math.min(100, Number(datos.cumplimiento))) + "%";
    }

    if (sello) {
      var fecha = datos.actualizado_en ? new Date(datos.actualizado_en) : new Date();
      sello.textContent = "actualizado " + horaCorta(fecha);
    }
  }

  function horaCorta(fecha) {
    return fecha.toLocaleTimeString("es-PE", { hour: "2-digit", minute: "2-digit" });
  }

  /* --- Actualización --- */

  function actualizar() {
    if (pulso) pulso.classList.add("actualizando");
    if (boton) boton.disabled = true;

    window
      .fetch(url, { headers: { Accept: "application/json" }, cache: "no-store" })
      .then(function (respuesta) {
        if (!respuesta.ok) throw new Error("respuesta " + respuesta.status);
        return respuesta.json();
      })
      .then(function (cuerpo) {
        if (cuerpo && cuerpo.datos) pintar(cuerpo.datos);
      })
      .catch(function () {
        if (sello) sello.textContent = "sin conexión con el sistema";
      })
      .finally(function () {
        if (pulso) pulso.classList.remove("actualizando");
        if (boton) boton.disabled = false;
      });
  }

  function programar() {
    window.clearInterval(temporizador);
    temporizador = window.setInterval(actualizar, intervalo);
  }

  if (boton) {
    boton.addEventListener("click", function () {
      actualizar();
      programar();
    });
  }

  /* Primera animación con las cifras ya renderizadas por Blade. */
  raiz.querySelectorAll("[data-indicador][data-formato='entero']").forEach(function (nodo) {
    var valor = Number(nodo.textContent.replace(/[^\d-]/g, "")) || 0;
    nodo.dataset.valor = 0;
    animar(nodo, valor);
  });

  programar();
})();
