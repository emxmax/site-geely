(function () {
  function initHeroCarousels() {
    if (typeof Swiper === "undefined") return;

    document.querySelectorAll(".hero-carousel").forEach((root) => {
      if (root.dataset.hcInit === "1") return;
      root.dataset.hcInit = "1";

      const swiperEl = root.querySelector(".hero-carousel__swiper");
      if (!swiperEl) return;

      const btnPrev = root.querySelector(".hero-carousel__nav--prev");
      const btnNext = root.querySelector(".hero-carousel__nav--next");
      const pagH = root.querySelector(".hero-carousel__pagination--h");
      const pagV = root.querySelector(".hero-carousel__pagination--v");

      const safeUpdateAutoHeight = (swiper, speed = 0) => {
        if (!swiper || typeof swiper.updateAutoHeight !== "function") return;
        requestAnimationFrame(() => swiper.updateAutoHeight(speed));
      };

      const swiper = new Swiper(swiperEl, {
        loop: false,
        slidesPerView: 1,
        speed: 450,
        watchOverflow: true,
        observer: true,
        observeParents: true,

        autoHeight: true,

        navigation: {
          nextEl: btnNext,
          prevEl: btnPrev,
        },

        pagination: {
          el: pagH,
          clickable: true,
        },

        uniqueNavElements: true,

        // ayuda a que el link no rompa el swipe
        preventClicks: true,
        preventClicksPropagation: true,
        touchStartPreventDefault: false,

        on: {
          init() {
            safeUpdateAutoHeight(this, 0);

            // Recalcular cuando carguen imágenes
            const imgs = swiperEl.querySelectorAll("img");
            imgs.forEach((img) => {
              if (img.complete) return;
              img.addEventListener(
                "load",
                () => {
                  this.update();
                  safeUpdateAutoHeight(this, 0);
                },
                { once: true }
              );
            });
          },
          slideChange() {
            safeUpdateAutoHeight(this, 200);
          },
          slideChangeTransitionEnd() {
            safeUpdateAutoHeight(this, 200);
          },
          resize() {
            this.update();
            safeUpdateAutoHeight(this, 0);
          },
        },
      });

      // Control: solo permitir navegación si fue TAP, no drag
      const bindOverlayTapGuard = () => {
        const overlays = root.querySelectorAll(".hero-carousel__overlay-link");
        overlays.forEach((a) => {
          let startX = 0;
          let startY = 0;
          let moved = false;

          const threshold = 8; // px

          a.addEventListener(
            "touchstart",
            (e) => {
              moved = false;
              const t = e.touches && e.touches[0];
              if (!t) return;
              startX = t.clientX;
              startY = t.clientY;
            },
            { passive: true }
          );

          a.addEventListener(
            "touchmove",
            (e) => {
              const t = e.touches && e.touches[0];
              if (!t) return;
              const dx = Math.abs(t.clientX - startX);
              const dy = Math.abs(t.clientY - startY);
              if (dx > threshold || dy > threshold) moved = true;
            },
            { passive: true }
          );

          // Si fue drag, bloqueamos el click del overlay
          a.addEventListener("click", (e) => {
            if (moved || (swiper && swiper.allowClick === false)) {
              e.preventDefault();
              e.stopPropagation();
            }
          });
        });
      };

      // -------- Vertical bullets (custom) ----------
      function renderVBullets() {
        if (!pagV) return;
        pagV.innerHTML = "";

        const total = swiper.slides.length;
        for (let i = 0; i < total; i++) {
          const b = document.createElement("button");
          b.type = "button";
          b.className = "hero-carousel__vbullet";
          b.setAttribute("aria-label", `Ir al slide ${i + 1}`);
          b.addEventListener("click", () => {
            swiper.slideTo(i);
            safeUpdateAutoHeight(swiper, 200);
          });
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
        updateVBullets();
        safeUpdateAutoHeight(swiper, 0);
        bindOverlayTapGuard();
      });

      swiper.on("slideChange", () => {
        updateVBullets();
        safeUpdateAutoHeight(swiper, 200);
      });

      // init fallback
      renderVBullets();
      updateVBullets();
      safeUpdateAutoHeight(swiper, 0);
      bindOverlayTapGuard();
    });
  }

  document.addEventListener("DOMContentLoaded", initHeroCarousels);
  window.addEventListener("load", initHeroCarousels);
})();
