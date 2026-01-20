document.addEventListener("DOMContentLoaded", () => {
  if (typeof Swiper === "undefined") return;

  const selectors = window.__EXP_CAROUSELS__ || [];
  selectors.forEach((sel) => {
    const root = document.querySelector(sel);
    if (!root) return;

    // Evita inicializar 2 veces el mismo carrusel
    if (root.dataset.expInited === "1") return;
    root.dataset.expInited = "1";

    const swiperEl = root.querySelector(".exp-carousel__swiper");

    // Clases correctas según tu HTML
    const prevBtn = root.querySelector(".exp-carousel__nav--prev");
    const nextBtn = root.querySelector(".exp-carousel__nav--next");
    const paginationEl = root.querySelector(".exp-carousel__pagination");

    if (!swiperEl) return;

    new Swiper(swiperEl, {
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
    });
  });
});
