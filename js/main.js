/* ============================================================
   js/main.js
   Puebla todo el contenido del sitio desde data/site.js.
   NETSAL - Laboratorio Dental Digital & Centro CAD/CAM.
   ============================================================ */

(function () {
  "use strict";

  /* ===== Utilidades ===== */
  function setText(id, text) {
    var el = document.getElementById(id);
    if (el) el.textContent = text;
  }

  function setHTML(id, html) {
    var el = document.getElementById(id);
    if (el) el.innerHTML = html;
  }

  /* ===== Iconos SVG de servicios CAD/CAM ===== */
  var serviceIcons = {
    cadcam:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8"/><path d="M12 16v4"/><path d="M7 9l2 2-2 2"/><path d="M13 13h4"/></svg>',
    tooth:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5.5c-1.5-1.8-3.5-2.5-5.5-2.5C3.5 3 2 5.5 2 8.5c0 2 .5 3.5 1.5 5 .8 1.2 1.5 2.5 1.8 4 .3 1.5.8 3.5 2 3.5 1.8 0 1.5-4 2.5-4s.7 4 2.2 4c1.2 0 1.7-2 2-3.5.3-1.5 1-2.8 1.8-4 1-1.5 1.5-3 1.5-5 0-3-1.5-5.5-4.5-5.5-2 0-4 .7-5.5 2.5z"/></svg>',
    braces:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8c2 0 3 1 3 3s-1 3-3 3"/><path d="M20 8c-2 0-3 1-3 3s1 3 3 3"/><path d="M7 11h10"/><path d="M7 14h10"/><path d="M9 8c0 2 1 3 3 3s3-1 3-3"/><path d="M9 14c0 2 1 3 3 3s3-1 3-3"/></svg>',
    implant:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v6"/><path d="M12 8c-2 0-3 1-3 2.5V20a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V10.5C15 9 14 8 12 8z"/><path d="M9 13h6"/></svg>',
    "sparkle-tooth":
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5.5c-1.5-1.8-3.5-2.5-5.5-2.5C3.5 3 2 5.5 2 8.5c0 2 .5 3.5 1.5 5 .8 1.2 1.5 2.5 1.8 4 .3 1.5.8 3.5 2 3.5 1.8 0 1.5-4 2.5-4s.7 4 2.2 4c1.2 0 1.7-2 2-3.5.3-1.5 1-2.8 1.8-4 1-1.5 1.5-3 1.5-5 0-3-1.5-5.5-4.5-5.5-2 0-4 .7-5.5 2.5z"/><path d="M18 2l.8 2.2L21 5l-2.2.8L18 8l-.8-2.2L15 5l2.2-.8z"/></svg>',
    guide:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/></svg>'
  };

  /* ===== Icono check ===== */
  var checkIcon =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>';

  /* ===== Iconos de equipos / tecnología ===== */
  var equipmentIcons = {
    scanner:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7V5a1 1 0 0 1 1-1h2"/><path d="M4 17v2a1 1 0 0 0 1 1h2"/><path d="M20 7V5a1 1 0 0 0-1-1h-2"/><path d="M20 17v2a1 1 0 0 1-1 1h-2"/><circle cx="12" cy="12" r="4"/><path d="M12 10v4"/><path d="M10 12h4"/></svg>',
    tomography:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><path d="M12 3v3"/><path d="M12 18v3"/><path d="M3 12h3"/><path d="M18 12h3"/></svg>',
    cadcam:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8"/><path d="M12 16v4"/><path d="M7 9l2 2-2 2"/><path d="M13 13h4"/></svg>',
    laser:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l6-6"/><path d="M14 4l6 6-8.5 8.5a2 2 0 0 1-2.8 0l-3.2-3.2a2 2 0 0 1 0-2.8z"/><path d="M13 5l6 6"/></svg>',
    design:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.7 3.6L18 8l-3.6 1.7L12 14l-1.7-4.3L6 8l4.3-1.4z"/><path d="M5 18l1 2 2 1-2 1-1 2-1-2-2-1 2-1z"/></svg>',
    guide:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/></svg>'
  };

  /* ===== Icono medalla (marcas) ===== */
  var medalIcon =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="9" r="5.5"/><path d="M9 13.5L7 22l5-3 5 3-2-8.5"/></svg>';

  /* ===== Iconos de valores ===== */
  var valueIcons = [
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l2.9 6.3 6.8.7-5.1 4.7 1.5 6.7L12 17.1 5.9 20.4l1.5-6.7-5.1-4.7 6.8-.7z"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v2"/><path d="M12 20v2"/><circle cx="12" cy="12" r="6"/><path d="M9.5 12a2.5 2.5 0 0 1 5 0c0 1.5-2.5 2-2.5 3.5"/><path d="M12 18h.01"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-3 2.7-5 6-5s6 2 6 5"/><path d="M14 15.2c2.5.3 4 2 4 4.8"/></svg>'
  ];

  /* ===== Iconos del Programa de Adultos Mayores ===== */
  var seniorIcons = {
    card:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="15" x2="10" y2="15"/></svg>',
    wallet:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2"/><path d="M21 7H3"/><path d="M21 7v5h-5a2 2 0 0 1 0-4h5z"/></svg>',
    plan:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>',
    help:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    gift:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>'
  };

  /* ===== Iconos de contacto ===== */
  var contactIcons = {
    address:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    phone:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    email:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
    schedule:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
  };

  /* ===== Poblar datos ===== */
  function populateSite() {
    /* Topbar */
    setText("topbar-phone", site.phoneDisplay);
    setText("topbar-schedule", site.schedule[0].day + ": " + site.schedule[0].hours);

    /* Mapa Embed */
    var mapEmbed = document.getElementById("map-embed");
    if (mapEmbed && site.mapEmbed) {
      mapEmbed.setAttribute("src", site.mapEmbed);
    }

    /* Hero */
    setText("hero-eyebrow", site.hero.eyebrow);
    setText("hero-title-start", site.hero.title.replace(site.hero.accent, ""));
    setText("hero-title-accent", site.hero.accent);
    setText("hero-subtitle", site.hero.subtitle);
    setText("hero-phone", site.phoneDisplay);

    /* Stats del hero */
    var statsHTML = "";
    site.hero.stats.forEach(function (stat) {
      statsHTML +=
        '<div class="stat"><b>' + stat.value + "</b><span>" + stat.label + "</span></div>";
    });
    setHTML("hero-stats", statsHTML);

    /* Servicios */
    var servicesHTML = "";
    site.services.forEach(function (service, index) {
      var icon = serviceIcons[service.icon] || serviceIcons.cadcam;
      servicesHTML +=
        '<article class="service-card" data-service-index="' + index + '">' +
        '<div class="service-icon">' + icon + "</div>" +
        "<h3>" + service.title + "</h3>" +
        "<p>" + service.desc + "</p>" +
        '<button class="service-card-btn" data-service-index="' + index + '" aria-label="Ver detalle de ' + service.title + '">Ver detalle técnico</button>' +
        "</article>";
    });
    setHTML("services-grid", servicesHTML);

    /* Modal de servicios */
    var servicesGrid = document.getElementById("services-grid");
    if (servicesGrid) {
      servicesGrid.addEventListener("click", function (e) {
        var btn = e.target.closest(".service-card-btn");
        if (!btn) return;
        var index = parseInt(btn.getAttribute("data-service-index"), 10);
        var service = site.services[index];
        if (service) openServiceModal(service);
      });
    }

    /* Flujo Digital de Trabajo */
    if (site.workflow) {
      var workflowHTML = "";
      site.workflow.forEach(function (step) {
        workflowHTML +=
          '<div class="workflow-card">' +
          '<div class="workflow-step-num">' + step.step + "</div>" +
          "<h3>" + step.title + "</h3>" +
          "<p>" + step.desc + "</p>" +
          "</div>";
      });
      setHTML("workflow-grid", workflowHTML);
    }

    /* Why us */
    var whyHTML = "";
    site.whyUs.forEach(function (item) {
      whyHTML +=
        '<div class="why-card">' +
        '<span class="icon-check">' + checkIcon + "</span>" +
        "<div><h4>" + item.title + "</h4><p>" + item.desc + "</p></div>" +
        "</div>";
    });
    setHTML("why-grid", whyHTML);

    /* Acerca de nosotros */
    if (site.about) {
      setText("about-history", site.about.history);
      setText("about-mission", site.about.mission);
      setText("about-vision", site.about.vision);

      var objectivesHTML = "";
      site.about.objectives.forEach(function (obj) {
        objectivesHTML += "<li>" + obj + "</li>";
      });
      setHTML("about-objectives", objectivesHTML);

      /* Valores */
      if (site.about.values && site.about.values.length) {
        var valuesHTML = "";
        site.about.values.forEach(function (val, index) {
          var icon = valueIcons[index % valueIcons.length];
          valuesHTML +=
            '<div class="value-card">' +
            '<span class="icon-check">' + icon + "</span>" +
            "<div><h4>" + val.title + "</h4><p>" + val.desc + "</p></div>" +
            "</div>";
        });
        setHTML("about-values-grid", valuesHTML);
      }
    }

    /* Equipos y tecnologia */
    if (site.equipment) {
      var equipmentHTML = "";
      site.equipment.forEach(function (item) {
        var icon = equipmentIcons[item.icon] || equipmentIcons.scanner;
        equipmentHTML +=
          '<div class="equipment-card">' +
          '<div class="equipment-icon">' + icon + "</div>" +
          "<h4>" + item.title + "</h4>" +
          "<p>" + item.desc + "</p>" +
          "</div>";
      });
      setHTML("equipment-grid", equipmentHTML);
    }

    /* Marcas reconocidas */
    if (site.brands) {
      var brandsHTML = "";
      site.brands.forEach(function (brand) {
        brandsHTML +=
          '<div class="brand-chip">' +
          '<span class="brand-chip-icon">' + medalIcon + "</span>" +
          '<div><strong>' + brand.name + "</strong><span>" + brand.category + "</span></div>" +
          "</div>";
      });
      setHTML("brands-grid", brandsHTML);
    }

    /* Galería / Portafolio */
    var galleryHTML = "";
    site.gallery.forEach(function (item) {
      galleryHTML +=
        '<figure class="gallery-item" data-category="' + item.category + '">' +
        '<img src="' + item.img + '" alt="' + item.tag + ' - Prótesis NETSAL Laboratorio Dental Digital" loading="lazy" decoding="async">' +
        '<span class="tag">' + item.tag + "</span>" +
        "</figure>";
    });
    setHTML("gallery-grid", galleryHTML);

    /* Programa de Apoyo para Adultos Mayores */
    if (site.seniorProgram) {
      setText("senior-eyebrow", site.seniorProgram.eyebrow);
      setText("senior-title", site.seniorProgram.title);
      setText("senior-subtitle", site.seniorProgram.subtitle);
      setText("senior-description", site.seniorProgram.description);
      setText("senior-cta-text", site.seniorProgram.cta.text);

      /* Botón WhatsApp del programa */
      var seniorCtaBtn = document.getElementById("senior-cta-btn");
      if (seniorCtaBtn) {
        seniorCtaBtn.setAttribute(
          "href",
          "https://wa.me/" + site.whatsapp + "?text=" + encodeURIComponent(site.seniorProgram.cta.whatsapp)
        );
        seniorCtaBtn.setAttribute("target", "_blank");
        seniorCtaBtn.setAttribute("rel", "noopener");
      }

      /* Beneficios */
      var seniorHTML = "";
      site.seniorProgram.benefits.forEach(function (benefit) {
        var icon = seniorIcons[benefit.icon] || seniorIcons.card;
        seniorHTML +=
          '<div class="senior-benefit-card">' +
          '<div class="senior-benefit-icon">' + icon + "</div>" +
          "<h4>" + benefit.title + "</h4>" +
          "<p>" + benefit.desc + "</p>" +
          "</div>";
      });
      setHTML("senior-benefits-grid", seniorHTML);

      /* Compromiso */
      setText("senior-commitment", site.seniorProgram.commitment);
    }

    /* Testimonios */
    if (window.TestimonialSlider) {
      window.TestimonialSlider.init(site.testimonials);
    }

    /* FAQ */
    if (window.FaqAccordion) {
      window.FaqAccordion.init(site.faq);
    }

    /* Contacto */
    var contactHTML = "";
    contactHTML +=
      '<div class="contact-info-item">' +
      '<span class="icon-circle">' + contactIcons.address + "</span>" +
      "<div><h4>Laboratorio Dental Digital</h4><p>" + site.labName + "</p><p>" + site.labAddress + "</p></div>" +
      "</div>";
    contactHTML +=
      '<div class="contact-info-item">' +
      '<span class="icon-circle">' + contactIcons.phone + "</span>" +
      "<div><h4>Atención Directa al Odontólogo</h4><p>" + site.labPhoneFull + "</p></div>" +
      "</div>";
    contactHTML +=
      '<div class="contact-info-item">' +
      '<span class="icon-circle">' + contactIcons.email + "</span>" +
      "<div><h4>Correo Electrónico</h4><p>" + site.email + "</p></div>" +
      "</div>";
    contactHTML +=
      '<div class="contact-info-item">' +
      '<span class="icon-circle">' + contactIcons.schedule + "</span>" +
      "<div><h4>Horario de Atención</h4><p>" + site.schedule[0].day + ": " + site.schedule[0].hours + "<br>" + site.schedule[1].day + ": " + site.schedule[1].hours + "</p></div>" +
      "</div>";
    setHTML("contact-info-list", contactHTML);

    /* Enlaces sociales */
    var socialLinks = {
      "topbar-facebook": site.social.facebook,
      "topbar-instagram": site.social.instagram,
      "topbar-tiktok": site.social.tiktok,
      "footer-facebook": site.social.facebook,
      "footer-instagram": site.social.instagram,
      "footer-tiktok": site.social.tiktok
    };
    Object.keys(socialLinks).forEach(function (id) {
      var el = document.getElementById(id);
      if (el) {
        el.setAttribute("href", socialLinks[id]);
        el.setAttribute("target", "_blank");
        el.setAttribute("rel", "noopener");
      }
    });

    /* Footer */
    setText("footer-desc", site.labName + " – " + site.tagline + ". " + site.hero.subtitle);
    setText("footer-lab-address", site.labName + ": " + site.labAddress);
    setText("footer-phone", site.phoneDisplay);
    setText("footer-email", site.email);
    setText("footer-schedule", site.schedule[0].day + ": " + site.schedule[0].hours);
    setText("footer-year", new Date().getFullYear());
    setText("footer-clinic", site.labName);

    /* Modal detalle de servicios */
    var serviceModal = document.getElementById("service-modal");
    var serviceModalClose = document.getElementById("service-modal-close");
    var serviceModalTitle = document.querySelector(".service-modal-title");
    var serviceModalSubtitle = document.querySelector(".service-modal-subtitle");
    var serviceModalDesc = document.querySelector(".service-modal-desc");
    var serviceModalDetail = document.querySelector(".service-modal-detail");

    function openServiceModal(service) {
      if (!serviceModal) return;
      serviceModalTitle.textContent = service.title;
      serviceModalSubtitle.textContent = site.labName;
      serviceModalDesc.textContent = service.desc;
      serviceModalDetail.innerHTML = "<h3>Especificaciones Técnicas</h3><p>" + service.detail + "</p>";
      serviceModal.hidden = false;
      document.body.style.overflow = "hidden";
      serviceModal.classList.add("open");
    }

    function closeServiceModal() {
      if (!serviceModal) return;
      serviceModal.hidden = true;
      document.body.style.overflow = "";
      serviceModal.classList.remove("open");
    }

    if (serviceModalClose) serviceModalClose.addEventListener("click", closeServiceModal);

    if (serviceModal) {
      serviceModal.addEventListener("click", function(e) {
        if (e.target === serviceModal) closeServiceModal();
      });
    }

    document.addEventListener("keydown", function(e) {
      if (serviceModal && !serviceModal.hidden) {
        if (e.key === "Escape") closeServiceModal();
      }
    });
  }

  /* ===== Formulario de contacto / recepción de casos ===== */
  function initContactForm() {
    var form = document.getElementById("contact-form");
    if (!form) return;

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      var name = document.getElementById("contact-name");
      var phone = document.getElementById("contact-phone");
      var message = document.getElementById("contact-message");
      var success = document.getElementById("form-success");

      var valid = true;
      [name, phone, message].forEach(function (field) {
        if (!field.value.trim()) {
          field.style.borderColor = "#F21D2F";
          field.style.boxShadow = "0 0 0 3px rgba(242, 29, 47, 0.12)";
          valid = false;
        } else {
          field.style.borderColor = "";
          field.style.boxShadow = "";
        }
      });

      if (!valid) return;

      var service = document.getElementById("contact-service");
      var email = document.getElementById("contact-email");
      var text =
        "Hola " + site.labName + ", soy " + name.value.trim() +
        ". Deseo coordinar un caso." +
        (service.value ? " Tipo de trabajo: " + service.value + "." : "") +
        (email.value ? " Correo: " + email.value + "." : "") +
        " Teléfono: " + phone.value.trim() + "." +
        (message.value.trim() ? " Indicaciones del caso: " + message.value.trim() : "");

      var waUrl = "https://wa.me/" + site.whatsapp + "?text=" + encodeURIComponent(text);

      success.classList.add("is-visible");
      form.reset();

      /* Abrir WhatsApp en nueva pestaña de forma segura */
      var win = window.open(waUrl, "_blank");
      if (!win) {
        /* Fallback: crear enlace temporal si el popup es bloqueado */
        var a = document.createElement("a");
        a.href = waUrl;
        a.target = "_blank";
        a.rel = "noopener";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      }

      setTimeout(function () {
        success.classList.remove("is-visible");
      }, 6000);
    });
  }

  /* ===== Inicializar ===== */
  document.addEventListener("DOMContentLoaded", function () {
    populateSite();
    initContactForm();
  });
})();