/* ============================================================
   js/interactivo.js
   Capa de dinamismo del prototipo NETSAL: reloj, tema claro/oscuro,
   avisos emergentes, modal de bienvenida, progreso de lectura,
   validaciones del formulario y estimador de presupuesto CAD/CAM.
   ============================================================ */

(function () {
  "use strict";

  var LIMITE_MENSAJE = 500;

  var raiz = document.documentElement;
  var reloj = document.getElementById("reloj");
  var botonTema = document.getElementById("boton-tema");
  var botonBienvenida = document.getElementById("boton-bienvenida");
  var botonSubir = document.getElementById("back-to-top");
  var barraProgreso = document.getElementById("barra-progreso");
  var contenedorToast = document.getElementById("contenedor-toast");
  var modalBienvenida = document.getElementById("modal-bienvenida");
  var formularioBienvenida = document.getElementById("formulario-bienvenida");
  var nombreVisitante = document.getElementById("nombre-visitante");
  var errorNombre = document.getElementById("error-nombre");
  var textoSaludo = document.getElementById("texto-saludo");
  var cerrarModal = document.getElementById("cerrar-modal");
  var omitirBienvenida = document.getElementById("omitir-bienvenida");
  var formularioContacto = document.getElementById("contact-form");
  var campoNombre = document.getElementById("contact-name");
  var campoTelefono = document.getElementById("contact-phone");
  var campoCorreo = document.getElementById("contact-email");
  var campoAsunto = document.getElementById("contact-service");
  var campoMensaje = document.getElementById("contact-message");
  var contadorMensaje = document.getElementById("contador-mensaje");
  var notaHorarioDia = document.getElementById("nota-horario-dia");
  var selectTrabajo = document.getElementById("tipo-trabajo");
  var rangoPiezas = document.getElementById("numero-piezas");
  var valorPiezas = document.getElementById("valor-piezas");
  var totalPresupuesto = document.getElementById("total-presupuesto");
  var botonAgendarPresupuesto = document.getElementById("agendar-presupuesto");
  var enlacesContacto = document.querySelectorAll('a[href="#contacto"]');
  var camposContacto = [campoNombre, campoTelefono, campoCorreo, campoMensaje];

  var formatoMoneda = new Intl.NumberFormat("es-PE", {
    style: "currency",
    currency: "PEN",
    maximumFractionDigits: 0
  });

  function obtenerSaludo() {
    var hora = new Date().getHours();
    if (hora < 12) return "Buenos días";
    if (hora < 19) return "Buenas tardes";
    return "Buenas noches";
  }

  function formatearFecha() {
    return new Date().toLocaleDateString("es-PE", {
      weekday: "long",
      day: "numeric",
      month: "long",
      year: "numeric"
    });
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

  function guardarNombre(nombre) {
    sessionStorage.setItem("netsalVisitante", nombre);
  }

  function obtenerNombreGuardado() {
    return sessionStorage.getItem("netsalVisitante") || "";
  }

  function abrirBienvenida() {
    if (!modalBienvenida) return;
    modalBienvenida.classList.add("abierto");
    document.body.classList.add("modal-abierto");

    var nombre = obtenerNombreGuardado();
    if (nombre && nombreVisitante) nombreVisitante.value = nombre;

    window.setTimeout(function () {
      if (nombreVisitante) nombreVisitante.focus();
    }, 320);
  }

  function cerrarBienvenida() {
    if (!modalBienvenida) return;
    modalBienvenida.classList.remove("abierto");
    document.body.classList.remove("modal-abierto");
    sessionStorage.setItem("netsalBienvenida", "visto");
  }

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
    if (aviso && aviso.id !== "error-nombre") aviso.remove();
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

  function calcularPresupuesto() {
    if (!selectTrabajo || !rangoPiezas) return null;

    var opcion = selectTrabajo.selectedOptions[0];
    var cantidad = Number(rangoPiezas.value);
    var precio = Number(opcion.dataset.precio || 0);
    var total = cantidad * precio;

    if (valorPiezas) valorPiezas.textContent = cantidad;
    if (totalPresupuesto) totalPresupuesto.textContent = formatoMoneda.format(total);

    var minimo = Number(rangoPiezas.min);
    var maximo = Number(rangoPiezas.max);
    var porcentaje = maximo > minimo ? ((cantidad - minimo) / (maximo - minimo)) * 100 : 0;
    rangoPiezas.style.setProperty("--porcentaje", porcentaje + "%");

    return {
      cantidad: cantidad,
      precio: precio,
      total: total,
      tipo: opcion.textContent.trim()
    };
  }

  function agendarConPresupuesto() {
    var resultado = calcularPresupuesto();
    if (!resultado) return;

    var destino = document.getElementById("contacto");
    if (destino) destino.scrollIntoView({ behavior: "smooth" });

    window.setTimeout(function () {
      if (campoAsunto) {
        var coincidencia = Array.prototype.find.call(campoAsunto.options, function (opcion) {
          return opcion.textContent.trim() === resultado.tipo;
        });
        if (coincidencia) campoAsunto.value = coincidencia.value;
      }

      if (campoMensaje) {
        campoMensaje.value =
          "Estimación referencial del prototipo: " + resultado.cantidad + " × " + resultado.tipo +
          ". Total aproximado " + formatoMoneda.format(resultado.total) +
          ". Solicito la confirmación del presupuesto final.";
        actualizarContadorMensaje();
      }

      if (campoNombre) campoNombre.focus({ preventScroll: true });
      mostrarToast("Presupuesto referencial cargado en el formulario", "info");
    }, 700);
  }

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

  function actualizarProgreso() {
    if (!barraProgreso) return;
    var alturaTotal = document.documentElement.scrollHeight - window.innerHeight;
    var progreso = alturaTotal > 0 ? (window.scrollY / alturaTotal) * 100 : 0;
    barraProgreso.style.width = progreso + "%";
  }

  function iniciarBienvenida() {
    if (textoSaludo) {
      textoSaludo.textContent =
        obtenerSaludo() + ". Hoy es " + formatearFecha() +
        ". Nos alegra que visites el prototipo de nuestro laboratorio dental digital. " +
        "Cuéntanos tu nombre para atenderte mejor.";
    }

    if (sessionStorage.getItem("netsalBienvenida") === "visto") {
      var nombre = obtenerNombreGuardado();
      if (nombre) {
        window.setTimeout(function () {
          mostrarToast(obtenerSaludo() + ", " + nombre + ". ¡Bienvenido de nuevo a NETSAL!", "info");
        }, 700);
      }
      return;
    }

    window.setTimeout(abrirBienvenida, 900);
  }

  function iniciarEventos() {
    if (botonTema) botonTema.addEventListener("click", alternarTema);
    aplicarTema(localStorage.getItem("netsalTema") || "claro");

    if (botonBienvenida) {
      botonBienvenida.addEventListener("click", function () {
        abrirBienvenida();
        mostrarToast("Aquí tienes de nuevo el mensaje de bienvenida", "info");
      });
    }

    if (cerrarModal) cerrarModal.addEventListener("click", cerrarBienvenida);
    if (omitirBienvenida) omitirBienvenida.addEventListener("click", cerrarBienvenida);

    if (modalBienvenida) {
      modalBienvenida.addEventListener("click", function (evento) {
        if (evento.target === modalBienvenida) cerrarBienvenida();
      });
    }

    if (formularioBienvenida) {
      formularioBienvenida.addEventListener("submit", function (evento) {
        evento.preventDefault();

        var nombre = nombreVisitante ? nombreVisitante.value.trim() : "";
        if (nombre.length < 2) {
          if (nombreVisitante) nombreVisitante.classList.add("campo-invalido");
          if (errorNombre) errorNombre.textContent = "Escribe tu nombre para saludarte mejor.";
          if (nombreVisitante) nombreVisitante.focus();
          return;
        }

        if (nombreVisitante) nombreVisitante.classList.remove("campo-invalido");
        if (errorNombre) errorNombre.textContent = "";
        guardarNombre(nombre);
        cerrarBienvenida();
        mostrarToast(obtenerSaludo() + ", " + nombre + ". ¡Bienvenido a NETSAL!", "exito");
      });
    }

    if (nombreVisitante) {
      nombreVisitante.addEventListener("input", function () {
        nombreVisitante.classList.remove("campo-invalido");
        if (errorNombre) errorNombre.textContent = "";
      });
    }

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

    if (selectTrabajo) {
      selectTrabajo.addEventListener("change", function () {
        var resultado = calcularPresupuesto();
        if (resultado) mostrarToast("Restauración: " + resultado.tipo, "info");
      });
      calcularPresupuesto();
    }

    if (rangoPiezas) {
      rangoPiezas.addEventListener("input", calcularPresupuesto);
      rangoPiezas.addEventListener("change", function () {
        var resultado = calcularPresupuesto();
        if (resultado) mostrarToast("Seleccionaste " + resultado.cantidad + " pieza(s)", "info");
      });
    }

    if (botonAgendarPresupuesto) {
      botonAgendarPresupuesto.addEventListener("click", agendarConPresupuesto);
    }

    window.addEventListener("scroll", actualizarProgreso, { passive: true });
    window.addEventListener("resize", actualizarProgreso);

    actualizarReloj();
    window.setInterval(actualizarReloj, 1000);
    actualizarProgreso();
    resaltarHorarioHoy();
    iniciarBienvenida();
  }

  document.addEventListener("DOMContentLoaded", iniciarEventos);
})();
