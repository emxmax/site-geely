document.addEventListener("DOMContentLoaded", () => {
    if (typeof Swiper === "undefined") return;

    const selectors = window.__MG_MODELS_BLOCKS__ || [];
    selectors.forEach((sel) => {
        const root = document.querySelector(sel);
        if (!root) return;

        const tabs = root.querySelectorAll(".mg-models__tab");
        const panels = root.querySelectorAll(".mg-models__panel");

        const updateNav = (swiper, prev, next) => {
            if (!swiper || !prev || !next) return;
            prev.classList.toggle("is-disabled", swiper.isBeginning);
            next.classList.toggle("is-disabled", swiper.isEnd);
        };

        const panelState = new Map();

        panels.forEach((panel) => {
            const desktopEl = panel.querySelector(".js-mg-swiper-desktop");
            const mobileEl = panel.querySelector(".js-mg-swiper-mobile");

            let desktopSwiper = null;
            let mobileSwiper = null;

            if (desktopEl) {
                const prev = panel.querySelector(".mg-models__nav--prev");
                const next = panel.querySelector(".mg-models__nav--next");
                const pag = panel.querySelector(".mg-models__pagination--desktop");

                desktopSwiper = new Swiper(desktopEl, {
                    slidesPerView: 1,
                    speed: 600,
                    loop: false,
                    pagination: { el: pag, clickable: true },
                    navigation: { prevEl: prev, nextEl: next },
                    on: {
                        init(sw) { updateNav(sw, prev, next); },
                        slideChange(sw) { updateNav(sw, prev, next); },
                    },
                });

                updateNav(desktopSwiper, prev, next);
            }

            if (mobileEl) {
                const pag = panel.querySelector(".mg-models__pagination--mobile");

                mobileSwiper = new Swiper(mobileEl, {
                    slidesPerView: 1,
                    speed: 600,
                    loop: false,
                    pagination: { el: pag, clickable: true },
                });
            }

            panelState.set(panel, { desktopSwiper, mobileSwiper });
        });

        const activate = (slug) => {
            tabs.forEach((t) => {
                const isActive = t.getAttribute("data-mg-tab") === slug;
                t.classList.toggle("is-active", isActive);
                t.setAttribute("aria-selected", isActive ? "true" : "false");
            });

            panels.forEach((p) => {
                const isActive = p.getAttribute("data-mg-panel") === slug;
                p.classList.toggle("is-active", isActive);

                if (isActive) {
                    const st = panelState.get(p);
                    if (st?.desktopSwiper) st.desktopSwiper.update();
                    if (st?.mobileSwiper) st.mobileSwiper.update();
                }
            });
        };

        tabs.forEach((btn) => {
            btn.addEventListener("click", () => {
                const slug = btn.getAttribute("data-mg-tab");
                if (slug) activate(slug);
            });
        });
    });
});
