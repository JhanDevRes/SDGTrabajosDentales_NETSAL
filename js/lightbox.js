/* ============================================================
   js/lightbox.js
   Modal lightbox para la galería: abre imágenes en grande,
   navegación con flechas, teclado y cierre.
   Soporta videos de YouTube/Vimeo y links externos.
   ============================================================ */

(function () {
  "use strict";

  var lightbox = document.getElementById("lightbox");
  var lightboxImg = document.getElementById("lightbox-img");
  var lightboxCaption = document.getElementById("lightbox-caption");
  var closeBtn = document.getElementById("lightbox-close");
  var prevBtn = document.getElementById("lightbox-prev");
  var nextBtn = document.getElementById("lightbox-next");

  var items = [];
  var currentIndex = 0;

  /* ===== Recopilar imágenes de la galería ===== */
  function collectGalleryItems() {
    items = [];
    var figures = document.querySelectorAll(".gallery-item");
    figures.forEach(function (figure) {
      var img = figure.querySelector("img");
      var tag = figure.querySelector(".tag");
      if (img) {
        var src = img.getAttribute("src");
        var video = figure.getAttribute("data-video") || "";
        var link = figure.getAttribute("data-link") || "";

        var type = "image";
        if (link) {
          type = "link";
        } else if (video) {
          type = "video";
        }

        items.push({
          src: src,
          video: video,
          link: link,
          type: type,
          alt: img.getAttribute("alt") || "",
          caption: tag ? tag.textContent : ""
        });
      }
    });
  }

  /* ===== Extraer ID de YouTube ===== */
  function getYouTubeId(url) {
    var match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/);
    return match ? match[1] : null;
  }

  /* ===== Extraer ID de Vimeo ===== */
  function getVimeoId(url) {
    var match = url.match(/vimeo\.com\/(\d+)/);
    return match ? match[1] : null;
  }

  /* ===== Determinar contenido a mostrar ===== */
  function getMediaHTML(item) {
    if (item.type === "video") {
      var videoUrl = item.video || item.src;
      var ytId = getYouTubeId(videoUrl);
      if (ytId) {
        return '<div class="lightbox-media">' +
          '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' + ytId + '?rel=0&autoplay=0" ' +
          'frameborder="0" allow="autoplay; encrypted-media" allowfullscreen>' +
          '</iframe>' +
          '</div>';
      }
      var vimeoId = getVimeoId(videoUrl);
      if (vimeoId) {
        return '<div class="lightbox-media">' +
          '<iframe src="https://player.vimeo.com/video/' + vimeoId + '?background=0" ' +
          'frameborder="0" allow="autoplay; fullscreen" allowfullscreen>' +
          '</iframe>' +
          '</div>';
      }
      // Si no se pudo detectar el video, mostrar la imagen
      return '<img src="' + item.src + '" alt="' + item.alt + '" style="width:100%;height:100%;object-fit:contain;">';
    } else if (item.type === "link") {
      var linkUrl = item.link || item.src;
      return '<div class="lightbox-media lightbox-media-link">' +
        '<img src="' + item.src + '" alt="' + item.alt + '" style="width:100%;height:100%;object-fit:contain;">' +
        '<div class="lightbox-link-overlay">' +
        '<a href="' + linkUrl + '" target="_blank" rel="noopener" class="lightbox-link-btn">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>' +
        'Abrir enlace externo' +
        '</a>' +
        '</div>' +
        '</div>';
    } else {
      // Imagen normal
      return '<img src="' + item.src + '" alt="' + item.alt + '" style="width:100%;height:100%;object-fit:contain;">';
    }
  }

  /* ===== Abrir lightbox ===== */
  function openLightbox(index) {
    if (!lightbox || items.length === 0) return;
    currentIndex = (index + items.length) % items.length;
    updateLightbox();
    lightbox.hidden = false;
    document.body.style.overflow = "hidden";
  }

  /* ===== Cerrar lightbox ===== */
  function closeLightbox() {
    if (!lightbox) return;
    lightbox.hidden = true;
    document.body.style.overflow = "";
    // Limpiar el contenido de video al cerrar
    var mediaContainer = document.getElementById("lightbox-media");
    if (mediaContainer) {
      mediaContainer.innerHTML = "";
      mediaContainer.style.display = "none";
    }
  }

  /* ===== Actualizar contenido ===== */
  function updateLightbox() {
    var item = items[currentIndex];
    if (!item) return;

    // Ocultar/mostrar la imagen base
    lightboxImg.style.display = item.type !== "image" ? "none" : "block";
    lightboxImg.src = item.src;
    lightboxImg.alt = item.alt;
    lightboxCaption.textContent = item.caption;

    // Crear o reutilizar el contenedor de media
    var mediaContainer = document.getElementById("lightbox-media");
    if (!mediaContainer) {
      mediaContainer = document.createElement("div");
      mediaContainer.id = "lightbox-media";
      var figure = lightbox.querySelector("figure");
      if (figure) figure.appendChild(mediaContainer);
    }

    // Limpiar y llenar con el contenido apropiado
    mediaContainer.innerHTML = "";
    if (item.type !== "image") {
      mediaContainer.innerHTML = getMediaHTML(item);
      mediaContainer.style.display = "flex";
    } else {
      mediaContainer.style.display = "none";
    }

    // Ajustar altura para videos
    if (item.type === "video") {
      lightbox.style.height = "100vh";
    } else {
      lightbox.style.height = "auto";
    }
  }

  /* ===== Navegación ===== */
  function next() {
    currentIndex = (currentIndex + 1) % items.length;
    updateLightbox();
  }

  function prev() {
    currentIndex = (currentIndex - 1 + items.length) % items.length;
    updateLightbox();
  }

  /* ===== Inicializar ===== */
  function init() {
    collectGalleryItems();

    /* Delegación de clics en la galería */
    var galleryGrid = document.getElementById("gallery-grid");
    if (galleryGrid) {
      galleryGrid.addEventListener("click", function (e) {
        var figure = e.target.closest(".gallery-item");
        if (!figure) return;
        // Recolectar items en el momento del clic para asegurar que la galería ya está poblada
        collectGalleryItems();
        var index = Array.prototype.indexOf.call(galleryGrid.children, figure);
        openLightbox(index);
      });
    }

    /* Botones */
    if (closeBtn) closeBtn.addEventListener("click", closeLightbox);
    if (prevBtn) prevBtn.addEventListener("click", prev);
    if (nextBtn) nextBtn.addEventListener("click", next);

    /* Clic fuera de la imagen cierra */
    if (lightbox) {
      lightbox.addEventListener("click", function (e) {
        if (e.target === lightbox) closeLightbox();
      });
    }

    /* Teclado */
    document.addEventListener("keydown", function (e) {
      if (lightbox && !lightbox.hidden) {
        if (e.key === "Escape") closeLightbox();
        if (e.key === "ArrowRight") next();
        if (e.key === "ArrowLeft") prev();
      }
    });
  }

  /* ===== Recolectar de nuevo cuando cambie la galería (filtros) ===== */
  document.addEventListener("DOMContentLoaded", function () {
    init();

    /* Recolectar tras cambios en la galería (filtros) */
    var tabs = document.querySelectorAll(".gallery-tabs button");
    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        setTimeout(collectGalleryItems, 50);
      });
    });
  });
})();