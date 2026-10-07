/* ============================================================
   js/navbar.js
   Menu movil, header transparente con scroll, navegacion activa.
   ============================================================ */

(function () {
  "use strict";

  var header = document.getElementById("site-header");
  var navToggle = document.getElementById("nav-toggle");
  var navMenu = document.getElementById("nav-menu");
  var navLinks = navMenu ? navMenu.querySelectorAll("a") : [];
  var lastScrollY = window.scrollY;
  var menuOpen = false;

  /* ===== Header cambia al hacer scroll ===== */
  function onScroll() {
    if (!header) return;
    var y = window.scrollY;

    if (y > 40) {
      header.classList.add("is-scrolled");
    } else {
      header.classList.remove("is-scrolled");
    }

    /* Cerrar menu al hacer scroll (solo si se hace scroll significativo) */
    if (menuOpen && Math.abs(y - lastScrollY) > 120) {
      closeMenu();
    }

    /* Boton volver arriba */
    var topBtn = document.getElementById("back-to-top");
    if (topBtn) {
      if (y > 500) {
        topBtn.classList.add("is-visible");
      } else {
        topBtn.classList.remove("is-visible");
      }
    }

    lastScrollY = y;
  }

  /* ===== Abrir / cerrar menu ===== */
  function openMenu() {
    if (!navMenu || !navToggle) return;
    menuOpen = true;
    navMenu.classList.add("is-open");
    navToggle.classList.add("is-open");
    navToggle.setAttribute("aria-expanded", "true");
    navToggle.setAttribute("aria-label", "Cerrar menú");
    document.body.style.overflow = "hidden";
    document.body.classList.add("menu-open");
  }

  function closeMenu() {
    if (!navMenu || !navToggle) return;
    menuOpen = false;
    navMenu.classList.remove("is-open");
    navToggle.classList.remove("is-open");
    navToggle.setAttribute("aria-expanded", "false");
    navToggle.setAttribute("aria-label", "Abrir menú");
    document.body.style.overflow = "";
    document.body.classList.remove("menu-open");
  }

  function toggleMenu() {
    if (menuOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  /* ===== Navegacion activa (scroll spy) ===== */
  function setActiveLink() {
    var sections = document.querySelectorAll("section[id]");
    var scrollPos = window.scrollY + 120;

    sections.forEach(function (section) {
      var top = section.offsetTop;
      var height = section.offsetHeight;
      var id = section.getAttribute("id");

      if (scrollPos >= top && scrollPos < top + height) {
        navLinks.forEach(function (link) {
          link.classList.toggle("is-active", link.getAttribute("href") === "#" + id);
        });
      }
    });
  }

  /* ===== Volver arriba ===== */
  function initBackToTop() {
    var topBtn = document.getElementById("back-to-top");
    if (!topBtn) return;

    topBtn.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  /* ===== Cerrar con tecla Escape ===== */
  function onKeydown(e) {
    if (e.key === "Escape" && menuOpen) {
      closeMenu();
      if (navToggle) navToggle.focus();
    }
  }

  /* ===== Cerrar al hacer click fuera ===== */
  function onDocClick(e) {
    if (
      menuOpen &&
      navMenu &&
      !navMenu.contains(e.target) &&
      navToggle &&
      !navToggle.contains(e.target)
    ) {
      closeMenu();
    }
  }

  /* ===== Inicializar ===== */
  function init() {
    if (navToggle) {
      navToggle.addEventListener("click", toggleMenu);
    }

    navLinks.forEach(function (link) {
      link.addEventListener("click", closeMenu);
    });

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("scroll", setActiveLink, { passive: true });
    document.addEventListener("keydown", onKeydown);
    document.addEventListener("click", onDocClick);

    initBackToTop();

    /* Estado inicial */
    onScroll();
    setActiveLink();
  }

  document.addEventListener("DOMContentLoaded", init);
})();