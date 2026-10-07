/* ============================================================
   js/sistema.js
   Simulación del sistema web de gestión y seguimiento de trabajos
   dentales de NETSAL: login con roles, panel principal, órdenes,
   seguimiento, clientes, materiales, entregas y reportes.
   Prototipo académico: los datos son simulados y viven en el navegador.
   ============================================================ */

(function () {
  "use strict";

  /* ============================================================
     DATOS SIMULADOS
     ============================================================ */

  var etapas = [
    { id: "recibido", nombre: "Recibido" },
    { id: "diseno", nombre: "Diseño" },
    { id: "fresado", nombre: "Fresado" },
    { id: "horno", nombre: "Horno / acabado" },
    { id: "control", nombre: "Control de calidad" },
    { id: "listo", nombre: "Listo" }
  ];

  var preciosTrabajo = {
    "Zirconio Multicapa": 180,
    "Disilicato de Litio (E.max)": 220,
    "Barra de Titanio DMLS": 950,
    "Guía Quirúrgica 3D": 350,
    "PEEK Dental": 280,
    "Placa Miorelajante": 120
  };

  var usuarios = [
    { correo: "admin@netsal.pe", clave: "123456", nombre: "Jhande Vargas", rol: "Administrador" },
    { correo: "recepcion@netsal.pe", clave: "123456", nombre: "Kiara Quispe", rol: "Recepción" },
    { correo: "diseno@netsal.pe", clave: "123456", nombre: "Paul Huamán", rol: "Técnico de diseño" },
    { correo: "fresado@netsal.pe", clave: "123456", nombre: "Ruth Cárdenas", rol: "Técnico de fresado y acabados" }
  ];

  var modulos = [
    {
      id: "panel",
      nombre: "Panel principal",
      roles: ["Administrador", "Recepción", "Técnico de diseño", "Técnico de fresado y acabados"]
    },
    {
      id: "ordenes",
      nombre: "Órdenes de trabajo",
      roles: ["Administrador", "Recepción", "Técnico de diseño", "Técnico de fresado y acabados"]
    },
    {
      id: "seguimiento",
      nombre: "Seguimiento de producción",
      roles: ["Administrador", "Técnico de diseño", "Técnico de fresado y acabados"]
    },
    { id: "clientes", nombre: "Clientes", roles: ["Administrador", "Recepción"] },
    { id: "materiales", nombre: "Materiales e inventario", roles: ["Administrador", "Técnico de fresado y acabados"] },
    { id: "entregas", nombre: "Entregas", roles: ["Administrador", "Recepción"] },
    { id: "reportes", nombre: "Reportes", roles: ["Administrador", "Recepción"] }
  ];

  var clientesBase = [
    { nombre: "Dr. Manuel Torres", tipo: "Odontólogo", telefono: "984 111 222", correo: "mtorres@clinicadental.pe", direccion: "Av. El Sol 123, Cusco" },
    { nombre: "Dra. Lucía Mendoza", tipo: "Cirujana implantóloga", telefono: "984 333 444", correo: "lmendoza@implant.pe", direccion: "Calle Plateros 210, Cusco" },
    { nombre: "Clínica Dental San Blas", tipo: "Clínica", telefono: "984 555 666", correo: "contacto@sanblasdental.pe", direccion: "Jr. Tandapata 456, Cusco" },
    { nombre: "Dr. Roberto Farfán", tipo: "Director de clínica", telefono: "984 777 888", correo: "rfarfan@odontologia.pe", direccion: "Av. de la Cultura 1420, Cusco" },
    { nombre: "Centro Odontológico Wimpillay", tipo: "Clínica", telefono: "984 999 000", correo: "citas@wimpillay.pe", direccion: "Av. Velasco Astete 890, Cusco" }
  ];

  var materialesBase = [
    { nombre: "Bloque de Zirconio multicapa 98 mm", unidad: "bloque", costo: 320, stock: 8, minimo: 4 },
    { nombre: "Lingote de Disilicato de Litio", unidad: "lingote", costo: 180, stock: 2, minimo: 5 },
    { nombre: "Polvo de Titanio DMLS", unidad: "kg", costo: 1450, stock: 3, minimo: 2 },
    { nombre: "Resina biocompatible 3D", unidad: "litro", costo: 210, stock: 1, minimo: 3 },
    { nombre: "Yeso piedra tipo IV", unidad: "bolsa", costo: 45, stock: 14, minimo: 6 },
    { nombre: "Fresas CAD/CAM 2.5 mm", unidad: "unidad", costo: 38, stock: 9, minimo: 10 }
  ];

  var ordenesBase = [
    { codigo: "OT-2026-001", cliente: "Dr. Manuel Torres", paciente: "R. Q.", trabajo: "Zirconio Multicapa", piezas: 3, estado: "entregado", ingreso: "2026-09-28", entrega: "2026-10-01", responsable: "Paul Huamán" },
    { codigo: "OT-2026-002", cliente: "Dra. Lucía Mendoza", paciente: "M. S.", trabajo: "Barra de Titanio DMLS", piezas: 1, estado: "control", ingreso: "2026-09-30", entrega: "2026-10-06", responsable: "Ruth Cárdenas" },
    { codigo: "OT-2026-003", cliente: "Clínica Dental San Blas", paciente: "J. P.", trabajo: "Disilicato de Litio (E.max)", piezas: 4, estado: "fresado", ingreso: "2026-10-01", entrega: "2026-10-05", responsable: "Paul Huamán" },
    { codigo: "OT-2026-004", cliente: "Dr. Roberto Farfán", paciente: "A. L.", trabajo: "Guía Quirúrgica 3D", piezas: 2, estado: "diseno", ingreso: "2026-10-03", entrega: "2026-10-08", responsable: "Paul Huamán" },
    { codigo: "OT-2026-005", cliente: "Centro Odontológico Wimpillay", paciente: "C. M.", trabajo: "Zirconio Multicapa", piezas: 6, estado: "horno", ingreso: "2026-10-02", entrega: "2026-10-09", responsable: "Ruth Cárdenas" },
    { codigo: "OT-2026-006", cliente: "Dra. Lucía Mendoza", paciente: "F. T.", trabajo: "PEEK Dental", piezas: 2, estado: "listo", ingreso: "2026-09-29", entrega: "2026-10-06", responsable: "Ruth Cárdenas" },
    { codigo: "OT-2026-007", cliente: "Clínica Dental San Blas", paciente: "L. V.", trabajo: "Placa Miorelajante", piezas: 1, estado: "entregado", ingreso: "2026-09-26", entrega: "2026-10-02", responsable: "Paul Huamán" },
    { codigo: "OT-2026-008", cliente: "Dr. Manuel Torres", paciente: "S. R.", trabajo: "Disilicato de Litio (E.max)", piezas: 2, estado: "recibido", ingreso: "2026-10-06", entrega: "2026-10-12", responsable: "Paul Huamán" },
    { codigo: "OT-2026-009", cliente: "Dr. Roberto Farfán", paciente: "P. N.", trabajo: "Zirconio Multicapa", piezas: 1, estado: "recibido", ingreso: "2026-10-07", entrega: "2026-10-13", responsable: "Ruth Cárdenas" }
  ];

  var entregasBase = [
    { codigo: "OT-2026-001", cliente: "Dr. Manuel Torres", tipo: "Recojo", fecha: "2026-10-01", confirmada: true },
    { codigo: "OT-2026-007", cliente: "Clínica Dental San Blas", tipo: "Envío", fecha: "2026-10-02", confirmada: true },
    { codigo: "OT-2026-006", cliente: "Dra. Lucía Mendoza", tipo: "Recojo", fecha: "2026-10-06", confirmada: false },
    { codigo: "OT-2026-002", cliente: "Dra. Lucía Mendoza", tipo: "Envío", fecha: "2026-10-06", confirmada: false }
  ];

  /* ============================================================
     ESTADO DE LA SIMULACIÓN
     ============================================================ */

  var estadoApp = {
    sesion: null,
    modulo: "panel",
    filtroOrdenes: { texto: "", estado: "todos" },
    filtroClientes: "",
    ordenActiva: null
  };

  var ordenes = [];
  var clientes = [];
  var materiales = [];
  var entregas = [];

  /* ============================================================
     UTILIDADES
     ============================================================ */

  function clonar(valor) {
    return JSON.parse(JSON.stringify(valor));
  }

  function precioOrden(orden) {
    var unitario = preciosTrabajo[orden.trabajo] || 0;
    return unitario * orden.piezas;
  }

  function formatoMoneda(valor) {
    return new Intl.NumberFormat("es-PE", {
      style: "currency",
      currency: "PEN",
      maximumFractionDigits: 0
    }).format(valor);
  }

  function formatoFecha(iso) {
    if (!iso) return "—";
    var partes = iso.split("-");
    return partes[2] + "/" + partes[1] + "/" + partes[0];
  }

  function hoyISO() {
    var hoy = new Date();
    var mes = String(hoy.getMonth() + 1).padStart(2, "0");
    var dia = String(hoy.getDate()).padStart(2, "0");
    return hoy.getFullYear() + "-" + mes + "-" + dia;
  }

  function nombreEtapa(id) {
    var encontrada = etapas.filter(function (etapa) { return etapa.id === id; })[0];
    if (encontrada) return encontrada.nombre;
    if (id === "entregado") return "Entregado";
    if (id === "anulada") return "Anulada";
    return id;
  }

  function estaAtrasada(orden) {
    return orden.estado !== "entregado" && orden.estado !== "anulada" && orden.entrega < hoyISO();
  }

  function historialInicial(orden) {
    var indice = 0;
    etapas.forEach(function (etapa, posicion) {
      if (etapa.id === orden.estado) indice = posicion;
    });

    var registros = [];
    for (var i = 0; i <= indice; i++) {
      registros.push({
        estado: etapas[i].id,
        fecha: etapas[i].id === "recibido" ? orden.ingreso : orden.ingreso,
        usuario: orden.responsable
      });
    }

    if (orden.estado === "entregado") {
      registros.push({ estado: "entregado", fecha: orden.entrega, usuario: "Kiara Quispe" });
    }

    return registros;
  }

  function prepararOrdenes(lista) {
    return lista.map(function (orden) {
      var copia = clonar(orden);
      if (!copia.historial) copia.historial = historialInicial(copia);
      return copia;
    });
  }

  function cargarDatos() {
    var guardadas = sessionStorage.getItem("netsalOrdenes");

    if (guardadas) {
      try {
        ordenes = JSON.parse(guardadas);
      } catch (error) {
        ordenes = prepararOrdenes(ordenesBase);
      }
    } else {
      ordenes = prepararOrdenes(ordenesBase);
    }

    clientes = clonar(clientesBase);
    materiales = clonar(materialesBase);
    entregas = clonar(entregasBase);
  }

  function guardarOrdenes() {
    sessionStorage.setItem("netsalOrdenes", JSON.stringify(ordenes));
  }

  function restablecerDatos() {
    sessionStorage.removeItem("netsalOrdenes");
    cargarDatos();
    aviso("Datos de demostración restablecidos", "info");
    renderVista();
  }

  /* ============================================================
     AVISOS
     ============================================================ */

  function aviso(mensaje, tipo) {
    var contenedor = document.getElementById("contenedor-toast");
    if (!contenedor) return;

    var elemento = document.createElement("div");
    elemento.className = "toast toast-" + (tipo || "exito");
    elemento.setAttribute("role", "status");
    elemento.textContent = mensaje;
    contenedor.appendChild(elemento);

    window.setTimeout(function () { elemento.classList.add("mostrar"); }, 30);

    function retirar() {
      elemento.classList.remove("mostrar");
      window.setTimeout(function () { elemento.remove(); }, 380);
    }

    elemento.addEventListener("click", retirar);
    window.setTimeout(retirar, 3600);
  }

  /* ============================================================
     PERMISOS SEGÚN ROL
     ============================================================ */

  function puede(modulo) {
    if (!estadoApp.sesion) return false;
    var encontrado = modulos.filter(function (item) { return item.id === modulo; })[0];
    if (!encontrado) return false;
    return encontrado.roles.indexOf(estadoApp.sesion.rol) !== -1;
  }

  function puedeCrearOrden() {
    return estadoApp.sesion && (estadoApp.sesion.rol === "Administrador" || estadoApp.sesion.rol === "Recepción");
  }

  function puedeAvanzarEtapa() {
    return (
      estadoApp.sesion &&
      (estadoApp.sesion.rol === "Administrador" ||
        estadoApp.sesion.rol === "Técnico de diseño" ||
        estadoApp.sesion.rol === "Técnico de fresado y acabados")
    );
  }

  function puedeAnular() {
    return estadoApp.sesion && estadoApp.sesion.rol === "Administrador";
  }

  function puedeEntregar() {
    return estadoApp.sesion && (estadoApp.sesion.rol === "Administrador" || estadoApp.sesion.rol === "Recepción");
  }

  /* ============================================================
     ICONOS
     ============================================================ */

  var iconos = {
    panel:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
    ordenes:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/></svg>',
    seguimiento:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="6" r="2.5"/><circle cx="18" cy="12" r="2.5"/><circle cx="6" cy="18" r="2.5"/><path d="M8.5 6H14a4 4 0 0 1 4 4"/><path d="M8.5 18H14a4 4 0 0 0 4-4"/></svg>',
    clientes:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c0-3.2 2.9-5.5 6.5-5.5s6.5 2.3 6.5 5.5"/><path d="M17 8.5a2.6 2.6 0 0 1 0 5"/><path d="M18 20c0-2.2-.8-4-2-5"/></svg>',
    materiales:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5 9 5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>',
    entregas:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/></svg>',
    reportes:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/></svg>'
  };

  /* ============================================================
     VISTAS
     ============================================================ */

  function cabecera(titulo, descripcion, acciones) {
    return (
      '<div class="vista-cabecera">' +
      "<div>" +
      '<span class="eyebrow">Sistema de gestión · NETSAL</span>' +
      "<h1>" + titulo + "</h1>" +
      "<p>" + descripcion + "</p>" +
      "</div>" +
      (acciones ? '<div class="vista-cabecera-acciones">' + acciones + "</div>" : "") +
      "</div>"
    );
  }

  function vistaPanel() {
    var pendientes = ordenes.filter(function (o) { return o.estado !== "entregado" && o.estado !== "anulada"; });
    var atrasadas = pendientes.filter(estaAtrasada);
    var entregadas = ordenes.filter(function (o) { return o.estado === "entregado"; });
    var bajos = materiales.filter(function (m) { return m.stock < m.minimo; });

    var html = "";

    html += cabecera(
      "Panel principal",
      "Resumen del día: trabajos pendientes, atrasados, entregados y alertas de inventario.",
      '<span class="estado-badge estado-recibido">' + estadoApp.sesion.rol + "</span>"
    );

    html +=
      '<div class="kpi-grid">' +
      '<div class="kpi-card"><span>Trabajos pendientes</span><b>' + pendientes.length + "</b><small>En producción ahora</small></div>" +
      '<div class="kpi-card kpi-alerta"><span>Trabajos atrasados</span><b>' + atrasadas.length + "</b><small>Entrega vencida</small></div>" +
      '<div class="kpi-card kpi-ok"><span>Trabajos entregados</span><b>' + entregadas.length + "</b><small>Cerrados</small></div>" +
      '<div class="kpi-card kpi-alerta"><span>Insumos bajo mínimo</span><b>' + bajos.length + "</b><small>Requieren compra</small></div>" +
      "</div>";

    var recientes = ordenes.slice(0, 5);
    html +=
      '<div class="panel-grid">' +
      '<div class="panel-card">' +
      '<div class="panel-card-head"><div><h2>Trabajos recientes</h2><p>Últimas órdenes registradas en el laboratorio</p></div>' +
      '<button type="button" class="btn btn-outline-dark btn-sm" data-ir="ordenes">Ver todas</button></div>' +
      '<div class="sis-tabla-wrap">' +
      '<table class="sis-tabla"><thead><tr><th>Código</th><th>Cliente</th><th>Trabajo</th><th>Estado</th><th>Entrega</th><th></th></tr></thead><tbody>' +
      recientes
        .map(function (orden) {
          return (
            '<tr class="' + (estaAtrasada(orden) ? "fila-atrasada" : "") + '">' +
            '<td class="celda-codigo">' + orden.codigo + "</td>" +
            "<td>" + orden.cliente + "</td>" +
            "<td>" + orden.trabajo + " · " + orden.piezas + " pza.</td>" +
            "<td>" + etiquetaEstado(orden.estado) + "</td>" +
            '<td class="' + (estaAtrasada(orden) ? "fecha-atrasada" : "") + '">' + formatoFecha(orden.entrega) + "</td>" +
            '<td><button type="button" class="boton-mini" data-detalle="' + orden.codigo + '">Ver</button></td>' +
            "</tr>"
          );
        })
        .join("") +
      "</tbody></table></div></div>" +

      "<div>" +
      '<div class="panel-card">' +
      '<div class="panel-card-head"><div><h2>Alertas de inventario</h2><p>Materiales por debajo del stock mínimo</p></div></div>' +
      '<div class="panel-card-body">' +
      (bajos.length
        ? '<div class="lista-alertas">' +
          bajos
            .map(function (material) {
              return (
                '<div class="alerta-item"><div><strong>' + material.nombre + "</strong>" +
                "<span>Stock " + material.stock + " de " + material.minimo + " " + material.unidad + "(s) mínimo</span></div>" +
                '<span class="estado-badge estado-fresado">Reponer</span></div>'
              );
            })
            .join("") +
          "</div>"
        : '<p class="lista-vacia">Sin alertas: el inventario está por encima del mínimo.</p>') +
      "</div></div>" +

      '<div class="panel-card">' +
      '<div class="panel-card-head"><div><h2>Trabajos por estado</h2><p>Distribución de toda la producción</p></div></div>' +
      '<div class="panel-card-body">' + barrasPorEstado() + "</div></div>" +
      "</div>" +
      "</div>";

    return html;
  }

  function vistaOrdenes() {
    var texto = estadoApp.filtroOrdenes.texto.toLowerCase();
    var filtroEstado = estadoApp.filtroOrdenes.estado;

    var lista = ordenes.filter(function (orden) {
      var coincideTexto =
        !texto ||
        orden.codigo.toLowerCase().indexOf(texto) !== -1 ||
        orden.cliente.toLowerCase().indexOf(texto) !== -1 ||
        orden.trabajo.toLowerCase().indexOf(texto) !== -1 ||
        orden.responsable.toLowerCase().indexOf(texto) !== -1;

      var coincideEstado = filtroEstado === "todos" || orden.estado === filtroEstado;
      return coincideTexto && coincideEstado;
    });

    var opciones = '<option value="todos">Todos los estados</option>';
    etapas.forEach(function (etapa) {
      opciones += '<option value="' + etapa.id + '"' + (filtroEstado === etapa.id ? " selected" : "") + ">" + etapa.nombre + "</option>";
    });
    opciones += '<option value="entregado"' + (filtroEstado === "entregado" ? " selected" : "") + ">Entregado</option>";
    opciones += '<option value="anulada"' + (filtroEstado === "anulada" ? " selected" : "") + ">Anulada</option>";

    var html = "";

    html += cabecera(
      "Órdenes de trabajo",
      "Registro, consulta y control de los trabajos protésicos recibidos de odontólogos y clínicas.",
      puedeCrearOrden()
        ? '<button type="button" class="btn btn-primary btn-sm" id="boton-nueva-orden">+ Nueva orden</button>'
        : ""
    );

    html +=
      '<div class="panel-card">' +
      '<div class="panel-card-head">' +
      '<div class="filtros">' +
      '<input type="search" id="filtro-texto-ordenes" placeholder="Buscar por código, cliente, trabajo o responsable" value="' + estadoApp.filtroOrdenes.texto + '">' +
      '<select id="filtro-estado-ordenes">' + opciones + "</select>" +
      "</div>" +
      "<p>" + lista.length + " de " + ordenes.length + " órdenes</p>" +
      "</div>" +
      '<div class="sis-tabla-wrap">' +
      '<table class="sis-tabla"><thead><tr>' +
      "<th>Código</th><th>Cliente</th><th>Paciente</th><th>Trabajo</th><th>Piezas</th>" +
      "<th>Estado</th><th>Ingreso</th><th>Entrega</th><th>Precio</th><th>Responsable</th><th></th>" +
      "</tr></thead><tbody>";

    if (!lista.length) {
      html += '<tr><td colspan="11"><p class="lista-vacia">No se encontraron órdenes con ese criterio.</p></td></tr>';
    } else {
      lista.forEach(function (orden) {
        html +=
          '<tr class="' + (estaAtrasada(orden) ? "fila-atrasada" : "") + '">' +
          '<td class="celda-codigo">' + orden.codigo + "</td>" +
          "<td>" + orden.cliente + "</td>" +
          "<td>" + orden.paciente + "</td>" +
          "<td>" + orden.trabajo + "</td>" +
          '<td class="celda-numero">' + orden.piezas + "</td>" +
          "<td>" + etiquetaEstado(orden.estado) + "</td>" +
          "<td>" + formatoFecha(orden.ingreso) + "</td>" +
          '<td class="' + (estaAtrasada(orden) ? "fecha-atrasada" : "") + '">' + formatoFecha(orden.entrega) + "</td>" +
          '<td class="celda-numero">' + formatoMoneda(precioOrden(orden)) + "</td>" +
          "<td>" + orden.responsable + "</td>" +
          '<td><button type="button" class="boton-mini" data-detalle="' + orden.codigo + '">Detalle</button></td>' +
          "</tr>";
      });
    }

    html += "</tbody></table></div></div>";

    return html;
  }

  function vistaSeguimiento() {
    var produccion = ordenes.filter(function (orden) {
      return orden.estado !== "entregado" && orden.estado !== "anulada";
    });

    var html = "";

    html += cabecera(
      "Seguimiento de producción",
      "Etapas del proceso: recibido, diseño, fresado, horno/acabado, control de calidad y listo."
    );

    html +=
      '<div class="panel-card"><div class="sis-tabla-wrap">' +
      '<table class="sis-tabla"><thead><tr>' +
      "<th>Código</th><th>Cliente</th><th>Trabajo</th><th>Estado actual</th><th>Avance de etapas</th><th>Entrega</th><th>Responsable</th><th></th>" +
      "</tr></thead><tbody>";

    if (!produccion.length) {
      html += '<tr><td colspan="8"><p class="lista-vacia">No hay trabajos en producción.</p></td></tr>';
    } else {
      produccion.forEach(function (orden) {
        html +=
          '<tr class="' + (estaAtrasada(orden) ? "fila-atrasada" : "") + '">' +
          '<td class="celda-codigo">' + orden.codigo + "</td>" +
          "<td>" + orden.cliente + "</td>" +
          "<td>" + orden.trabajo + "</td>" +
          "<td>" + etiquetaEstado(orden.estado) + "</td>" +
          "<td>" + barraEtapas(orden.estado) + "</td>" +
          '<td class="' + (estaAtrasada(orden) ? "fecha-atrasada" : "") + '">' + formatoFecha(orden.entrega) + "</td>" +
          "<td>" + orden.responsable + "</td>" +
          "<td>" +
          (puedeAvanzarEtapa() && orden.estado !== "listo"
            ? '<button type="button" class="boton-mini boton-mini-primario" data-avanzar="' + orden.codigo + '">Avanzar etapa</button>'
            : '<button type="button" class="boton-mini" data-detalle="' + orden.codigo + '">Detalle</button>') +
          "</td>" +
          "</tr>";
      });
    }

    html += "</tbody></table></div></div>";
    return html;
  }

  function vistaClientes() {
    var texto = estadoApp.filtroClientes.toLowerCase();

    var lista = clientes.filter(function (cliente) {
      return (
        !texto ||
        cliente.nombre.toLowerCase().indexOf(texto) !== -1 ||
        cliente.tipo.toLowerCase().indexOf(texto) !== -1
      );
    });

    var html = "";

    html += cabecera(
      "Clientes",
      "Odontólogos y clínicas que envían trabajos al laboratorio, con su historial de órdenes."
    );

    html +=
      '<div class="panel-card">' +
      '<div class="panel-card-head">' +
      '<div class="filtros"><input type="search" id="filtro-clientes" placeholder="Buscar por nombre o tipo de cliente" value="' + estadoApp.filtroClientes + '"></div>' +
      "<p>" + lista.length + " cliente(s)</p>" +
      "</div>" +
      '<div class="sis-tabla-wrap">' +
      '<table class="sis-tabla"><thead><tr>' +
      "<th>Cliente</th><th>Tipo</th><th>Teléfono</th><th>Correo</th><th>Dirección</th><th>Órdenes</th><th>Facturado</th>" +
      "</tr></thead><tbody>";

    if (!lista.length) {
      html += '<tr><td colspan="7"><p class="lista-vacia">No se encontraron clientes.</p></td></tr>';
    } else {
      lista.forEach(function (cliente) {
        var suyas = ordenes.filter(function (orden) {
          return orden.cliente === cliente.nombre && orden.estado !== "anulada";
        });
        var total = suyas.reduce(function (suma, orden) { return suma + precioOrden(orden); }, 0);

        html +=
          "<tr><td><strong>" + cliente.nombre + "</strong></td>" +
          "<td>" + cliente.tipo + "</td>" +
          "<td>" + cliente.telefono + "</td>" +
          "<td>" + cliente.correo + "</td>" +
          "<td>" + cliente.direccion + "</td>" +
          '<td class="celda-numero">' + suyas.length + "</td>" +
          '<td class="celda-numero">' + formatoMoneda(total) + "</td></tr>";
      });
    }

    html += "</tbody></table></div></div>";
    return html;
  }

  function vistaMateriales() {
    var bajos = materiales.filter(function (material) { return material.stock < material.minimo; });
    var valorInventario = materiales.reduce(function (suma, material) {
      return suma + material.stock * material.costo;
    }, 0);

    var html = "";

    html += cabecera(
      "Materiales e inventario",
      "Control de insumos, costos unitarios y stock mínimo para la producción del laboratorio."
    );

    html +=
      '<div class="kpi-grid">' +
      '<div class="kpi-card"><span>Materiales registrados</span><b>' + materiales.length + "</b><small>Insumos activos</small></div>" +
      '<div class="kpi-card kpi-alerta"><span>Bajo stock mínimo</span><b>' + bajos.length + "</b><small>Requieren reposición</small></div>" +
      '<div class="kpi-card kpi-ok"><span>Valor del inventario</span><b>' + formatoMoneda(valorInventario) + "</b><small>Stock × costo unitario</small></div>" +
      "</div>";

    html +=
      '<div class="panel-card"><div class="sis-tabla-wrap">' +
      '<table class="sis-tabla"><thead><tr>' +
      "<th>Material</th><th>Unidad</th><th>Costo unitario</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th>Valor en almacén</th>" +
      "</tr></thead><tbody>";

    materiales.forEach(function (material) {
      var bajo = material.stock < material.minimo;
      html +=
        "<tr>" +
        "<td><strong>" + material.nombre + "</strong></td>" +
        "<td>" + material.unidad + "</td>" +
        '<td class="celda-numero">' + formatoMoneda(material.costo) + "</td>" +
        '<td class="celda-numero">' + material.stock + "</td>" +
        '<td class="celda-numero">' + material.minimo + "</td>" +
        "<td>" +
        (bajo
          ? '<span class="estado-badge estado-fresado">Stock bajo</span>'
          : '<span class="estado-badge estado-listo">Disponible</span>') +
        "</td>" +
        '<td class="celda-numero">' + formatoMoneda(material.stock * material.costo) + "</td>" +
        "</tr>";
    });

    html += "</tbody></table></div></div>";
    return html;
  }

  function vistaEntregas() {
    var pendientes = entregas.filter(function (entrega) { return !entrega.confirmada; });

    var html = "";

    html += cabecera(
      "Entregas",
      "Registro de recojo o envío de los trabajos terminados y su confirmación de entrega."
    );

    html +=
      '<div class="kpi-grid">' +
      '<div class="kpi-card"><span>Entregas registradas</span><b>' + entregas.length + "</b><small>Total del período</small></div>" +
      '<div class="kpi-card kpi-alerta"><span>Por confirmar</span><b>' + pendientes.length + "</b><small>Pendientes de cierre</small></div>" +
      '<div class="kpi-card kpi-ok"><span>Confirmadas</span><b>' + (entregas.length - pendientes.length) + "</b><small>Cerradas</small></div>" +
      "</div>";

    html +=
      '<div class="panel-card"><div class="sis-tabla-wrap">' +
      '<table class="sis-tabla"><thead><tr>' +
      "<th>Código de orden</th><th>Cliente</th><th>Tipo</th><th>Fecha</th><th>Confirmación</th><th></th>" +
      "</tr></thead><tbody>";

    if (!entregas.length) {
      html += '<tr><td colspan="6"><p class="lista-vacia">Todavía no hay entregas registradas.</p></td></tr>';
    } else {
      entregas.forEach(function (entrega) {
        html +=
          "<tr>" +
          '<td class="celda-codigo">' + entrega.codigo + "</td>" +
          "<td>" + entrega.cliente + "</td>" +
          "<td>" + entrega.tipo + "</td>" +
          "<td>" + formatoFecha(entrega.fecha) + "</td>" +
          "<td>" +
          (entrega.confirmada
            ? '<span class="estado-badge estado-entregado">Confirmada</span>'
            : '<span class="estado-badge estado-control">Pendiente</span>') +
          "</td>" +
          "<td>" +
          (entrega.confirmada || !puedeEntregar()
            ? ""
            : '<button type="button" class="boton-mini boton-mini-primario" data-confirmar="' + entrega.codigo + '">Confirmar entrega</button>') +
          "</td></tr>";
      });
    }

    html += "</tbody></table></div></div>";
    return html;
  }

  function vistaReportes() {
    var validas = ordenes.filter(function (orden) { return orden.estado !== "anulada"; });
    var pendientes = validas.filter(function (orden) { return orden.estado !== "entregado"; });
    var atrasadas = pendientes.filter(estaAtrasada);
    var ingreso = validas.reduce(function (suma, orden) { return suma + precioOrden(orden); }, 0);

    var porCliente = {};
    validas.forEach(function (orden) {
      porCliente[orden.cliente] = (porCliente[orden.cliente] || 0) + precioOrden(orden);
    });

    var ranking = Object.keys(porCliente)
      .map(function (nombre) { return { nombre: nombre, total: porCliente[nombre] }; })
      .sort(function (a, b) { return b.total - a.total; });

    var html = "";

    html += cabecera(
      "Reportes",
      "Trabajos pendientes, atrasados y entregados; distribución por estado e ingresos estimados."
    );

    html +=
      '<div class="kpi-grid">' +
      '<div class="kpi-card"><span>Órdenes registradas</span><b>' + validas.length + "</b><small>Sin contar anuladas</small></div>" +
      '<div class="kpi-card kpi-alerta"><span>Pendientes</span><b>' + pendientes.length + "</b><small>En proceso</small></div>" +
      '<div class="kpi-card kpi-alerta"><span>Atrasadas</span><b>' + atrasadas.length + "</b><small>Con entrega vencida</small></div>" +
      '<div class="kpi-card kpi-ok"><span>Ingreso estimado</span><b>' + formatoMoneda(ingreso) + "</b><small>Según precio por pieza</small></div>" +
      "</div>";

    html +=
      '<div class="panel-grid">' +
      '<div class="panel-card">' +
      '<div class="panel-card-head"><div><h2>Distribución por estado</h2><p>Cantidad de órdenes en cada etapa del proceso</p></div></div>' +
      '<div class="panel-card-body">' + barrasPorEstado() + "</div></div>" +

      '<div class="panel-card">' +
      '<div class="panel-card-head"><div><h2>Clientes por facturación</h2><p>Monto estimado según las órdenes registradas</p></div></div>' +
      '<div class="panel-card-body"><div class="lista-alertas">' +
      ranking
        .map(function (item) {
          return (
            '<div class="alerta-item"><div><strong>' + item.nombre + "</strong>" +
            "<span>" + formatoMoneda(item.total) + "</span></div></div>"
          );
        })
        .join("") +
      "</div></div></div>" +
      "</div>";

    return html;
  }

  /* ============================================================
     COMPONENTES
     ============================================================ */

  function etiquetaEstado(estado) {
    return '<span class="estado-badge estado-' + estado + '">' + nombreEtapa(estado) + "</span>";
  }

  function barraEtapas(estadoActual) {
    var posicion = etapas.map(function (etapa) { return etapa.id; }).indexOf(estadoActual);
    var html = '<div class="etapas" title="' + nombreEtapa(estadoActual) + '">';

    etapas.forEach(function (etapa, indice) {
      var clase = "";
      if (estadoActual === "entregado") clase = "completada";
      else if (indice < posicion) clase = "completada";
      else if (indice === posicion) clase = "actual";
      html += '<span class="etapa ' + clase + '"></span>';
    });

    return html + "</div>";
  }

  function barrasPorEstado() {
    var conteos = {};
    etapas.forEach(function (etapa) { conteos[etapa.id] = 0; });
    conteos.entregado = 0;
    conteos.anulada = 0;

    ordenes.forEach(function (orden) {
      conteos[orden.estado] = (conteos[orden.estado] || 0) + 1;
    });

    var total = ordenes.length || 1;
    var html = '<div class="barras">';

    Object.keys(conteos).forEach(function (clave) {
      var cantidad = conteos[clave];
      if (!cantidad) return;
      var porcentaje = Math.round((cantidad / total) * 100);

      html +=
        '<div class="barra-item">' +
        "<span>" + nombreEtapa(clave) + "</span>" +
        '<span class="barra-pista"><span class="barra-relleno" style="width: ' + porcentaje + '%"></span></span>' +
        '<span class="barra-valor">' + cantidad + "</span>" +
        "</div>";
    });

    return html + "</div>";
  }

  /* ============================================================
     MODAL DE DETALLE
     ============================================================ */

  function abrirDetalle(codigo) {
    var orden = ordenes.filter(function (item) { return item.codigo === codigo; })[0];
    if (!orden) return;

    estadoApp.ordenActiva = codigo;

    var modal = document.getElementById("modal-sistema");
    var contenido = document.getElementById("modal-sistema-contenido");

    var opcionesEstado = etapas
      .map(function (etapa) {
        return '<option value="' + etapa.id + '"' + (orden.estado === etapa.id ? " selected" : "") + ">" + etapa.nombre + "</option>";
      })
      .join("");

    contenido.innerHTML =
      "<h2>" + orden.codigo + "</h2>" +
      "<p>" + orden.trabajo + " · " + orden.piezas + " pieza(s) · " + formatoMoneda(precioOrden(orden)) + "</p>" +

      '<div class="detalle-grid">' +
      '<div class="detalle-item"><span>Cliente</span><strong>' + orden.cliente + "</strong></div>" +
      '<div class="detalle-item"><span>Paciente</span><strong>' + orden.paciente + "</strong></div>" +
      '<div class="detalle-item"><span>Estado actual</span><strong>' + nombreEtapa(orden.estado) + "</strong></div>" +
      '<div class="detalle-item"><span>Responsable</span><strong>' + orden.responsable + "</strong></div>" +
      '<div class="detalle-item"><span>Fecha de ingreso</span><strong>' + formatoFecha(orden.ingreso) + "</strong></div>" +
      '<div class="detalle-item"><span>Fecha de entrega</span><strong class="' + (estaAtrasada(orden) ? "fecha-atrasada" : "") + '">' + formatoFecha(orden.entrega) + "</strong></div>" +
      "</div>" +

      "<h2 style=\"font-size: var(--fs-md)\">Historial de estados</h2>" +
      '<ul class="timeline">' +
      orden.historial
        .map(function (registro) {
          return (
            '<li class="hecho"><span class="timeline-punto">✓</span>' +
            '<span class="timeline-detalle"><strong>' + nombreEtapa(registro.estado) + "</strong>" +
            "<span>" + formatoFecha(registro.fecha) + " · " + registro.usuario + "</span></span></li>"
          );
        })
        .join("") +
      "</ul>" +

      '<div class="modal-acciones">' +
      (puedeAvanzarEtapa() && orden.estado !== "entregado" && orden.estado !== "anulada"
        ? '<label class="filtros" style="align-items:center;gap:.5rem"><span style="font-size:var(--fs-xs);color:var(--color-text-muted)">Cambiar estado:</span>' +
          '<select id="detalle-estado">' + opcionesEstado + "</select>" +
          '<button type="button" class="boton-mini boton-mini-primario" data-aplicar-estado="' + orden.codigo + '">Guardar</button></label>'
        : "") +
      (puedeEntregar() && orden.estado === "listo"
        ? '<button type="button" class="boton-mini boton-mini-primario" data-registrar-entrega="' + orden.codigo + '">Registrar entrega</button>'
        : "") +
      (puedeAnular() && orden.estado !== "entregado" && orden.estado !== "anulada"
        ? '<button type="button" class="boton-mini" data-anular="' + orden.codigo + '">Anular orden</button>'
        : "") +
      '<button type="button" class="boton-mini" data-cerrar-modal="1">Cerrar</button>' +
      "</div>";

    modal.classList.add("abierto");
  }

  function cerrarDetalle() {
    var modal = document.getElementById("modal-sistema");
    if (modal) modal.classList.remove("abierto");
    estadoApp.ordenActiva = null;
  }

  /* ============================================================
     MODAL DE NUEVA ORDEN
     ============================================================ */

  function abrirNuevaOrden() {
    var modal = document.getElementById("modal-sistema");
    var contenido = document.getElementById("modal-sistema-contenido");

    var opcionesCliente = clientes
      .map(function (cliente) { return "<option>" + cliente.nombre + "</option>"; })
      .join("");

    var opcionesTrabajo = Object.keys(preciosTrabajo)
      .map(function (trabajo) { return '<option value="' + trabajo + '">' + trabajo + " · " + formatoMoneda(preciosTrabajo[trabajo]) + " por pieza</option>"; })
      .join("");

    var opcionesResponsable = usuarios
      .filter(function (usuario) { return usuario.rol.indexOf("Técnico") === 0; })
      .map(function (usuario) { return "<option>" + usuario.nombre + "</option>"; })
      .join("");

    contenido.innerHTML =
      "<h2>Nueva orden de trabajo</h2>" +
      "<p>Registra la recepción del caso. El código de la orden se genera automáticamente.</p>" +
      '<form id="formulario-nueva-orden" novalidate>' +
      '<div class="sis-form-grid">' +
      '<div class="form-group"><label for="nueva-cliente">Cliente</label><select id="nueva-cliente">' + opcionesCliente + "</select></div>" +
      '<div class="form-group"><label for="nueva-paciente">Paciente (dato mínimo)</label><input type="text" id="nueva-paciente" placeholder="Iniciales del paciente" autocomplete="off"></div>' +
      '<div class="form-group es-ancho"><label for="nueva-trabajo">Tipo de trabajo</label><select id="nueva-trabajo">' + opcionesTrabajo + "</select></div>" +
      '<div class="form-group"><label for="nueva-piezas">Número de piezas</label><input type="number" id="nueva-piezas" min="1" max="20" value="1"></div>' +
      '<div class="form-group"><label for="nueva-entrega">Fecha de entrega</label><input type="date" id="nueva-entrega"></div>' +
      '<div class="form-group"><label for="nueva-responsable">Responsable</label><select id="nueva-responsable">' + opcionesResponsable + "</select></div>" +
      '<div class="form-group"><label>Precio estimado</label><input type="text" id="nueva-precio" value="' + formatoMoneda(preciosTrabajo[Object.keys(preciosTrabajo)[0]]) + '" readonly></div>' +
      "</div>" +
      '<small class="aviso-error" id="error-nueva-orden"></small>' +
      '<div class="sis-form-acciones">' +
      '<button type="button" class="boton-mini" data-cerrar-modal="1">Cancelar</button>' +
      '<button type="submit" class="boton-mini boton-mini-primario">Registrar orden</button>' +
      "</div></form>";

    modal.classList.add("abierto");
  }

  function actualizarPrecioNuevaOrden() {
    var trabajo = document.getElementById("nueva-trabajo");
    var piezas = document.getElementById("nueva-piezas");
    var precio = document.getElementById("nueva-precio");
    if (!trabajo || !piezas || !precio) return;

    var unitario = preciosTrabajo[trabajo.value] || 0;
    var cantidad = Number(piezas.value) || 1;
    precio.value = formatoMoneda(unitario * cantidad);
  }

  function guardarNuevaOrden(evento) {
    evento.preventDefault();

    var cliente = document.getElementById("nueva-cliente").value;
    var paciente = document.getElementById("nueva-paciente").value.trim();
    var trabajo = document.getElementById("nueva-trabajo").value;
    var piezas = Number(document.getElementById("nueva-piezas").value);
    var entrega = document.getElementById("nueva-entrega").value;
    var responsable = document.getElementById("nueva-responsable").value;
    var error = document.getElementById("error-nueva-orden");

    if (paciente.length < 1 || !entrega || piezas < 1) {
      error.textContent = "Completa el paciente, el número de piezas y la fecha de entrega.";
      return;
    }

    error.textContent = "";

    var numero = ordenes.length + 1;
    var codigo = "OT-2026-" + String(numero).padStart(3, "0");

    var nueva = {
      codigo: codigo,
      cliente: cliente,
      paciente: paciente,
      trabajo: trabajo,
      piezas: piezas,
      estado: "recibido",
      ingreso: hoyISO(),
      entrega: entrega,
      responsable: responsable,
      historial: [{ estado: "recibido", fecha: hoyISO(), usuario: estadoApp.sesion.nombre }]
    };

    ordenes.unshift(nueva);
    guardarOrdenes();
    cerrarDetalle();
    renderVista();
    aviso("Orden " + codigo + " registrada correctamente", "exito");
  }

  /* ============================================================
     ACCIONES SOBRE ÓRDENES
     ============================================================ */

  function avanzarEtapa(codigo) {
    var orden = ordenes.filter(function (item) { return item.codigo === codigo; })[0];
    if (!orden) return;

    var posicion = etapas.map(function (etapa) { return etapa.id; }).indexOf(orden.estado);
    if (posicion < 0 || posicion >= etapas.length - 1) return;

    orden.estado = etapas[posicion + 1].id;
    orden.historial.push({ estado: orden.estado, fecha: hoyISO(), usuario: estadoApp.sesion.nombre });

    guardarOrdenes();
    renderVista();
    aviso(orden.codigo + " avanzó a la etapa: " + nombreEtapa(orden.estado), "info");
  }

  function aplicarEstado(codigo) {
    var orden = ordenes.filter(function (item) { return item.codigo === codigo; })[0];
    var selector = document.getElementById("detalle-estado");
    if (!orden || !selector) return;

    if (selector.value === orden.estado) {
      aviso("La orden ya se encuentra en ese estado", "info");
      return;
    }

    orden.estado = selector.value;
    orden.historial.push({ estado: orden.estado, fecha: hoyISO(), usuario: estadoApp.sesion.nombre });

    guardarOrdenes();
    cerrarDetalle();
    renderVista();
    aviso(orden.codigo + " actualizado a: " + nombreEtapa(orden.estado), "exito");
  }

  function anularOrden(codigo) {
    var orden = ordenes.filter(function (item) { return item.codigo === codigo; })[0];
    if (!orden) return;

    orden.estado = "anulada";
    orden.historial.push({ estado: "anulada", fecha: hoyISO(), usuario: estadoApp.sesion.nombre });

    guardarOrdenes();
    cerrarDetalle();
    renderVista();
    aviso("La orden " + codigo + " fue anulada", "error");
  }

  function registrarEntrega(codigo) {
    var orden = ordenes.filter(function (item) { return item.codigo === codigo; })[0];
    if (!orden) return;

    orden.estado = "entregado";
    orden.historial.push({ estado: "entregado", fecha: hoyISO(), usuario: estadoApp.sesion.nombre });

    entregas.unshift({
      codigo: codigo,
      cliente: orden.cliente,
      tipo: "Recojo",
      fecha: hoyISO(),
      confirmada: true
    });

    guardarOrdenes();
    cerrarDetalle();
    renderVista();
    aviso("Entrega registrada para " + codigo, "exito");
  }

  function confirmarEntrega(codigo) {
    var entrega = entregas.filter(function (item) { return item.codigo === codigo && !item.confirmada; })[0];
    if (!entrega) return;

    entrega.confirmada = true;
    renderVista();
    aviso("Entrega de " + codigo + " confirmada", "exito");
  }

  /* ============================================================
     RENDERIZADO DEL PANEL
     ============================================================ */

  function renderMenu() {
    var lista = document.getElementById("sis-lista-modulos");
    if (!lista) return;

    var html = modulos
      .filter(function (modulo) { return puede(modulo.id); })
      .map(function (modulo) {
        return (
          "<li><button type=\"button\" class=\"sis-menu-btn-modulo" +
          (estadoApp.modulo === modulo.id ? " is-activo" : "") +
          '" data-modulo="' + modulo.id + '">' +
          iconos[modulo.id] +
          "<span>" + modulo.nombre + "</span></button></li>"
        );
      })
      .join("");

    lista.innerHTML = html;
  }

  function renderVista() {
    if (!estadoApp.sesion) return;

    if (!puede(estadoApp.modulo)) {
      estadoApp.modulo = "panel";
    }

    var contenedor = document.getElementById("vista-sistema");
    if (!contenedor) return;

    var vistas = {
      panel: vistaPanel,
      ordenes: vistaOrdenes,
      seguimiento: vistaSeguimiento,
      clientes: vistaClientes,
      materiales: vistaMateriales,
      entregas: vistaEntregas,
      reportes: vistaReportes
    };

    contenedor.innerHTML = (vistas[estadoApp.modulo] || vistaPanel)();
    renderMenu();
  }

  function irAlInicioDeVista() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function restaurarFoco(id) {
    var campo = document.getElementById(id);
    if (!campo) return;

    campo.focus();
    var largo = campo.value.length;
    try {
      campo.setSelectionRange(largo, largo);
    } catch (error) {
      /* Algunos navegadores no permiten seleccionar en ciertos campos */
    }
  }

  function cerrarMenuModulos() {
    var menu = document.getElementById("sis-menu");
    var botonMenu = document.getElementById("sis-menu-btn");
    var fondo = document.getElementById("sis-menu-fondo");

    if (menu) menu.classList.remove("abierto");
    if (fondo) fondo.classList.remove("abierto");
    if (botonMenu) {
      botonMenu.setAttribute("aria-expanded", "false");
      botonMenu.setAttribute("aria-label", "Abrir menú de módulos");
    }
  }

  function iniciarSesion(usuario) {
    estadoApp.sesion = { correo: usuario.correo, nombre: usuario.nombre, rol: usuario.rol };
    estadoApp.modulo = "panel";

    sessionStorage.setItem(
      "netsalSesion",
      JSON.stringify({ correo: usuario.correo, nombre: usuario.nombre, rol: usuario.rol })
    );

    document.body.classList.replace("modo-sitio", "modo-app");
    document.getElementById("pantalla-login").hidden = true;
    document.getElementById("panel-sistema").hidden = false;

    document.getElementById("sis-usuario-nombre").textContent = usuario.nombre;
    document.getElementById("sis-usuario-rol").textContent = usuario.rol;
    document.getElementById("sis-avatar").textContent = usuario.nombre
      .split(" ")
      .map(function (parte) { return parte.charAt(0); })
      .join("")
      .slice(0, 2)
      .toUpperCase();

    renderVista();
    irAlInicioDeVista();
    aviso("Bienvenido, " + usuario.nombre + " · " + usuario.rol, "exito");
  }

  function cerrarSesion() {
    estadoApp.sesion = null;
    estadoApp.modulo = "panel";
    sessionStorage.removeItem("netsalSesion");

    document.body.classList.replace("modo-app", "modo-sitio");
    cerrarMenuModulos();

    document.getElementById("panel-sistema").hidden = true;
    document.getElementById("pantalla-login").hidden = false;

    var clave = document.getElementById("login-clave");
    if (clave) clave.value = "";

    var error = document.getElementById("login-error");
    if (error) error.hidden = true;

    aviso("Sesión cerrada", "info");
  }

  /* ============================================================
     LOGIN
     ============================================================ */

  function marcarErrorLogin(campo, idAviso, mensaje) {
    if (campo) campo.classList.add("campo-invalido");
    var avisoError = document.getElementById(idAviso);
    if (avisoError) avisoError.textContent = mensaje;
  }

  function limpiarErrorLogin() {
    ["login-correo", "login-clave"].forEach(function (id) {
      var campo = document.getElementById(id);
      if (campo) campo.classList.remove("campo-invalido");
    });
    ["error-login-correo", "error-login-clave"].forEach(function (id) {
      var avisoError = document.getElementById(id);
      if (avisoError) avisoError.textContent = "";
    });
  }

  function procesarLogin(evento) {
    evento.preventDefault();

    var correo = document.getElementById("login-correo");
    var clave = document.getElementById("login-clave");
    var cajaError = document.getElementById("login-error");

    limpiarErrorLogin();
    cajaError.hidden = true;

    var valido = true;

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(correo.value.trim())) {
      marcarErrorLogin(correo, "error-login-correo", "Escribe un correo válido, por ejemplo admin@netsal.pe");
      valido = false;
    }

    if (clave.value.length < 6) {
      marcarErrorLogin(clave, "error-login-clave", "La contraseña debe tener al menos 6 caracteres.");
      valido = false;
    }

    if (!valido) return;

    var encontrado = usuarios.filter(function (usuario) {
      return usuario.correo === correo.value.trim().toLowerCase() && usuario.clave === clave.value;
    })[0];

    if (!encontrado) {
      cajaError.hidden = false;
      cajaError.textContent = "Credenciales incorrectas. Usa uno de los perfiles de demostración.";
      return;
    }

    if (document.getElementById("recordar-correo").checked) {
      localStorage.setItem("netsalLoginCorreo", encontrado.correo);
    } else {
      localStorage.removeItem("netsalLoginCorreo");
    }

    iniciarSesion(encontrado);
  }

  function renderizarPerfilesDemo() {
    var contenedor = document.getElementById("login-demo-grid");
    if (!contenedor) return;

    contenedor.innerHTML = usuarios
      .map(function (usuario) {
        return (
          '<button type="button" class="login-demo-btn" data-correo="' + usuario.correo + '">' +
          "<strong>" + usuario.rol + "</strong>" +
          "<span>" + usuario.correo + "</span></button>"
        );
      })
      .join("");
  }

  /* ============================================================
     EVENTOS
     ============================================================ */

  function iniciarEventos() {
    var formulario = document.getElementById("formulario-login");
    if (formulario) formulario.addEventListener("submit", procesarLogin);

    /* ===== Header del sitio: menú móvil y encabezado con scroll ===== */
    var botonNav = document.getElementById("nav-toggle");
    var menuNav = document.getElementById("nav-menu");
    var cabeceraSitio = document.getElementById("site-header");

    if (botonNav && menuNav) {
      botonNav.addEventListener("click", function () {
        var abierto = menuNav.classList.toggle("is-open");
        botonNav.classList.toggle("is-open", abierto);
        botonNav.setAttribute("aria-expanded", abierto ? "true" : "false");
        botonNav.setAttribute("aria-label", abierto ? "Cerrar menú" : "Abrir menú");
        document.body.classList.toggle("menu-open", abierto);
      });

      menuNav.addEventListener("click", function (evento) {
        if (evento.target.tagName !== "A") return;
        menuNav.classList.remove("is-open");
        botonNav.classList.remove("is-open");
        botonNav.setAttribute("aria-expanded", "false");
        document.body.classList.remove("menu-open");
      });
    }

    if (cabeceraSitio) {
      var actualizarCabecera = function () {
        cabeceraSitio.classList.toggle("is-scrolled", window.scrollY > 40);
      };

      window.addEventListener("scroll", actualizarCabecera, { passive: true });
      actualizarCabecera();
    }

    /* ===== Año del pie de página ===== */
    var anio = document.getElementById("anio-sistema");
    if (anio) anio.textContent = new Date().getFullYear();

    var ojo = document.getElementById("toggle-clave");
    if (ojo) {
      ojo.addEventListener("click", function () {
        var clave = document.getElementById("login-clave");
        var visible = clave.getAttribute("type") === "text";
        clave.setAttribute("type", visible ? "password" : "text");
        ojo.setAttribute("aria-label", visible ? "Mostrar contraseña" : "Ocultar contraseña");
      });
    }

    var demo = document.getElementById("login-demo-grid");
    if (demo) {
      demo.addEventListener("click", function (evento) {
        var boton = evento.target.closest("[data-correo]");
        if (!boton) return;

        document.getElementById("login-correo").value = boton.getAttribute("data-correo");
        document.getElementById("login-clave").value = "123456";
        limpiarErrorLogin();
        document.getElementById("login-error").hidden = true;
        aviso("Credenciales de demostración cargadas", "info");
      });
    }

    var olvido = document.getElementById("olvido-clave");
    if (olvido) {
      olvido.addEventListener("click", function () {
        aviso("En el prototipo la clave de demostración es 123456", "info");
      });
    }

    var salir = document.getElementById("boton-salir");
    if (salir) salir.addEventListener("click", cerrarSesion);

    var reset = document.getElementById("boton-reset");
    if (reset) reset.addEventListener("click", restablecerDatos);

    var botonMenu = document.getElementById("sis-menu-btn");
    var menu = document.getElementById("sis-menu");
    var fondoMenu = document.getElementById("sis-menu-fondo");

    if (botonMenu && menu) {
      botonMenu.addEventListener("click", function () {
        var abierto = menu.classList.toggle("abierto");
        botonMenu.setAttribute("aria-expanded", abierto ? "true" : "false");
        botonMenu.setAttribute(
          "aria-label",
          abierto ? "Cerrar menú de módulos" : "Abrir menú de módulos"
        );
        if (fondoMenu) fondoMenu.classList.toggle("abierto", abierto);
      });
    }

    if (fondoMenu) fondoMenu.addEventListener("click", cerrarMenuModulos);

    var contenido = document.getElementById("vista-sistema");
    if (contenido) {
      contenido.addEventListener("click", function (evento) {
        var irA = evento.target.closest("[data-ir]");
        if (irA) {
          estadoApp.modulo = irA.getAttribute("data-ir");
          renderVista();
          irAlInicioDeVista();
          return;
        }

        var ver = evento.target.closest("[data-detalle]");
        if (ver) {
          abrirDetalle(ver.getAttribute("data-detalle"));
          return;
        }

        var avanzar = evento.target.closest("[data-avanzar]");
        if (avanzar) {
          avanzarEtapa(avanzar.getAttribute("data-avanzar"));
          return;
        }

        var confirmar = evento.target.closest("[data-confirmar]");
        if (confirmar) {
          confirmarEntrega(confirmar.getAttribute("data-confirmar"));
          return;
        }

        var nueva = evento.target.closest("#boton-nueva-orden");
        if (nueva) {
          abrirNuevaOrden();
          actualizarPrecioNuevaOrden();
        }
      });

      contenido.addEventListener("input", function (evento) {
        var id = evento.target.id;

        if (id === "filtro-texto-ordenes") {
          estadoApp.filtroOrdenes.texto = evento.target.value;
          renderVista();
          restaurarFoco("filtro-texto-ordenes");
          return;
        }

        if (id === "filtro-clientes") {
          estadoApp.filtroClientes = evento.target.value;
          renderVista();
          restaurarFoco("filtro-clientes");
          return;
        }

        if (id === "nueva-trabajo" || id === "nueva-piezas") {
          actualizarPrecioNuevaOrden();
        }
      });

      contenido.addEventListener("change", function (evento) {
        if (evento.target.id === "filtro-estado-ordenes") {
          estadoApp.filtroOrdenes.estado = evento.target.value;
          renderVista();
        }
      });
    }

    var listaModulos = document.getElementById("sis-lista-modulos");
    if (listaModulos) {
      listaModulos.addEventListener("click", function (evento) {
        var boton = evento.target.closest("[data-modulo]");
        if (!boton) return;

        estadoApp.modulo = boton.getAttribute("data-modulo");
        cerrarMenuModulos();
        renderVista();
        irAlInicioDeVista();
      });
    }

    var modal = document.getElementById("modal-sistema");
    if (modal) {
      modal.addEventListener("click", function (evento) {
        if (evento.target === modal || evento.target.closest("[data-cerrar-modal]")) {
          cerrarDetalle();
          return;
        }

        var aplicar = evento.target.closest("[data-aplicar-estado]");
        if (aplicar) {
          aplicarEstado(aplicar.getAttribute("data-aplicar-estado"));
          return;
        }

        var anular = evento.target.closest("[data-anular]");
        if (anular) {
          anularOrden(anular.getAttribute("data-anular"));
          return;
        }

        var entrega = evento.target.closest("[data-registrar-entrega]");
        if (entrega) {
          registrarEntrega(entrega.getAttribute("data-registrar-entrega"));
        }
      });

      modal.addEventListener("submit", function (evento) {
        if (evento.target.id === "formulario-nueva-orden") guardarNuevaOrden(evento);
      });
    }

    document.addEventListener("keydown", function (evento) {
      if (evento.key === "Escape") {
        cerrarDetalle();
        cerrarMenuModulos();
      }
    });
  }

  /* ============================================================
     INICIO
     ============================================================ */

  function iniciar() {
    cargarDatos();
    renderizarPerfilesDemo();
    iniciarEventos();

    var correoRecordado = localStorage.getItem("netsalLoginCorreo");
    if (correoRecordado) {
      document.getElementById("login-correo").value = correoRecordado;
      document.getElementById("recordar-correo").checked = true;
    }

    var guardada = sessionStorage.getItem("netsalSesion");
    if (guardada) {
      try {
        var sesion = JSON.parse(guardada);
        var usuario = usuarios.filter(function (item) { return item.correo === sesion.correo; })[0];
        if (usuario) iniciarSesion(usuario);
      } catch (error) {
        sessionStorage.removeItem("netsalSesion");
      }
    }
  }

  document.addEventListener("DOMContentLoaded", iniciar);
})();
