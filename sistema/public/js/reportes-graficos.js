/* ============================================================================
   public/js/reportes-graficos.js

   Dibuja los gráficos del módulo de reportes a partir de las series que
   entrega reportes/datos (JSON). Todas las series se calculan con GROUP BY en
   MySQL; aquí solo se convierten en gráficos.

   Requiere Chart.js (se carga por CDN en la vista).
   ============================================================================ */

(function () {
  "use strict";

  var raiz = document.getElementById("reportes-graficos");
  if (!raiz || !window.Chart) return;

  var url = raiz.dataset.url;
  var instancias = [];

  /* Paleta plana alineada con la hoja de estilos del panel. */
  var COLORES = {
    recibido: "#8a93a3",
    diseno: "#1b65a6",
    fresado: "#33477f",
    horno_acabado: "#c98a1a",
    control: "#5b3f86",
    listo: "#1f7a4d",
    entregado: "#175e57",
    anulado: "#a12626"
  };

  Chart.defaults.font.family = '"Segoe UI", system-ui, -apple-system, Arial, sans-serif';
  Chart.defaults.font.size = 11;
  Chart.defaults.color = "#46536b";
  Chart.defaults.borderColor = "#e8edf3";

  function destruir() {
    instancias.forEach(function (grafico) {
      grafico.destroy();
    });
    instancias = [];
  }

  function crear(id, configuracion) {
    var lienzo = document.getElementById(id);
    if (!lienzo) return;

    var contexto = lienzo.getContext("2d");
    contexto.clearRect(0, 0, lienzo.width, lienzo.height);
    instancias.push(new Chart(contexto, configuracion));
  }

  function vacio(id, mensaje) {
    var contenedor = document.getElementById(id);
    if (!contenedor) return;
    var padre = contenedor.parentElement;
    padre.innerHTML = '<div class="grafico-vacio">' + mensaje + "</div>";
  }

  /* --- 1. Órdenes por estado --- */

  function graficoEstados(series) {
    var conDatos = series.filter(function (fila) {
      return fila.total > 0;
    });

    if (!conDatos.length) {
      vacio("grafico-estados", "Sin órdenes registradas.");
      return;
    }

    crear("grafico-estados", {
      type: "doughnut",
      data: {
        labels: conDatos.map(function (fila) {
          return fila.etiqueta;
        }),
        datasets: [
          {
            data: conDatos.map(function (fila) {
              return fila.total;
            }),
            backgroundColor: conDatos.map(function (fila) {
              return COLORES[fila.estado] || "#667388";
            }),
            borderColor: "#ffffff",
            borderWidth: 2
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "58%",
        plugins: {
          legend: { position: "bottom", labels: { boxWidth: 10, padding: 10 } },
          tooltip: {
            callbacks: {
              label: function (item) {
                return " " + item.label + ": " + item.parsed + " orden(es)";
              }
            }
          }
        }
      }
    });
  }

  /* --- 2. Ingresadas y entregadas por mes --- */

  function graficoMeses(series) {
    crear("grafico-meses", {
      type: "bar",
      data: {
        labels: series.map(function (fila) {
          return fila.etiqueta;
        }),
        datasets: [
          {
            label: "Ingresadas",
            data: series.map(function (fila) {
              return fila.ingresadas;
            }),
            backgroundColor: "#1b65a6",
            borderRadius: 3,
            maxBarThickness: 26
          },
          {
            label: "Entregadas",
            data: series.map(function (fila) {
              return fila.entregadas;
            }),
            backgroundColor: "#1f7a4d",
            borderRadius: 3,
            maxBarThickness: 26
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: "#eef1f5" } },
          x: { grid: { display: false } }
        },
        plugins: {
          legend: { position: "bottom", labels: { boxWidth: 10, padding: 12 } }
        }
      }
    });
  }

  /* --- 3. Tipos de trabajo más solicitados --- */

  function graficoTipos(series) {
    if (!series.length) {
      vacio("grafico-tipos", "Sin datos suficientes.");
      return;
    }

    crear("grafico-tipos", {
      type: "bar",
      data: {
        labels: series.map(function (fila) {
          return fila.tipo.length > 26 ? fila.tipo.slice(0, 24) + "…" : fila.tipo;
        }),
        datasets: [
          {
            label: "Órdenes",
            data: series.map(function (fila) {
              return fila.total;
            }),
            backgroundColor: "#17548a",
            borderRadius: 3,
            maxBarThickness: 18
          }
        ]
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: "#eef1f5" } },
          y: { grid: { display: false } }
        },
        plugins: { legend: { display: false } }
      }
    });
  }

  /* --- 4. Ingreso, costo y margen por mes (solo administración) --- */

  function graficoEconomia(series) {
    var lienzo = document.getElementById("grafico-economia");
    if (!lienzo) return;

    crear("grafico-economia", {
      type: "line",
      data: {
        labels: series.map(function (fila) {
          return fila.etiqueta;
        }),
        datasets: [
          {
            label: "Ingreso facturado",
            data: series.map(function (fila) {
              return fila.ingreso;
            }),
            borderColor: "#1b65a6",
            backgroundColor: "rgba(27, 101, 166, 0.10)",
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointRadius: 3
          },
          {
            label: "Costo de materiales",
            data: series.map(function (fila) {
              return fila.costo;
            }),
            borderColor: "#a12626",
            backgroundColor: "rgba(161, 38, 38, 0.08)",
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointRadius: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: "index", intersect: false },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: "#eef1f5" },
            ticks: {
              callback: function (valor) {
                return "S/ " + Number(valor).toLocaleString("es-PE");
              }
            }
          },
          x: { grid: { display: false } }
        },
        plugins: {
          legend: { position: "bottom", labels: { boxWidth: 10, padding: 12 } },
          tooltip: {
            callbacks: {
              label: function (item) {
                return " " + item.dataset.label + ": S/ " + Number(item.parsed.y).toLocaleString("es-PE", { minimumFractionDigits: 2 });
              }
            }
          }
        }
      }
    });
  }

  /* --- Carga de datos --- */

  function cargar() {
    var boton = document.getElementById("graficos-refrescar");
    if (boton) boton.disabled = true;

    window
      .fetch(url, { headers: { Accept: "application/json" }, cache: "no-store" })
      .then(function (respuesta) {
        if (!respuesta.ok) throw new Error("respuesta " + respuesta.status);
        return respuesta.json();
      })
      .then(function (cuerpo) {
        var datos = cuerpo.datos || {};
        destruir();
        graficoEstados(datos.por_estado || []);
        graficoMeses(datos.por_mes || []);
        graficoTipos(datos.por_tipo || []);
        if (datos.economia) graficoEconomia(datos.economia);

        var sello = document.getElementById("graficos-sello");
        if (sello && datos.generado_en) {
          sello.textContent =
            "Actualizado " +
            new Date(datos.generado_en).toLocaleTimeString("es-PE", {
              hour: "2-digit",
              minute: "2-digit"
            });
        }
      })
      .catch(function () {
        var sello = document.getElementById("graficos-sello");
        if (sello) sello.textContent = "No se pudieron cargar los gráficos.";
      })
      .finally(function () {
        if (boton) boton.disabled = false;
      });
  }

  var boton = document.getElementById("graficos-refrescar");
  if (boton) boton.addEventListener("click", cargar);

  cargar();
})();
