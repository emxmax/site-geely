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

        // Helper: solo mobile, centra el tab activo dentro del scroll horizontal
        const scrollActiveTabIntoViewMobile = (btn) => {
            const tabsWrap = btn.closest(".mg-models__tabs"); // contenedor scrollable
            if (!tabsWrap) return;

            // Solo mobile
            if (!window.matchMedia("(max-width: 768px)").matches) return;

            const wrapRect = tabsWrap.getBoundingClientRect();
            const btnRect = btn.getBoundingClientRect();

            const currentScroll = tabsWrap.scrollLeft;

            // Centro del botón dentro del contenedor visible
            const btnCenterInWrap =
                (btnRect.left - wrapRect.left) + (btnRect.width / 2);

            // Queremos que el centro del botón quede en el centro del contenedor
            const targetScroll =
                currentScroll + btnCenterInWrap - (wrapRect.width / 2);

            // Clamp para no pasarnos
            const maxScroll = tabsWrap.scrollWidth - tabsWrap.clientWidth;
            const nextScroll = Math.max(0, Math.min(targetScroll, maxScroll));

            tabsWrap.scrollTo({ left: nextScroll, behavior: "smooth" });

            // Focus accesible sin re-scroll
            btn.focus({ preventScroll: true });
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
                        init(sw) {
                            updateNav(sw, prev, next);
                        },
                        slideChange(sw) {
                            updateNav(sw, prev, next);
                        },
                    },
                });

                updateNav(desktopSwiper, prev, next);
            }

            if (mobileEl) {
                const prevM = panel.querySelector(".mg-models__nav--prev-m");
                const nextM = panel.querySelector(".mg-models__nav--next-m");
                const pag = panel.querySelector(".mg-models__pagination--mobile");

                mobileSwiper = new Swiper(mobileEl, {
                    slidesPerView: 1,
                    speed: 600,
                    loop: false,
                    pagination: { el: pag, clickable: true },
                    navigation: { prevEl: prevM, nextEl: nextM },
                    on: {
                        init(sw) {
                            updateNav(sw, prevM, nextM);
                        },
                        slideChange(sw) {
                            updateNav(sw, prevM, nextM);
                        },
                    },
                });

                updateNav(mobileSwiper, prevM, nextM);
            }

            panelState.set(panel, { desktopSwiper, mobileSwiper });
        });

        const activate = (slug, clickedBtn = null) => {
            tabs.forEach((t) => {
                const isActive = t.getAttribute("data-mg-tab") === slug;
                t.classList.toggle("is-active", isActive);
                t.setAttribute("aria-selected", isActive ? "true" : "false");
                t.setAttribute("tabindex", isActive ? "0" : "-1");
            });

            // si viene desde click (o quieres forzarlo), movemos el tab activo en mobile
            if (clickedBtn) scrollActiveTabIntoViewMobile(clickedBtn);

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
                if (slug) activate(slug, btn);
            });
        });

        // (opcional) asegurar tabindex inicial correcto
        tabs.forEach((t, i) => t.setAttribute("tabindex", i === 0 ? "0" : "-1"));
    });
});
