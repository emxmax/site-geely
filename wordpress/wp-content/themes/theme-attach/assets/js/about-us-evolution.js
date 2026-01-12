(function () {
    'use strict';

    /**
     * About Us Evolution Timeline con Swiper
     * Timeline interactivo que sincroniza con Swiper
     */
    document.addEventListener('DOMContentLoaded', () => {
        const swiperElement = document.querySelector('.about-evolution__swiper');
        const timelineButtons = document.querySelectorAll('.about-evolution__year');

        if (!swiperElement || !timelineButtons.length) return;

        // Inicializar Swiper
        const swiper = new Swiper(swiperElement, {
            slidesPerView: 1,
            spaceBetween: 0,
            speed: 600,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            allowTouchMove: false, // Deshabilitar swipe, solo navegación por botones
            on: {
                slideChange: function () {
                    updateActiveYear(this.activeIndex);
                }
            }
        });

        /**
         * Actualizar año activo en el timeline
         */
        function updateActiveYear(index) {
            timelineButtons.forEach((btn, i) => {
                if (i === index) {
                    btn.classList.add('is-active');
                } else {
                    btn.classList.remove('is-active');
                }
            });
        }

        /**
         * Click en botones del timeline
         */
        timelineButtons.forEach((button, index) => {
            button.addEventListener('click', () => {
                swiper.slideTo(index);
            });
        });
    });
})();
