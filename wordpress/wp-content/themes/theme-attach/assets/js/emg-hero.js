document.addEventListener('DOMContentLoaded', () => {
    // Puede haber más de un hero, pero normalmente será uno
    const heroSections = document.querySelectorAll('.emg-hero');

    heroSections.forEach((hero) => {
        const heroSwiperEl = hero.querySelector('.emg-hero__swiper');

        // Swiper sólo para el fondo
        if (heroSwiperEl && typeof Swiper !== 'undefined') {
            new Swiper(heroSwiperEl, {
                loop: true,
                effect: 'fade',
                speed: 1200,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
            });
        }

        // === Menú mobile flotante ===
        const heroBottom = hero.querySelector('.emg-hero__bottom');
        const navToggle = hero.querySelector('.emg-hero__nav-toggle');
        const nav = hero.querySelector('.emg-hero__nav');
        const mqMobile = window.matchMedia('(max-width: 768px)');

        if (!heroBottom || !navToggle || !nav) return;

        const isMobile = () => mqMobile.matches;

        const closeMenu = () => {
            heroBottom.classList.remove('is-nav-open');
            navToggle.setAttribute('aria-expanded', 'false');
        };

        // Toggle menú sólo en mobile
        navToggle.addEventListener('click', () => {
            if (!isMobile()) return;
            const isOpen = heroBottom.classList.toggle('is-nav-open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Cerrar menú al cambiar a desktop
        mqMobile.addEventListener('change', (ev) => {
            if (!ev.matches) {
                closeMenu();
            }
        });

        // Smooth scroll + cerrar menú al hacer clic en un item
        nav.querySelectorAll('.emg-hero__nav-link').forEach((link) => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href') || '';

                // Si es un anchor interno (#exterior, #interior, etc)
                if (href.startsWith('#')) {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();

                        const headerOffset = 80; // ajusta si tienes un header fijo
                        const rect = target.getBoundingClientRect();
                        const offsetTop = rect.top + window.pageYOffset - headerOffset;

                        // cerramos menú antes de hacer scroll
                        closeMenu();

                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth',
                        });
                    } else {
                        // anchor que no existe: igual cerramos menú
                        closeMenu();
                    }
                } else {
                    // link normal: sólo cerramos menú (por si es mobile)
                    closeMenu();
                }
            });
        });
    });
});
