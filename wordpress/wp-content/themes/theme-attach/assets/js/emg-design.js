document.addEventListener('DOMContentLoaded', () => {
    const designBlocks = document.querySelectorAll('.emg-design');

    if (!designBlocks.length) return;

    designBlocks.forEach((block) => {
        const tabButtons = block.querySelectorAll('.emg-design__tab');
        const panels = block.querySelectorAll('.emg-design__tab-panel');

        // ---- Inicializar Swipers por panel ----
        const swipers = [];

        panels.forEach((panel) => {
            const swiperEl = panel.querySelector('.emg-design__swiper');
            if (!swiperEl) {
                swipers.push(null);
                return;
            }

            const prevBtn = panel.querySelector('.emg-design__nav-btn--prev');
            const nextBtn = panel.querySelector('.emg-design__nav-btn--next');

            if (typeof Swiper === 'undefined') {
                swipers.push(null);
                return;
            }

            const swiperInstance = new Swiper(swiperEl, {
                slidesPerView: 1.05,
                spaceBetween: 16,
                centeredSlides: true,
                speed: 600,
                loop: false,
                navigation: {
                    prevEl: prevBtn,
                    nextEl: nextBtn,
                },
                breakpoints: {
                    768: {
                        slidesPerView: 1,
                        centeredSlides: false,
                        spaceBetween: 24,
                    },
                    1200: {
                        slidesPerView: 1,
                        centeredSlides: false,
                        spaceBetween: 32,
                    },
                },
            });

            swipers.push(swiperInstance);
        });

        // ---- Tabs click ----
        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetSlug = btn.getAttribute('data-design-tab');
                if (!targetSlug) return;

                // Actualizar estado de tabs
                tabButtons.forEach((b) => {
                    const isActive = b === btn;
                    b.classList.toggle('is-active', isActive);
                    b.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                // Mostrar/ocultar paneles
                panels.forEach((panel, index) => {
                    const panelSlug = panel.getAttribute('data-design-panel');
                    const isActive = panelSlug === targetSlug;
                    panel.classList.toggle('is-active', isActive);

                    // Refrescar swiper del panel activo
                    if (isActive && swipers[index] && typeof swipers[index].update === 'function') {
                        swipers[index].update();
                    }
                });
            });
        });
    });
});
