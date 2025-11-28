document.addEventListener('DOMContentLoaded', () => {
    const sliders = document.querySelectorAll('.emg-moments__swiper');

    if (!sliders.length || typeof Swiper === 'undefined') return;

    sliders.forEach((slider) => {
        const paginationEl = slider.querySelector('.emg-moments__pagination');

        new Swiper(slider, {
            slidesPerView: 1,
            spaceBetween: 16,
            loop: true,
            speed: 700,
            autoHeight: false,
            pagination: {
                el: paginationEl,
                clickable: true,
            },
        });
    });
});
