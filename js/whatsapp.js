/* ============================================================
   js/whatsapp.js
   Centraliza los enlaces de WhatsApp con mensajes predefinidos.
   ============================================================ */

(function () {
  "use strict";

  /* ===== Construir URL de WhatsApp ===== */
  function buildWhatsAppUrl(message) {
    var number = (window.site && site.whatsapp) || "51984900939";
    return "https://wa.me/" + number + "?text=" + encodeURIComponent(message);
  }

  /* ===== Mensajes predefinidos ===== */
  var messages = {
    general: function () {
      return "Hola " + (window.site ? site.labName : "NETSAL Laboratorio Dental Digital") + ", deseo enviar un caso digital .STL o solicitar cotización de prótesis.";
    },
    service: function (serviceName) {
      return (
        "Hola " + (window.site ? site.labName : "NETSAL Laboratorio Dental Digital") +
        ", deseo información y cotización sobre: " + serviceName + "."
      );
    }
  };

  /* ===== Aplicar mensajes a los botones ===== */
  function applyWhatsAppLinks() {
    var waLinks = document.querySelectorAll('a[href*="wa.me"]');

    waLinks.forEach(function (link) {
      /* Si el enlace ya tiene texto, no lo sobreescribimos */
      if (link.getAttribute("href").indexOf("?text=") !== -1) return;

      var message = messages.general();
      link.setAttribute("href", buildWhatsAppUrl(message));
      link.setAttribute("target", "_blank");
      link.setAttribute("rel", "noopener");
    });
  }

  /* ===== Inicializar ===== */
  document.addEventListener("DOMContentLoaded", function () {
    applyWhatsAppLinks();
  });
})();