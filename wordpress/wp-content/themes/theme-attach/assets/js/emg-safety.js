document.addEventListener('DOMContentLoaded', () => {
  const safetyBlocks = document.querySelectorAll('.emg-safety');
  if (!safetyBlocks.length || typeof Swiper === 'undefined') return;

  safetyBlocks.forEach((block) => {
    const tabButtons = block.querySelectorAll('.emg-safety__tab');
    const panels = block.querySelectorAll('.emg-safety__tab-panel');

    // --- Helper para actualizar estado de flechas ---
    const updateNavState = (swiper, prevBtn, nextBtn) => {
      if (!swiper || !prevBtn || !nextBtn) return;

      if (swiper.isBeginning) prevBtn.classList.add('is-disabled');
      else prevBtn.classList.remove('is-disabled');

      if (swiper.isEnd) nextBtn.classList.add('is-disabled');
      else nextBtn.classList.remove('is-disabled');
    };

    // --- Helper: solo mobile, centra el tab activo dentro del scroll horizontal ---
    const scrollActiveTabIntoViewMobile = (btn) => {
      const tabsWrap = btn.closest('.emg-safety__tabs');
      if (!tabsWrap) return;

      // Solo mobile
      if (!window.matchMedia('(max-width: 768px)').matches) return;

      const wrapRect = tabsWrap.getBoundingClientRect();
      const btnRect = btn.getBoundingClientRect();

      const currentScroll = tabsWrap.scrollLeft;

      // Centro del botón dentro del contenedor visible
      const btnCenterInWrap = (btnRect.left - wrapRect.left) + (btnRect.width / 2);

      // Queremos que el centro del botón quede en el centro del contenedor
      const targetScroll = currentScroll + btnCenterInWrap - (wrapRect.width / 2);

      // Clamp para no pasarnos
      const maxScroll = tabsWrap.scrollWidth - tabsWrap.clientWidth;
      const nextScroll = Math.max(0, Math.min(targetScroll, maxScroll));

      tabsWrap.scrollTo({ left: nextScroll, behavior: 'smooth' });

      // Focus accesible sin re-scroll
      btn.focus({ preventScroll: true });
    };

    const swipers = [];

    // --- Crear Swiper por panel ---
    panels.forEach((panel) => {
      const swiperEl = panel.querySelector('.emg-safety__swiper');
      if (!swiperEl) {
        swipers.push(null);
        return;
      }

      const prevBtn = panel.querySelector('.emg-safety__nav-btn--prev');
      const nextBtn = panel.querySelector('.emg-safety__nav-btn--next');
      const paginationEl = panel.querySelector('.emg-safety__pagination');

      const swiperInstance = new Swiper(swiperEl, {
        slidesPerView: 1,
        spaceBetween: 24,
        speed: 600,
        loop: false,
        pagination: {
          el: paginationEl,
          clickable: true,
        },
        navigation: {
          prevEl: prevBtn,
          nextEl: nextBtn,
        },
        breakpoints: {
          1024: {
            spaceBetween: 32,
          },
        },
        on: {
          init(swiper) {
            updateNavState(swiper, prevBtn, nextBtn);
          },
          slideChange(swiper) {
            updateNavState(swiper, prevBtn, nextBtn);
          },
        },
      });

      // Forzar estado inicial
      updateNavState(swiperInstance, prevBtn, nextBtn);

      swipers.push(swiperInstance);
    });

    // --- Tabs ---
    tabButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const slug = btn.getAttribute('data-safety-tab');
        if (!slug) return;

        // Marcar tab activa + accesibilidad
        tabButtons.forEach((b) => {
          const isActive = b === btn;
          b.classList.toggle('is-active', isActive);
          b.setAttribute('aria-selected', isActive ? 'true' : 'false');
          b.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        // MOBILE: desplazar para que el tab activo quede bien posicionado
        scrollActiveTabIntoViewMobile(btn);

        // Mostrar panel correspondiente
        panels.forEach((panel, index) => {
          const panelSlug = panel.getAttribute('data-safety-panel');
          const isActive = panelSlug === slug;
          panel.classList.toggle('is-active', isActive);

          // Refrescar swiper y actualizar flechas del panel activo
          if (isActive && swipers[index]) {
            const swiper = swipers[index];
            swiper.update();

            const prevBtn = panel.querySelector('.emg-safety__nav-btn--prev');
            const nextBtn = panel.querySelector('.emg-safety__nav-btn--next');
            updateNavState(swiper, prevBtn, nextBtn);
          }
        });
      });
    });

    // (Opcional) al cargar: asegurar tabindex correcto
    tabButtons.forEach((b, i) => b.setAttribute('tabindex', i === 0 ? '0' : '-1'));
  });
});
