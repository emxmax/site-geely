document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('.emg-config');

    sections.forEach(section => {
        // Tabs de modelos
        const versionButtons = section.querySelectorAll('.emg-config__version');
        const panels = section.querySelectorAll('.emg-config__panel');

        versionButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const model = btn.dataset.model;

                versionButtons.forEach(b =>
                    b.classList.toggle('is-active', b === btn)
                );

                panels.forEach(panel => {
                    const isActive = panel.dataset.model === model;
                    panel.classList.toggle('is-active', isActive);
                });
            });
        });

        // Selector de colores por panel
        panels.forEach(panel => {
            const dots = panel.querySelectorAll('.emg-config__color-dot');
            const images = panel.querySelectorAll('.emg-config__image');

            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    const color = dot.dataset.color;

                    dots.forEach(d =>
                        d.classList.toggle('is-active', d === dot)
                    );

                    images.forEach(img => {
                        const isActive = img.dataset.color === color;
                        img.classList.toggle('is-active', isActive);
                    });
                });
            });
        });
    });
});
