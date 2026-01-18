document.addEventListener("DOMContentLoaded", () => {
    //console.log("[MF] DOMContentLoaded");

    const selectors = window.__MF_BLOCKS__ || [];
    //console.log("[MF] __MF_BLOCKS__ =", selectors);

    if (!selectors.length) {
        //console.warn("[MF] No hay bloques registrados en __MF_BLOCKS__");
        return;
    }

    const qsa = (root, sel) => Array.from(root.querySelectorAll(sel));

    // =========================
    // MODAL helpers
    // =========================
    const onEsc = (e) => {
        if (e.key === "Escape") closeAllModals();
    };

    const closeAllModals = () => {
        document.querySelectorAll(".mf-modal").forEach((m) => m.remove());
        document.body.classList.remove("mf-modal-open");
        document.removeEventListener("keydown", onEsc);
    };

    const bindGlobalModalClose = () => {
        // Evita duplicar listeners si el script se carga 2 veces
        if (window.__MF_MODAL_CLOSE_BOUND__) return;
        window.__MF_MODAL_CLOSE_BOUND__ = true;

        document.addEventListener("click", (e) => {
            // Click en botón cerrar (la X)
            if (e.target.closest(".js-mf-close")) {
                e.preventDefault();
                closeAllModals();
                return;
            }

            // Click en el overlay (fuera del dialog)
            const modal = e.target.closest(".mf-modal");
            const dialog = e.target.closest(".mf-modal__dialog");
            if (modal && !dialog) {
                closeAllModals();
            }
        });
    };


    const openModal = (html) => {
        //console.log("[MF][MODAL] openModal() called");

        closeAllModals();

        const wrap = document.createElement("div");
        wrap.innerHTML = (html || "").trim();
        const modal = wrap.firstElementChild;

        if (!modal) {
            //console.error("[MF][MODAL] HTML inválido");
            return null;
        }

        // 🔑 clave: tu CSS muestra solo con data-open="1"
        modal.setAttribute("data-open", "1");

        document.body.appendChild(modal);
        document.body.classList.add("mf-modal-open");

        const close = () => closeAllModals();

        const closeBtn = modal.querySelector(".js-mf-close");
        if (closeBtn) closeBtn.addEventListener("click", close);

        modal.addEventListener("click", (e) => {
            if (e.target === modal) close();
        });

        document.addEventListener("keydown", onEsc);

        const dialog = modal.querySelector(".mf-modal__dialog");
        if (dialog) {
            dialog.setAttribute("tabindex", "-1");
            setTimeout(() => dialog.focus(), 0);
        }

        return modal;
    };

    // =========================
    // Modal HTML (final)
    // =========================
    const buildModalHtml = (data) => {
        const title = data?.title || "Modelo";
        const img = data?.img || "";
        const usd = data?.usd || "";
        const local = data?.local || "";

        const versions = Array.isArray(data?.versions) ? data.versions : [];
        const spec = data?.specs || {};
        const safe = (x) => (x === null || x === undefined || x === "" ? "-" : String(x));

        const options = versions.length
            ? versions
                .map((v, i) => {
                    const name = v?.name || `Versión ${i + 1}`;
                    return `<option value="${i}">${name}</option>`;
                })
                .join("")
            : `<option value="0">COMFORT 1.5 MT</option>`;

        return `
      <div class="mf-modal" role="dialog" aria-modal="true" aria-label="Versiones">
        <div class="mf-modal__dialog">
          <button class="mf-modal__close js-mf-close" type="button" aria-label="Cerrar">×</button>

          <h2 class="mf-modal__title">Versiones</h2>

          <div class="mf-modal__content">
            <div class="mf-modal__left">
                <div class="mf-modal__model">${title}</div>

              ${img
                ? `<img class="mf-modal__img" src="${img}" alt="${title}" loading="eager" decoding="async"
                       onerror="this.style.display='none';">`
                : ""
            }

              <div class="mf-modal__priceLabel">Precio desde</div>

              ${usd || local
                ? `<div class="mf-modal__price" style="margin-top:24px">
                       <strong>${usd ? `USD ${usd}` : ""}</strong>
                       ${usd && local ? `<span style="margin:0 6px">o</span>` : ""}
                       <strong>${local ? `PEN ${local}` : ""}</strong>
                     </div>`
                : ""
            }

              <div class="mf-modal__selectWrap">
                <select class="mf-modal__select js-mf-version-select" ${versions.length ? "" : "disabled"}>
                  ${options}
                </select>
              </div>
            </div>

            <div class="mf-modal__right">
              <div class="mf-modal__specBox">
                <div class="mf-modal__specTitle">Especificaciones</div>

                <div class="js-mf-specs">
                  <div class="mf-modal__specRow"><strong>Potencia máxima</strong><span>${safe(
                spec.spec_maximum_power
            )}</span></div>
                  <div class="mf-modal__specRow"><strong>Transmisión</strong><span>${safe(
                spec.spec_transmission
            )}</span></div>
                  <div class="mf-modal__specRow"><strong>Seguridad</strong><span>${safe(
                spec.spec_security
            )}</span></div>
                  <div class="mf-modal__specRow"><strong>Asientos</strong><span>${safe(
                spec.spec_seating
            )}</span></div>
                  <div class="mf-modal__specRow"><strong>Encendido</strong><span>${safe(
                spec.spec_sush_button
            )}</span></div>
                </div>

              </div>
            </div>
          </div>

        </div>
      </div>
    `;
    };

    // =========================
    // Cache + Skeleton (rápido)
    // =========================
    const mfCache = new Map();

    const buildModalSkeleton = (base) => {
        const title = base?.title || "Modelo";
        const img = base?.img || "";

        return `
      <div class="mf-modal" role="dialog" aria-modal="true" aria-label="Versiones">
        <div class="mf-modal__dialog">
          <button class="mf-modal__close js-mf-close" type="button" aria-label="Cerrar">×</button>

          <h2 class="mf-modal__title">Versiones</h2>
          <div class="mf-modal__model">${title}</div>

          <div class="mf-modal__content">
            <div class="mf-modal__left">
              ${img
                ? `<img class="mf-modal__img" src="${img}" alt="${title}" loading="eager" decoding="async"
                       onerror="this.style.display='none';">`
                : ""
            }
              <div class="mf-modal__priceLabel" style="opacity:.7;margin-top:10px">Cargando versiones…</div>
            </div>

            <div class="mf-modal__right">
              <div class="mf-modal__specBox">
                <div class="mf-modal__specTitle">Especificaciones</div>
                <div class="mf-modal__priceLabel" style="opacity:.7">Cargando…</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    `;
    };

    // =========================
    // AJAX
    // =========================
    const fetchVersions = async (productId) => {
        //console.log("[MF][AJAX] fetchVersions productId =", productId);

        if (!window.mfFinder?.ajaxUrl) {
            //console.error("[MF][AJAX] mfFinder.ajaxUrl NO definido. window.mfFinder =", window.mfFinder);
            throw new Error("mfFinder.ajaxUrl no definido");
        }

        const fd = new FormData();
        fd.append("action", "mf_product_modal_ajax");
        fd.append("nonce", window.mfFinder.nonce || "");
        fd.append("product_id", String(productId));

        const res = await fetch(window.mfFinder.ajaxUrl, {
            method: "POST",
            body: fd,
            credentials: "same-origin",
        });

        const text = await res.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("[MF][AJAX] Respuesta NO es JSON válido:", text);
            throw e;
        }

        if (!data?.ok) {
            throw new Error(data?.message || "No se pudo cargar el modal");
        }

        return data;
    };

    // =========================
    // TABS
    // =========================
    const initTabs = (root) => {
        const tabs = qsa(root, ".mf__tab");
        const panels = qsa(root, ".mf__panel");
        if (!tabs.length || !panels.length) return;

        const activate = (slug) => {
            tabs.forEach((t) => {
                const isActive = t.getAttribute("data-mf-tab") === slug;
                t.classList.toggle("is-active", isActive);
                t.setAttribute("aria-selected", isActive ? "true" : "false");
            });

            panels.forEach((p) => {
                const isActive = p.getAttribute("data-mf-panel") === slug;
                p.classList.toggle("is-active", isActive);
            });
        };

        tabs.forEach((t) => {
            t.addEventListener("click", () => {
                const slug = t.getAttribute("data-mf-tab");
                activate(slug);
            });
        });

        const firstActive = tabs.find((t) => t.classList.contains("is-active")) || tabs[0];
        if (firstActive) activate(firstActive.getAttribute("data-mf-tab"));
    };

    // =========================
    // MODAL click handler
    // =========================
    const initModal = (root) => {
        root.addEventListener(
            "click",
            async (e) => {
                const btn = e.target.closest(".js-mf-open-versions");
                if (!btn) return;

                e.preventDefault();
                e.stopPropagation();

                const productId = btn.getAttribute("data-product-id");
                if (!productId) return;

                const base = {
                    title: btn.getAttribute("data-title") || "",
                    img: btn.getAttribute("data-img") || "",
                    usd: btn.getAttribute("data-usd") || "",
                    local: btn.getAttribute("data-local") || "",
                };

                // ✅ 1) Abre instantáneo (skeleton)
                const modalEl = openModal(buildModalSkeleton(base));
                if (!modalEl) return;

                // ✅ 2) Cache -> inmediato
                if (mfCache.has(productId)) {
                    const cached = mfCache.get(productId);
                    const merged = {
                        ...cached,
                        title: cached.title || base.title,
                        img: cached.img || base.img,
                        usd: cached.usd || base.usd,
                        local: cached.local || base.local,
                    };

                    const wrap = document.createElement("div");
                    wrap.innerHTML = buildModalHtml(merged).trim();
                    const finalEl = wrap.firstElementChild;

                    if (finalEl) {
                        finalEl.setAttribute("data-open", "1");
                        modalEl.replaceWith(finalEl);
                    }
                    return;
                }

                btn.disabled = true;

                try {
                    const ajaxData = await fetchVersions(productId);
                    mfCache.set(productId, ajaxData);

                    const merged = {
                        ...ajaxData,
                        title: ajaxData.title || base.title,
                        img: ajaxData.img || base.img,
                        usd: ajaxData.usd || base.usd,
                        local: ajaxData.local || base.local,
                    };

                    const wrap = document.createElement("div");
                    wrap.innerHTML = buildModalHtml(merged).trim();
                    const finalEl = wrap.firstElementChild;

                    if (finalEl) {
                        finalEl.setAttribute("data-open", "1");
                        modalEl.replaceWith(finalEl);
                    }
                } catch (err) {
                    console.error("[MF] ERROR modal:", err);
                    const label = modalEl.querySelector(".mf-modal__priceLabel");
                    if (label) label.textContent = "No se pudieron cargar las versiones.";
                } finally {
                    btn.disabled = false;
                }
            },
            true
        );
    };

    // =========================
    // INIT
    // =========================
    selectors.forEach((sel) => {

        bindGlobalModalClose();

        const root = document.querySelector(sel);
        if (!root) return;

        initTabs(root);
        initModal(root);
    });
});
