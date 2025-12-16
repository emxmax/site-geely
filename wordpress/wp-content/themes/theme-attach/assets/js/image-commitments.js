document.addEventListener('DOMContentLoaded', () => {
  if (!window.__IC_BLOCKS__) return;

  window.__IC_BLOCKS__.forEach((selector) => {
    const root = document.querySelector(selector);
    if (!root) return;

    const swiperEl = root.querySelector('.ic-block__swiper');
    const prevBtn = root.querySelector('.ic-block__prev');
    const nextBtn = root.querySelector('.ic-block__next');
    const paginationEl = root.querySelector('.ic-block__pagination');

    if (!swiperEl || typeof Swiper === 'undefined') return;

    new Swiper(swiperEl, {
      // Desktop default
      slidesPerView: 2,
      slidesPerGroup: 1,
      spaceBetween: 20,
      speed: 600,
      loop: false,

      navigation: {
        prevEl: prevBtn,
        nextEl: nextBtn,
        disabledClass: 'is-disabled',
      },

      pagination: paginationEl
        ? {
            el: paginationEl,
            clickable: true,
          }
        : undefined,

      breakpoints: {
        // Mobile: "dos en dos" pero en vertical (2 filas)
        0: {
          slidesPerView: 1,
          slidesPerGroup: 1,
          spaceBetween: 14,
          grid: {
            rows: 2,
            fill: 'row',
          },
        },

        // Desktop/Tablet: 2 cards en horizontal (1 fila)
        769: {
          slidesPerView: 2,
          slidesPerGroup: 1,
          spaceBetween: 20,
          grid: {
            rows: 1,
            fill: 'row',
          },
        },
      },
    });
  });
});
