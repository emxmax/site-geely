document.addEventListener("DOMContentLoaded", () => {
    if (typeof Swiper === "undefined") return;

    const selectors = window.__EXP_CAROUSELS__ || [];
    selectors.forEach((sel) => {
        const root = document.querySelector(sel);
        if (!root) return;

        const swiperEl = root.querySelector(".exp-carousel__swiper");
        const prevBtn = root.querySelector(".exp-carousel__prev");
        const nextBtn = root.querySelector(".exp-carousel__next");
        const paginationEl = root.querySelector(".exp-carousel__pagination");

        if (!swiperEl) return;

        new Swiper(swiperEl, {
            slidesPerView: 1,
            spaceBetween: 24,
            speed: 600,
            loop: false,
            pagination: { el: paginationEl, clickable: true },
            navigation: { prevEl: prevBtn, nextEl: nextBtn },
            breakpoints: { 1024: { spaceBetween: 32 } },
        });
    });
});
