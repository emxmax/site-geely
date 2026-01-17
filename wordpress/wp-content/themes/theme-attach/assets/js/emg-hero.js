document.addEventListener("DOMContentLoaded", () => {
  /**
   * 1) Swiper (solo fondo) dentro del HERO
   */
  document.querySelectorAll(".emg-hero").forEach((hero) => {
    const heroSwiperEl = hero.querySelector(".emg-hero__swiper");
    if (heroSwiperEl && typeof Swiper !== "undefined") {
      new Swiper(heroSwiperEl, {
        loop: true,
        effect: "fade",
        speed: 1200,
        autoplay: {
          delay: 5000,
          disableOnInteraction: false,
        },
      });
    }
  });

  /**
   * 2) Sticky Nav (ahora está FUERA de .emg-hero)
   *    - toggle solo en mobile
   *    - cerrar al click fuera, ESC, resize
   *    - smooth scroll para anchors con offset = header + sticky
   */
  const sticky = document.querySelector('[data-emg-sticky-nav]');
  if (!sticky) return;

  const heroBottom = sticky.querySelector('[data-emg-bottom]');      // .emg-hero__bottom
  const navToggle  = sticky.querySelector('[data-emg-nav-toggle]');  // button
  const nav        = sticky.querySelector('[data-emg-nav]');         // nav
  const mqMobile   = window.matchMedia("(max-width: 768px)");

  if (!heroBottom || !navToggle || !nav) return;

  const isMobile = () => mqMobile.matches;

  const closeMenu = () => {
    heroBottom.classList.remove("is-nav-open");
    navToggle.setAttribute("aria-expanded", "false");
  };

  const openMenu = () => {
    heroBottom.classList.add("is-nav-open");
    navToggle.setAttribute("aria-expanded", "true");
  };

  const toggleMenu = () => {
    const isOpen = heroBottom.classList.toggle("is-nav-open");
    navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
  };

  // Toggle menú sólo en mobile
  navToggle.addEventListener("click", (e) => {
    if (!isMobile()) return; // desktop: no hace nada
    e.preventDefault();
    e.stopPropagation();
    toggleMenu();
  });

  // Cerrar menú al cambiar a desktop
  mqMobile.addEventListener("change", (ev) => {
    if (!ev.matches) closeMenu();
  });

  // Cerrar menú al hacer click fuera (solo mobile)
  document.addEventListener("click", (e) => {
    if (!isMobile()) return;
    if (!heroBottom.classList.contains("is-nav-open")) return;

    const inside = e.target.closest('[data-emg-sticky-nav]');
    if (!inside) closeMenu();
  });

  // ESC cierra (mobile y desktop, por si quedó abierto)
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeMenu();
  });

  // Smooth scroll + cerrar menú al hacer clic en un item
  nav.querySelectorAll(".emg-hero__nav-link").forEach((link) => {
    link.addEventListener("click", (e) => {
      const href = (link.getAttribute("href") || "").trim();

      // Anchor interno (#exterior, #interior, etc)
      if (href.startsWith("#")) {
        const target = document.querySelector(href);
        if (!target) {
          closeMenu();
          return;
        }

        e.preventDefault();

        // Offsets: header (según tu CSS vars) + sticky height + un pequeño gap
        const headerH = window.innerWidth <= 768
          ? 56
          : 80;

        const stickyH = sticky.offsetHeight || 0;
        const gap = 8;
        const offset = headerH + stickyH + gap;

        const rect = target.getBoundingClientRect();
        const offsetTop = rect.top + window.pageYOffset - offset;

        closeMenu();

        window.scrollTo({
          top: offsetTop,
          behavior: "smooth",
        });

        return;
      }

      // Link normal: cerramos menú (por si es mobile)
      closeMenu();
    });
  });

  /**
   * (Opcional) Si quieres que al hacer scroll se cierre el dropdown en mobile:
   * Útil para que no quede abierto tapando contenido.
   */
  let lastY = window.pageYOffset;
  window.addEventListener("scroll", () => {
    if (!isMobile()) return;
    if (!heroBottom.classList.contains("is-nav-open")) return;

    const y = window.pageYOffset;
    if (Math.abs(y - lastY) > 10) closeMenu();
    lastY = y;
  }, { passive: true });
});
