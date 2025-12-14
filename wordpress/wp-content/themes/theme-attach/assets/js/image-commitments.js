document.addEventListener('DOMContentLoaded', () => {
  if (!window.__IC_BLOCKS__) return;

  window.__IC_BLOCKS__.forEach((selector) => {
    const root = document.querySelector(selector);
    if (!root) return;

    const swiperEl = root.querySelector('.ic-block__swiper');
    const prevBtn = root.querySelector('.ic-block__prev');
    const nextBtn = root.querySelector('.ic-block__next');

    if (!swiperEl) return;

    new Swiper(swiperEl, {
      slidesPerView: 2,
      slidesPerGroup: 1,
      spaceBetween: 20,
      speed: 600,
      loop: false,

      navigation: {
        prevEl: prevBtn,
        nextEl: nextBtn,
      },

      breakpoints: {
        0: {
          slidesPerView: 1,
          slidesPerGroup: 1,
        },
        768: {
          slidesPerView: 2,
          slidesPerGroup: 1,
        }
      }
    });
  });
});
