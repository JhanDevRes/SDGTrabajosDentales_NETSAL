/* ============================================================
   js/animations.js
   Animaciones suaves al hacer scroll (IntersectionObserver)
   y filtro de la galeria (Antes y Despues).
   ============================================================ */

(function () {
  "use strict";

  /* ===== Scroll reveal ===== */
  function initReveal() {
    var revealEls = document.querySelectorAll(
      ".service-card, .doctor-card, .gallery-item, .why-card, .testimonial-card, .section-head, .contact-info-item"
    );

    if (!("IntersectionObserver" in window)) {
      revealEls.forEach(function (el) {
        el.style.opacity = "1";
        el.style.transform = "none";
      });
      return;
    }

    revealEls.forEach(function (el) {
      el.style.opacity = "0";
      el.style.transform = "translateY(30px)";
      el.style.transition = "opacity 0.7s cubic-bezier(0.22,1,0.36,1), transform 0.7s cubic-bezier(0.22,1,0.36,1)";
    });

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );

    revealEls.forEach(function (el) {
      observer.observe(el);
    });
  }

  /* ===== Filtro de galeria ===== */
  function initGalleryFilter() {
    var tabs = document.querySelectorAll(".gallery-tabs button");
    if (!tabs.length) return;

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        /* Estado activo del tab */
        tabs.forEach(function (t) {
          t.classList.remove("is-active");
        });
        tab.classList.add("is-active");

        var filter = tab.getAttribute("data-filter");
        var items = document.querySelectorAll(".gallery-item");

        items.forEach(function (item) {
          var category = item.getAttribute("data-category");
          if (filter === "todos" || category === filter) {
            item.classList.remove("is-hidden");
          } else {
            item.classList.add("is-hidden");
          }
        });
      });
    });
  }

  /* ===== Inicializar ===== */
  document.addEventListener("DOMContentLoaded", function () {
    /* Esperar a que main.js haya poblado el DOM con los datos.
       Usamos un pequeño retardo para asegurar que populateSite()
       ya se haya ejecutado y los elementos dinámicos existan. */
    setTimeout(function () {
      initReveal();
      initGalleryFilter();
    }, 10);
  });
})();