/**
 * About Us Tech Carousel
 * Inicializa el carrusel de tecnología con Swiper
 */

document.addEventListener('DOMContentLoaded', () => {
  if (typeof Swiper === 'undefined') return;

  const selectors = window.__ABOUT_TECH_CAROUSELS__ || [];
  
  selectors.forEach((selector) => {
    const root = document.querySelector(selector);
    if (!root) return;

    const swiperEl = root.querySelector('.about-tech__swiper');
    const prevBtn = root.querySelector('.about-tech__nav--prev');
    const nextBtn = root.querySelector('.about-tech__nav--next');
    const paginationEl = root.querySelector('.about-tech__pagination');

    if (!swiperEl) return;

    new Swiper(swiperEl, {
      slidesPerView: 1,
      spaceBetween: 24,
      speed: 600,
      loop: false,
      autoHeight: false,
      pagination: {
        el: paginationEl,
        clickable: true,
        dynamicBullets: true,
      },
      navigation: {
        prevEl: prevBtn,
        nextEl: nextBtn,
      },
      breakpoints: {
        768: {
          slidesPerView: 1,
          spaceBetween: 32,
        },
        1024: {
          slidesPerView: 1,
          spaceBetween: 40,
        },
      },
    });
  });
});
