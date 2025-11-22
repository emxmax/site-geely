document.addEventListener('DOMContentLoaded', () => {
    const heroSwiperEl = document.querySelector('.emg-hero__swiper');

    if (!heroSwiperEl || typeof Swiper === 'undefined') return;

    new Swiper(heroSwiperEl, {
        loop: true,
        effect: 'fade',
        speed: 1200,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
    });
});
