/* ============================================================
   js/faq.js
   Acordeon de preguntas frecuentes (FAQ).
   ============================================================ */

window.FaqAccordion = (function () {
  "use strict";

  var listEl;

  /* ===== Renderizar preguntas ===== */
  function renderFaq(faqItems) {
    var html = "";
    faqItems.forEach(function (item, index) {
      html +=
        '<div class="faq-item' + (index === 0 ? " is-open" : "") + '">' +
        '<button class="faq-question" aria-expanded="' + (index === 0 ? "true" : "false") + '" aria-controls="faq-answer-' + index + '" id="faq-question-' + index + '">' +
        "<span>" + item.q + "</span>" +
        '<span class="plus-icon">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
        "</span>" +
        "</button>" +
        '<div class="faq-answer" id="faq-answer-' + index + '" role="region" aria-labelledby="faq-question-' + index + '">' +
        '<div class="faq-answer-inner"><p>' + item.a + "</p></div>" +
        "</div>" +
        "</div>";
    });

    listEl.innerHTML = html;

    /* Bind clicks */
    var questions = listEl.querySelectorAll(".faq-question");
    questions.forEach(function (btn, index) {
      btn.addEventListener("click", function () {
        toggleItem(index);
      });
    });
  }

  /* ===== Alternar item ===== */
  function toggleItem(index) {
    var items = listEl.querySelectorAll(".faq-item");
    var clicked = items[index];
    var isCurrentlyOpen = clicked.classList.contains("is-open");

    /* Cerrar todos */
    items.forEach(function (item, i) {
      item.classList.remove("is-open");
      var btn = item.querySelector(".faq-question");
      if (btn) {
        btn.setAttribute("aria-expanded", "false");
      }
    });

    /* Abrir solo el clickeado si no estaba abierto */
    if (!isCurrentlyOpen) {
      clicked.classList.add("is-open");
      var clickedBtn = clicked.querySelector(".faq-question");
      if (clickedBtn) {
        clickedBtn.setAttribute("aria-expanded", "true");
      }
    }
  }

  /* ===== Inicializar ===== */
  function init(faqItems) {
    listEl = document.getElementById("faq-list");
    if (!listEl || !faqItems || faqItems.length === 0) return;

    renderFaq(faqItems);
  }

  return {
    init: init
  };
})();