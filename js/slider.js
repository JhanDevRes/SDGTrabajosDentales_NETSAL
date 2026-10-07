/* ============================================================
   js/slider.js
   Slider de testimonios: auto-play, flechas y dots.
   ============================================================ */

window.TestimonialSlider = (function () {
  "use strict";

  var slidesEl;
  var dotsEl;
  var prevBtn;
  var nextBtn;
  var current = 0;
  var total = 0;
  var timer = null;
  var autoplayDelay = 6000;

  /* ===== Renderizar slides ===== */
  function renderSlides(testimonials) {
    var starIcon =
      '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

    var html = "";
    testimonials.forEach(function (item) {
      html +=
        '<div class="testimonial-slide">' +
        '<div class="testimonial-card">' +
        '<div class="stars" aria-label="Calificación: 5 de 5 estrellas">' +
        starIcon + starIcon + starIcon + starIcon + starIcon +
        "</div>" +
        '<p class="quote">"' + item.quote + '"</p>' +
        '<div class="testimonial-author">' +
        '<img src="' + item.photo + '" alt="Foto de ' + item.name + '" width="48" height="48" loading="lazy" decoding="async">' +
        "<div><strong>" + item.name + "</strong><span>" + item.role + "</span></div>" +
        "</div>" +
        "</div>" +
        "</div>";
    });

    slidesEl.innerHTML = html;
    total = testimonials.length;
  }

  /* ===== Renderizar dots ===== */
  function renderDots() {
    var html = "";
    for (var i = 0; i < total; i++) {
      html +=
        '<button aria-label="Ir al testimonio ' + (i + 1) + ' de ' + total + '" data-index="' + i + '"></button>';
    }
    dotsEl.innerHTML = html;

    var dotButtons = dotsEl.querySelectorAll("button");
    dotButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        goTo(parseInt(btn.getAttribute("data-index"), 10));
        restartAutoplay();
      });
    });
  }

  /* ===== Actualizar posicion ===== */
  function update() {
    if (!slidesEl) return;

    if (current >= total) current = 0;
    if (current < 0) current = total - 1;

    slidesEl.style.transform = "translateX(-" + current * 100 + "%)";

    var dotButtons = dotsEl ? dotsEl.querySelectorAll("button") : [];
    dotButtons.forEach(function (btn, index) {
      btn.classList.toggle("is-active", index === current);
    });
  }

  /* ===== Navegacion ===== */
  function goTo(index) {
    current = index;
    update();
  }

  function next() {
    current = (current + 1) % total;
    update();
  }

  function prev() {
    current = current - 1 < 0 ? total - 1 : current - 1;
    update();
  }

  /* ===== Autoplay ===== */
  function startAutoplay() {
    stopAutoplay();
    timer = setInterval(next, autoplayDelay);
  }

  function stopAutoplay() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  }

  function restartAutoplay() {
    startAutoplay();
  }

  /* ===== Inicializar ===== */
  function init(testimonials) {
    slidesEl = document.getElementById("testimonial-slides");
    dotsEl = document.getElementById("slider-dots");
    prevBtn = document.getElementById("slider-prev");
    nextBtn = document.getElementById("slider-next");

    if (!slidesEl || !testimonials || testimonials.length === 0) return;

    renderSlides(testimonials);

    if (dotsEl) {
      renderDots();
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", function () {
        prev();
        restartAutoplay();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        next();
        restartAutoplay();
      });
    }

    /* Pausa al pasar el mouse */
    var slider = document.getElementById("testimonial-slider");
    if (slider) {
      slider.addEventListener("mouseenter", stopAutoplay);
      slider.addEventListener("mouseleave", startAutoplay);
      slider.addEventListener("touchstart", stopAutoplay, { passive: true });
      slider.addEventListener("touchend", startAutoplay, { passive: true });
    }

    current = 0;
    update();

    /* Solo autoplay si hay mas de un testimonio */
    if (total > 1) {
      startAutoplay();
    }
  }

  return {
    init: init
  };
})();