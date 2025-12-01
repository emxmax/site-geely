document.addEventListener('DOMContentLoaded', () => {
    const safetyBlocks = document.querySelectorAll('.emg-safety');
    if (!safetyBlocks.length || typeof Swiper === 'undefined') return;

    safetyBlocks.forEach((block) => {
        const tabButtons = block.querySelectorAll('.emg-safety__tab');
        const panels = block.querySelectorAll('.emg-safety__tab-panel');

        // --- Helper para actualizar estado de flechas ---
        const updateNavState = (swiper, prevBtn, nextBtn) => {
            if (!swiper || !prevBtn || !nextBtn) return;

            // Izquierda deshabilitada en el primer slide
            if (swiper.isBeginning) {
                prevBtn.classList.add('is-disabled');
            } else {
                prevBtn.classList.remove('is-disabled');
            }

            // Derecha deshabilitada en el último slide
            if (swiper.isEnd) {
                nextBtn.classList.add('is-disabled');
            } else {
                nextBtn.classList.remove('is-disabled');
            }
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

            // Por si acaso, forzamos el estado inicial
            updateNavState(swiperInstance, prevBtn, nextBtn);

            swipers.push(swiperInstance);
        });

        // --- Tabs ---
        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const slug = btn.getAttribute('data-safety-tab');
                if (!slug) return;

                // Marcar tab activa
                tabButtons.forEach((b) => {
                    const isActive = b === btn;
                    b.classList.toggle('is-active', isActive);
                    b.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

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
    });
});
