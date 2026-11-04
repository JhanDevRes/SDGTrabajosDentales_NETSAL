/* ============================================================
   js/interactivo.js
   Dinamismo del sitio: reloj de la barra superior, tema claro/oscuro,
   avisos emergentes, progreso de lectura, aviso de horario del día y
   validación del formulario de contacto.
   ============================================================ */

(function () {
  "use strict";

  var LIMITE_MENSAJE = 500;

  var raiz = document.documentElement;
  var reloj = document.getElementById("reloj");
  var botonTema = document.getElementById("boton-tema");
  var botonSubir = document.getElementById("back-to-top");
  var barraProgreso = document.getElementById("barra-progreso");
  var contenedorToast = document.getElementById("contenedor-toast");
  var formularioContacto = document.getElementById("contact-form");
  var campoNombre = document.getElementById("contact-name");
  var campoTelefono = document.getElementById("contact-phone");
  var campoCorreo = document.getElementById("contact-email");
  var campoAsunto = document.getElementById("contact-service");
  var campoMensaje = document.getElementById("contact-message");
  var contadorMensaje = document.getElementById("contador-mensaje");
  var notaHorarioDia = document.getElementById("nota-horario-dia");
  var enlacesContacto = document.querySelectorAll('a[href="#contacto"]');
  var camposContacto = [campoNombre, campoTelefono, campoCorreo, campoMensaje];

  /* ---------- Reloj y fecha ---------- */

  function obtenerSaludo() {
    var hora = new Date().getHours();
    if (hora < 12) return "Buenos días";
    if (hora < 19) return "Buenas tardes";
    return "Buenas noches";
  }

  function actualizarReloj() {
    if (!reloj) return;

    var ahora = new Date();
    var hora = ahora.toLocaleTimeString("es-PE", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit"
    });
    var fecha = ahora.toLocaleDateString("es-PE", {
      weekday: "short",
      day: "2-digit",
      month: "short"
    });

    reloj.textContent = fecha + " · " + hora;
  }

  /* ---------- Avisos emergentes ---------- */

  function mostrarToast(mensaje, tipo) {
    if (!contenedorToast) return null;

    var claseTipo = tipo === "error" ? "toast-error" : tipo === "info" ? "toast-info" : "toast-exito";
    var icono = tipo === "error" ? "Error" : tipo === "info" ? "Info" : "OK";

    var aviso = document.createElement("div");
    aviso.className = "toast " + claseTipo;
    aviso.setAttribute("role", "status");
    aviso.innerHTML =
      '<span class="icono-toast" aria-hidden="true">' + icono + "</span><span>" + mensaje + "</span>";

    contenedorToast.appendChild(aviso);
    window.setTimeout(function () { aviso.classList.add("mostrar"); }, 30);

    function retirarAviso() {
      aviso.classList.remove("mostrar");
      window.setTimeout(function () { aviso.remove(); }, 400);
    }

    aviso.addEventListener("click", retirarAviso);
    window.setTimeout(retirarAviso, 4600);

    return aviso;
  }

  /* ---------- Tema claro / oscuro ---------- */

  function aplicarTema(tema) {
    var oscuro = tema === "oscuro";
    raiz.classList.toggle("tema-oscuro", oscuro);

    if (botonTema) {
      var etiqueta = oscuro ? "Cambiar a tema claro" : "Cambiar a tema oscuro";
      botonTema.setAttribute("aria-pressed", oscuro ? "true" : "false");
      botonTema.setAttribute("aria-label", etiqueta);
      botonTema.setAttribute("title", etiqueta);
    }

    var metaTema = document.querySelector('meta[name="theme-color"]');
    if (metaTema) metaTema.setAttribute("content", oscuro ? "#0B0B10" : "#F21D2F");

    localStorage.setItem("netsalTema", oscuro ? "oscuro" : "claro");
  }

  function alternarTema() {
    var nuevo = raiz.classList.contains("tema-oscuro") ? "claro" : "oscuro";
    aplicarTema(nuevo);
    mostrarToast(nuevo === "oscuro" ? "Cambiaste al tema oscuro" : "Cambiaste al tema claro", "info");
  }

  /* ---------- Validación del formulario de contacto ---------- */

  function marcarCampoError(campo, mensaje) {
    if (!campo) return;
    campo.classList.add("campo-invalido");

    var contenedor = campo.parentElement;
    var aviso = contenedor.querySelector(".aviso-error");

    if (!aviso) {
      aviso = document.createElement("small");
      aviso.className = "aviso-error";
      campo.insertAdjacentElement("afterend", aviso);
    }

    aviso.textContent = mensaje;
  }

  function limpiarErrorCampo(campo) {
    if (!campo) return;
    campo.classList.remove("campo-invalido");

    var aviso = campo.parentElement.querySelector(".aviso-error");
    if (aviso) aviso.remove();
  }

  function validarCorreo(correo) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(correo);
  }

  function validarTelefono(telefono) {
    return /^[0-9+\s()-]{6,}$/.test(telefono);
  }

  function validarFormularioContacto() {
    if (!formularioContacto) return true;

    var valido = true;
    var nombre = campoNombre ? campoNombre.value.trim() : "";
    var telefono = campoTelefono ? campoTelefono.value.trim() : "";
    var correo = campoCorreo ? campoCorreo.value.trim() : "";
    var mensaje = campoMensaje ? campoMensaje.value.trim() : "";

    if (nombre.length < 2) {
      marcarCampoError(campoNombre, "Escribe el nombre del odontólogo o de la clínica.");
      valido = false;
    } else {
      limpiarErrorCampo(campoNombre);
    }

    if (!validarTelefono(telefono)) {
      marcarCampoError(campoTelefono, "Ingresa un teléfono o WhatsApp de contacto.");
      valido = false;
    } else {
      limpiarErrorCampo(campoTelefono);
    }

    if (correo && !validarCorreo(correo)) {
      marcarCampoError(campoCorreo, "El correo no tiene un formato válido.");
      valido = false;
    } else {
      limpiarErrorCampo(campoCorreo);
    }

    if (mensaje.length < 10) {
      marcarCampoError(campoMensaje, "Describe el caso con al menos 10 caracteres.");
      valido = false;
    } else {
      limpiarErrorCampo(campoMensaje);
    }

    return valido;
  }

  function actualizarContadorMensaje() {
    if (!campoMensaje || !contadorMensaje) return;

    if (campoMensaje.value.length > LIMITE_MENSAJE) {
      campoMensaje.value = campoMensaje.value.slice(0, LIMITE_MENSAJE);
    }

    var total = campoMensaje.value.length;
    contadorMensaje.textContent = total + " / " + LIMITE_MENSAJE + " caracteres";
    contadorMensaje.classList.toggle("contador-limite", total >= LIMITE_MENSAJE);
  }

  /* ---------- Aviso de horario del día ---------- */

  function resaltarHorarioHoy() {
    if (!notaHorarioDia) return;

    var agenda = (typeof site !== "undefined" && site.scheduleFull) || [];
    var diaSemana = new Date().getDay();

    if (diaSemana === 0) {
      notaHorarioDia.classList.add("nota-cerrado");
      notaHorarioDia.textContent =
        "Hoy es domingo y el laboratorio permanece cerrado. Atendemos de lunes a sábado.";
      return;
    }

    var detalle = agenda[(diaSemana + 6) % 7] || "";
    var separador = detalle.indexOf(": ");
    var horario = separador >= 0 ? detalle.slice(separador + 2) : detalle;
    var nombreDia = new Date().toLocaleDateString("es-PE", { weekday: "long" });

    notaHorarioDia.classList.remove("nota-cerrado");
    notaHorarioDia.textContent = "Hoy es " + nombreDia + ". Horario de atención: " + horario;
  }

  /* ---------- Barra de progreso de lectura ---------- */

  function actualizarProgreso() {
    if (!barraProgreso) return;
    var alturaTotal = document.documentElement.scrollHeight - window.innerHeight;
    var progreso = alturaTotal > 0 ? (window.scrollY / alturaTotal) * 100 : 0;
    barraProgreso.style.width = progreso + "%";
  }

  /* ---------- Registro de eventos ---------- */

  function iniciarEventos() {
    if (botonTema) botonTema.addEventListener("click", alternarTema);
    aplicarTema(localStorage.getItem("netsalTema") || "claro");

    if (botonSubir) {
      botonSubir.addEventListener("click", function () {
        mostrarToast("Volviste al inicio de la página", "info");
      });
    }

    camposContacto.forEach(function (campo) {
      if (!campo) return;
      campo.addEventListener("input", function () { limpiarErrorCampo(campo); });
    });

    if (campoMensaje) {
      campoMensaje.addEventListener("input", actualizarContadorMensaje);
      actualizarContadorMensaje();
    }

    document.addEventListener("submit", function (evento) {
      if (evento.target !== formularioContacto) return;
      if (!validarFormularioContacto()) {
        evento.preventDefault();
        evento.stopPropagation();
        mostrarToast("Revisa los campos marcados en el formulario", "error");
      }
    }, true);

    enlacesContacto.forEach(function (enlace) {
      enlace.addEventListener("click", function () {
        window.setTimeout(function () {
          if (campoNombre) campoNombre.focus({ preventScroll: true });
          mostrarToast("Completa el formulario y te contactaremos pronto", "info");
        }, 800);
      });
    });

    window.addEventListener("scroll", actualizarProgreso, { passive: true });
    window.addEventListener("resize", actualizarProgreso);

    actualizarReloj();
    window.setInterval(actualizarReloj, 1000);
    actualizarProgreso();
    resaltarHorarioHoy();
  }

  document.addEventListener("DOMContentLoaded", iniciarEventos);
})();
