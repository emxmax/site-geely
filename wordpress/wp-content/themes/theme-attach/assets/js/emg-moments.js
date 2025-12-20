document.addEventListener('DOMContentLoaded', () => {
    const sliders = document.querySelectorAll('.emg-moments__swiper');
    if (!sliders.length || typeof Swiper === 'undefined') return;

    sliders.forEach((slider) => {
        const wrapper = slider.closest('.emg-moments__mobile');
        const paginationEl = wrapper?.querySelector('.emg-moments__pagination');
        if (!paginationEl) return;

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
