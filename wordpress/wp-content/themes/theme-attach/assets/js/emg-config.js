(function () {
    function log(...args) {
        console.log('[EMG-CONFIG]', ...args);
    }

    window.CI360 =
        window.CI360 ||
        {};

    window.CI360.rotateOnInit = false;   // evita giro inicial automático
    window.CI360.autoplay = false;       // desactiva autoplay
    window.CI360.fullScreenButton = false;

    function initAll360(reason) {
        if (!window.CI360 || typeof CI360.init !== 'function') {
            log('CI360 NO disponible aún. Razón:', reason, 'CI360 =', window.CI360);
            return;
        }

        // Re-init global
        log('Llamando CI360.init() – Razón:', reason);
        setTimeout(() => CI360.init(), 30);

    }

    document.addEventListener('DOMContentLoaded', () => {
        const sections = document.querySelectorAll('.emg-config');

        log('DOMContentLoaded. Secciones encontradas:', sections.length);
        const allViewers = document.querySelectorAll('.cloudimage-360');
        allViewers.forEach((v, idx) => {
            log(
                `Viewer #${idx}`,
                'data-color=', v.dataset.color,
                'folder=', v.dataset.folder,
                'filename-x=', v.dataset.filenameX,
                'amount-x=', v.dataset.amountX
            );
        });

        initAll360('inicio de página');

        sections.forEach(section => {
            // Tabs de modelos
            const versionButtons = section.querySelectorAll('.emg-config__version');
            const panels = section.querySelectorAll('.emg-config__panel');

            log('Section:', section, '| versions=', versionButtons.length, '| panels=', panels.length);

            versionButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const model = btn.dataset.model;
                    log('Click TAB modelo:', model);

                    versionButtons.forEach(b =>
                        b.classList.toggle('is-active', b === btn)
                    );

                    panels.forEach(panel => {
                        const isActive = panel.dataset.model === model;
                        panel.classList.toggle('is-active', isActive);

                        if (isActive) {
                            const viewers = panel.querySelectorAll('.cloudimage-360');
                            log(
                                'Panel activo para modelo',
                                model,
                                '| viewers 360 encontrados =',
                                viewers.length
                            );
                        }
                    });

                    // Reinit 360 cuando cambio de TAB
                    initAll360('cambio de TAB modelo ' + model);
                });
            });

            // Selector de colores por panel
            panels.forEach(panel => {
                const dots = panel.querySelectorAll('.emg-config__color-dot');
                // OJO: incluimos también .cloudimage-360
                const images = panel.querySelectorAll('.emg-config__image, .cloudimage-360');

                log(
                    'Panel modelo', panel.dataset.model,
                    '| dots colores =', dots.length,
                    '| imágenes (estáticas + 360) =', images.length
                );

                dots.forEach(dot => {
                    dot.addEventListener('click', () => {
                        const color = dot.dataset.color;
                        log('Click COLOR:', color, '| panel modelo:', panel.dataset.model);

                        dots.forEach(d =>
                            d.classList.toggle('is-active', d === dot)
                        );

                        images.forEach(img => {
                            const isActive = img.dataset.color === color;
                            img.classList.toggle('is-active', isActive);
                        });

                        const activeViewer = panel.querySelector(
                            '.cloudimage-360.is-active'
                        );

                        if (activeViewer) {
                            log(
                                'Color con 360 activo:',
                                color,
                                '| folder=', activeViewer.dataset.folder,
                                '| filename-x=', activeViewer.dataset.filenameX,
                                '| amount-x=', activeViewer.dataset.amountX
                            );
                        } else {
                            log(
                                'Color SIN viewer 360 (solo estática). Color:',
                                color
                            );
                        }

                        // Reinit 360 tras cambiar color
                        initAll360('cambio de color ' + color);
                    });
                });
            });

            // Inicial 360 para el primer panel visible
            const firstActivePanel = section.querySelector('.emg-config__panel.is-active');
            if (firstActivePanel) {
                const firstActiveViewer = firstActivePanel.querySelector(
                    '.cloudimage-360.is-active'
                );
                if (firstActiveViewer) {
                    log(
                        'Primer panel activo tiene viewer 360:',
                        'color=', firstActiveViewer.dataset.color,
                        'folder=', firstActiveViewer.dataset.folder
                    );
                    initAll360('primer panel activo con 360');
                } else {
                    log('Primer panel activo NO tiene viewer 360 (solo imagen estática)');
                }
            }
        });
    });
})();
