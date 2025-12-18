(function () {
  function initHeroCarousels() {
    if (typeof Swiper === "undefined") {
      // Swiper no cargado aún
      return;
    }

    document.querySelectorAll(".hero-carousel").forEach((root) => {
      // Evitar doble init
      if (root.dataset.hcInit === "1") return;
      root.dataset.hcInit = "1";

      const swiperEl = root.querySelector(".hero-carousel__swiper");
      if (!swiperEl) return;

      const btnPrev = root.querySelector(".hero-carousel__nav--prev");
      const btnNext = root.querySelector(".hero-carousel__nav--next");
      const pagH = root.querySelector(".hero-carousel__pagination--h");
      const pagV = root.querySelector(".hero-carousel__pagination--v");

      // Swiper (solo UNA paginación nativa: la horizontal)
      const swiper = new Swiper(swiperEl, {
        loop: false,
        slidesPerView: 1,
        speed: 450,
        watchOverflow: true,
        observer: true,
        observeParents: true,

        navigation: {
          nextEl: btnNext,
          prevEl: btnPrev,
        },

        pagination: {
          el: pagH,
          clickable: true,
        },

        // evita que Swiper tome estilos globales de otros bloques
        uniqueNavElements: true,
      });

      // -------- Vertical bullets (custom) ----------
      function renderVBullets() {
        if (!pagV) return;
        pagV.innerHTML = "";

        const total = swiper.slides.length; // ya sin loop
        for (let i = 0; i < total; i++) {
          const b = document.createElement("button");
          b.type = "button";
          b.className = "hero-carousel__vbullet";
          b.setAttribute("aria-label", `Ir al slide ${i + 1}`);

          b.addEventListener("click", () => swiper.slideTo(i));
          pagV.appendChild(b);
        }
        updateVBullets();
      }

      function updateVBullets() {
        if (!pagV) return;
        const bullets = pagV.querySelectorAll(".hero-carousel__vbullet");
        bullets.forEach((b) => b.classList.remove("is-active"));
        const idx = swiper.activeIndex;
        if (bullets[idx]) bullets[idx].classList.add("is-active");
      }

      swiper.on("init", () => {
        renderVBullets();
      });

      swiper.on("slideChange", () => {
        updateVBullets();
      });

      // Swiper 8/9 a veces no dispara init automáticamente en este wrapper
      renderVBullets();
      updateVBullets();
    });
  }

  document.addEventListener("DOMContentLoaded", initHeroCarousels);
  window.addEventListener("load", initHeroCarousels);
})();
