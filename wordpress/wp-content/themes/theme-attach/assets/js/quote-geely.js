(() => {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const getUrlParams = () => {
    const params = {};
    const usp = new URLSearchParams(window.location.search);
    usp.forEach((v, k) => (params[k] = v));
    return params;
  };

  const setFieldById = (id, value, trigger = false) => {
    const el = document.getElementById(id);
    if (!el) return false;
    el.value = value ?? "";
    if (trigger) {
      el.dispatchEvent(new Event("input", { bubbles: true }));
      el.dispatchEvent(new Event("change", { bubbles: true }));
    }
    return true;
  };

  const ajaxPost = async (action, data = {}) => {
    const cfg = window.MG_QUOTE_AJAX || {};
    const url = cfg.url;
    const nonce = cfg.nonce;

    if (!url) throw new Error("MG_QUOTE_AJAX.url no definido");

    const body = new URLSearchParams();
    body.set("action", action);
    body.set("nonce", nonce || "");
    Object.entries(data).forEach(([k, v]) => body.set(k, String(v)));

    const resp = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: body.toString(),
      credentials: "same-origin",
    });

    const json = await resp.json().catch(() => null);
    return json;
  };

  /** =========================
   *  1) Tracking + product_id por URL
   * ========================= */
  const params = getUrlParams();

  const fillTrackingHidden = () => {
    setFieldById("utm_source", params.utm_source || "", true);
    setFieldById("utm_medium", params.utm_medium || "", true);
    setFieldById("utm_campaign", params.utm_campaign || "", true);
    setFieldById("utm_term", params.utm_term || "", true);
    setFieldById("utm_content", params.utm_content || "", true);
    setFieldById("gclid", params.gclid || "", true);
    setFieldById("fbclid", params.fbclid || "", true);

    if (params.product_id) setFieldById("product_id", params.product_id, true);
  };

  /** =========================
   *  2) CF7 logs
   * ========================= */
  const initCf7Logs = () => {
    document.addEventListener("wpcf7beforesubmit", (e) => {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;

      if (typeof form.__mgValidateAll === "function") {
        const ok = form.__mgValidateAll();
        if (!ok) {
          e.preventDefault();
          const firstInvalid = form.querySelector(".is-invalid");
          firstInvalid?.scrollIntoView({ behavior: "smooth", block: "center" });
          firstInvalid?.focus?.();
        }
      }
    });

    document.addEventListener("wpcf7submit", (e) => {
      const res = e.detail?.apiResponse;
      console.log("[MG_QUOTE] wpcf7submit apiResponse:", res);

      if (res?.mg_payload) console.log("[MG_QUOTE] PAYLOAD SENT TO API (body):", res.mg_payload);
      else console.warn("[MG_QUOTE] No llegó mg_payload. Revisa el filtro wpcf7_ajax_json_echo.");

      if (res?.mg_api) console.log("[MG_QUOTE] API RESULT (backend):", res.mg_api);
      else console.warn("[MG_QUOTE] No llegó mg_api. Revisa el filtro wpcf7_ajax_json_echo.");
    });

    document.addEventListener("wpcf7mailsent", (e) => {
      console.log("[MG_QUOTE] wpcf7mailsent (OK)", e.detail?.apiResponse);
    });

    document.addEventListener("wpcf7mailfailed", (e) => {
      console.warn("[MG_QUOTE] wpcf7mailfailed", e.detail?.apiResponse);
    });

    document.addEventListener("wpcf7invalid", (e) => {
      console.warn("[MG_QUOTE] wpcf7invalid (validación)", e.detail?.apiResponse);
    });

    document.addEventListener("click", (e) => {
      const btn = e.target.closest('input.wpcf7-submit, button.wpcf7-submit, .geely-cotiza-submit');
      if (!btn) return;
      console.log("[MG_QUOTE] CLICK Cotizar", btn, { disabled: btn.disabled, aria: btn.getAttribute("aria-disabled") });
    });
  };

  /** =========================
  *  3) Carga dinámica: Tiendas por Departamento (ROBUSTO)
  *  - event delegation (no se pierde si CF7 re-renderiza)
  *  - pinta opciones en #cot_store_ui (UI)
  *  - copia el valor a #cot_store (hidden CF7)
  * ========================= */
  const initDeptStoreDynamic = () => {
    // montar una sola vez globalmente
    if (window.__mgDeptStoreGlobalMounted) return true;
    window.__mgDeptStoreGlobalMounted = true;

    const STORE_UI_ID = "cot_store_ui";
    const STORE_HIDDEN_ID = "cot_store";
    const PLACEHOLDER_TEXT = "Selecciona una opción";

    // cache: departmentId -> items (stores)
    const storeCacheByDept = {};

    const getCtx = () => {
      const form = document.querySelector(".wpcf7 form");
      const deptEl = form?.querySelector('select[name="cot_department"]') || null;
      const storeUiEl = document.getElementById(STORE_UI_ID);
      const storeHiddenEl = document.getElementById(STORE_HIDDEN_ID);
      return { form, deptEl, storeUiEl, storeHiddenEl };
    };

    const setLoadingStores = (storeUiEl, loading) => {
      if (!storeUiEl) return;
      storeUiEl.disabled = !!loading;
      storeUiEl.classList.toggle("is-loading", !!loading);
    };

    // ====== UI bloque recomendaciones (compartido) ======
    const ensureNearStoresBox = (form) => {
      if (!form) return null;

      let box = form.querySelector(".mg-nearStores");
      if (box) return box;

      const geoWrap = form.querySelector(".geely-cotiza-geo"); // si existe, lo insertamos debajo

      box = document.createElement("div");
      box.className = "mg-nearStores";
      box.style.display = "none";
      box.innerHTML = `
      <div class="mg-nearStores__title">
        También contamos con estos concesionarios a tu disposición:
      </div>

      <div class="mg-nearStores__row js-nearStores-row"></div>

      <button type="button" class="mg-nearStores__link js-nearStores-more" style="display:none;">
        Ver más concesionarios
      </button>
    `;

      if (geoWrap && geoWrap.parentNode) {
        geoWrap.parentNode.insertBefore(box, geoWrap.nextSibling);
      } else {
        form.appendChild(box);
      }

      return box;
    };

    const clearRecommendationsUI = () => {
      const { form } = getCtx();
      const box = ensureNearStoresBox(form);
      if (!box) return;

      const row = box.querySelector(".js-nearStores-row");
      const link = box.querySelector(".js-nearStores-more");
      if (row) row.innerHTML = "";
      if (link) link.style.display = "none";
      box.style.display = "none";
    };

    const renderRecommendationsUI = (items, mainStoreId, opts = {}) => {
      const { form } = getCtx();
      const box = ensureNearStoresBox(form);
      if (!box) return;

      const row = box.querySelector(".js-nearStores-row");
      const link = box.querySelector(".js-nearStores-more");
      if (!row || !link) return;

      row.innerHTML = "";
      link.style.display = "none";

      const recs = (items || [])
        .map((x) => ({
          id: String(x.id ?? x.value ?? "").trim(),
          name: (x.name || x.label || "").trim(),
        }))
        .filter((x) => x.id && x.id !== String(mainStoreId))
        .slice(0, 5);

      if (!recs.length) {
        box.style.display = "none";
        return;
      }

      box.style.display = "block";

      // Si luego quieres modal/mapa, lo dejas aquí:
      link.style.display = "inline-flex";
      link.onclick = (e) => {
        e.preventDefault();
        console.log("[MG_QUOTE] click Ver más concesionarios (TODO modal)", opts);
      };

      // Click en recomendado => setea combo tienda + refresca recomendaciones (si aplica)
      const onPickRecommendation = async (storeId, storeName) => {
        const ok = setStoreByIdOnUI(storeId) || (await waitForStoreOptionThenSelect(storeId));
        if (!ok) {
          ensureOption(storeUiEl, storeId, storeName || storeId);
          setStoreByIdOnUI(storeId);
        }
        // Al cambiar tienda, el listener de change disparará refreshRecommendations()
      };

      recs.forEach((it) => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "mg-nearStores__btn";
        b.textContent = it.name || "Concesionario";
        b.addEventListener("click", () => onPickRecommendation(it.id, it.name));
        row.appendChild(b);
      });
    };

    // ====== helpers select ======
    const ensureOption = (selectEl, value, label) => {
      if (!selectEl || !value) return false;
      const exists = Array.from(selectEl.options).some((o) => o.value === value);
      if (!exists) {
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = label || value;
        selectEl.appendChild(opt);
      }
      return true;
    };

    const resetStoreToPlaceholder = () => {
      const { storeUiEl, storeHiddenEl } = getCtx();
      if (!storeUiEl) return;

      storeUiEl.innerHTML = "";

      const ph = document.createElement("option");
      ph.value = "";
      ph.textContent = PLACEHOLDER_TEXT;
      ph.disabled = true;
      ph.hidden = true;
      ph.selected = true;
      storeUiEl.appendChild(ph);

      storeUiEl.value = "";

      if (storeHiddenEl) {
        storeHiddenEl.value = "";
        storeHiddenEl.dispatchEvent(new Event("input", { bubbles: true }));
        storeHiddenEl.dispatchEvent(new Event("change", { bubbles: true }));
      }

      storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
      storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));
    };

    const fillStoreOptions = (items) => {
      const { storeUiEl } = getCtx();
      if (!storeUiEl) return;

      resetStoreToPlaceholder();

      (items || []).forEach((it) => {
        const opt = document.createElement("option");
        opt.value = String(it.id ?? it.value ?? "").trim(); // SOLO ID
        opt.textContent = it.label || it.name || "";
        if (!opt.value) return;
        storeUiEl.appendChild(opt);
      });
    };

    const setStoreByIdOnUI = (storeId) => {
      const { storeUiEl } = getCtx();
      if (!storeUiEl || !storeId) return false;

      const v = String(storeId).trim();
      if (!v) return false;

      const opt = storeUiEl.querySelector(`option[value="${CSS.escape(v)}"]`);
      if (!opt) return false;

      storeUiEl.value = v;
      storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
      storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));
      return true;
    };

    const waitForStoreOptionThenSelect = (storeId, triesMax = 40, everyMs = 200) => {
      return new Promise((resolve) => {
        let tries = 0;
        const t = setInterval(() => {
          tries++;
          const ok = setStoreByIdOnUI(storeId);
          if (ok) {
            clearInterval(t);
            resolve(true);
            return;
          }
          if (tries >= triesMax) {
            clearInterval(t);
            resolve(false);
          }
        }, everyMs);
      });
    };

    // ====== recomendaciones: Lima = API, No Lima = resto de tiendas del dept ======
    const fetchRecommendationsLima = async ({ regionId, mainStoreId }) => {
      try {
        const r = await ajaxPost("mg_quote_get_store_recommendations", {
          regionId,
          tiendaMainId: mainStoreId, // 👈 tu PHP lo lee así
        });
        return r?.success ? (r.data?.items || []) : [];
      } catch (e) {
        console.warn("[MG_QUOTE] Error mg_quote_get_store_recommendations:", e);
        return [];
      }
    };

    const refreshRecommendations = async ({ dept, selectedStoreId }) => {
      const { form } = getCtx();
      if (!form) return;

      const deptId = String(dept || "").trim();
      const storeId = String(selectedStoreId || "").trim();

      // sin dept o sin tienda => ocultar
      if (!deptId || !storeId) {
        clearRecommendationsUI();
        return;
      }

      // LIMA => API
      if (deptId === "16") {
        const items = await fetchRecommendationsLima({ regionId: "16", mainStoreId: storeId });
        // si no hay recs, ocultar
        if (!items || !items.length) {
          clearRecommendationsUI();
          return;
        }
        renderRecommendationsUI(items, storeId, { mode: "lima-api", deptId });
        return;
      }

      // NO LIMA => usar stores del dept en cache (resto)
      const stores = storeCacheByDept[deptId] || [];
      const recs = stores.filter((x) => String(x.id ?? x.value ?? "").trim() !== storeId);

      if (!recs.length) {
        clearRecommendationsUI(); // 👈 si solo hay 1 tienda, NO se ve el bloque
        return;
      }

      renderRecommendationsUI(recs, storeId, { mode: "non-lima-from-stores", deptId });
    };

    const autoSelectFirstStoreIfAny = async (deptId, items) => {
      const { storeUiEl, storeHiddenEl } = getCtx();
      if (!storeUiEl) return;

      const first = (items || [])[0];
      const firstId = String(first?.id ?? first?.value ?? "").trim();
      const firstLabel = String(first?.label ?? first?.name ?? "").trim();

      if (!firstId) {
        clearRecommendationsUI();
        return;
      }

      // si no existe opción aún, la aseguramos
      ensureOption(storeUiEl, firstId, firstLabel);

      storeUiEl.value = firstId;
      storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
      storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));

      // sync hidden
      if (storeHiddenEl) {
        storeHiddenEl.value = firstId;
        storeHiddenEl.dispatchEvent(new Event("input", { bubbles: true }));
        storeHiddenEl.dispatchEvent(new Event("change", { bubbles: true }));
      }

      // 👇 aquí aplicamos la regla:
      // - si items.length === 1 => ocultar bloque
      // - si items.length > 1 => mostrar recomendaciones (No Lima = resto, Lima = API)
      if ((items || []).length <= 1) {
        clearRecommendationsUI();
        return;
      }

      await refreshRecommendations({ dept: deptId, selectedStoreId: firstId });
    };

    const loadStoresByDept = async (deptValue) => {
      const { storeUiEl } = getCtx();
      if (!storeUiEl) return;

      const dept = String(deptValue || "").trim();
      console.log("[MG_QUOTE] loadStoresByDept ->", dept);

      if (!dept) {
        resetStoreToPlaceholder();
        clearRecommendationsUI();
        return;
      }

      resetStoreToPlaceholder();
      clearRecommendationsUI(); // mientras carga, ocultamos
      setLoadingStores(storeUiEl, true);

      try {
        const res = await ajaxPost("mg_quote_get_stores", { department: dept });
        console.log("[MG_QUOTE] stores res:", res);

        const items = res?.success ? (res.data?.items || []) : [];
        storeCacheByDept[dept] = items;

        fillStoreOptions(items);

        // regla nueva: auto-setear 1ra tienda si existe
        await autoSelectFirstStoreIfAny(dept, items);
      } catch (err) {
        console.warn("[MG_QUOTE] Error loading stores:", err);
        resetStoreToPlaceholder();
        clearRecommendationsUI();
      } finally {
        setLoadingStores(storeUiEl, false);
      }
    };

    // Exponer para otros módulos (como tu GEO)
    Object.defineProperty(window, "__mgLoadStoresByDept", {
      value: loadStoresByDept,
      configurable: true,
    });

    // ===== listeners =====

    // cambio dept => cargar stores + auto-select + recomendaciones según regla
    document.addEventListener(
      "change",
      (e) => {
        const t = e.target;
        if (!(t instanceof HTMLSelectElement)) return;
        if (!t.matches('select[name="cot_department"]')) return;

        console.log("[MG_QUOTE] dept change detected:", t.value);
        loadStoresByDept(t.value || "");
      },
      true
    );

    // cambio tienda UI => sync hidden + refrescar recomendaciones (Lima: API / No Lima: resto)
    document.addEventListener(
      "change",
      async (e) => {
        const t = e.target;
        if (!(t instanceof HTMLSelectElement)) return;
        if (t.id !== STORE_UI_ID) return;

        const v = (t.value || "").trim();
        const { storeHiddenEl, deptEl } = getCtx();

        console.log("[MG_QUOTE] store_ui change -> hidden:", v);

        if (storeHiddenEl) {
          storeHiddenEl.value = v; // ID
          storeHiddenEl.dispatchEvent(new Event("input", { bubbles: true }));
          storeHiddenEl.dispatchEvent(new Event("change", { bubbles: true }));
        }

        // NUEVO: al cambiar tienda manualmente, disparar recomendaciones
        const deptNow = String(deptEl?.value || "").trim();
        await refreshRecommendations({ dept: deptNow, selectedStoreId: v });
      },
      true
    );

    // init
    resetStoreToPlaceholder();
    clearRecommendationsUI();

    const { deptEl } = getCtx();
    if (deptEl?.value) {
      loadStoresByDept(deptEl.value);
    }

    return true;
  };


  /** =========================
   *  4) GEO + recomendación tienda cercana (NUEVA LÓGICA)
   *  - obtiene 1 tienda cercana
   *  - setea departamento (dispara mg_quote_get_stores)
   *  - espera carga y setea tienda cercana en #cot_store_ui
   *  - recomendaciones: action nuevo mg_quote_get_store_recommendations (si existe)
   *    fallback: usa items.slice(1) del action antiguo mg_quote_nearest_stores
   *  - NUEVO: si cambia el combo TIENDA manualmente, refresca recomendaciones (LIMA)
   * ========================= */
  const initGeo = () => {
    const form = document.querySelector(".wpcf7 form");
    if (!form) return false;

    // evita duplicar listeners
    if (form.__mgGeoMounted) return true;
    form.__mgGeoMounted = true;

    const root = form.closest(".mg-quote") || document.querySelector(".mg-quote");

    const geoBtn = $("#geely-cotiza-geo-btn");
    const latId = "geely-cotiza-lat";
    const lngId = "geely-cotiza-lng";

    const deptEl = form.querySelector('select[name="cot_department"]');
    const storeUiEl = document.getElementById("cot_store_ui");
    const storeHiddenId = "cot_store";

    if (!geoBtn || !deptEl || !storeUiEl) return false;

    const setGeoHidden = (lat, lng) => {
      setFieldById(latId, String(lat), true);
      setFieldById(lngId, String(lng), true);
    };

    const ensureDeniedModal = () => {
      let modal = document.querySelector(".mg-geoModal");
      if (modal) return modal;

      const bgUrl = root?.getAttribute("data-geo-bg") || "";

      modal = document.createElement("div");
      modal.className = "mg-geoModal";
      modal.innerHTML = `
      <div class="mg-geoModal__card" role="dialog" aria-modal="true" aria-label="Permiso de ubicación desactivado">
        <button class="mg-geoModal__close" type="button" aria-label="Cerrar">×</button>
        <div class="mg-geoModal__body">
          <div class="mg-geoModal__title">Permiso de ubicación desactivado</div>
          <p class="mg-geoModal__text">
            Has bloqueado previamente el acceso a la ubicación para este sitio.
            Para continuar, permite el acceso en la configuración de tu navegador,
            recarga la página y vuelve a intentarlo.
          </p>
          <button class="mg-geoModal__btn" type="button">Aceptar</button>
        </div>
      </div>
    `;
      document.body.appendChild(modal);

      const card = modal.querySelector(".mg-geoModal__card");
      if (card && bgUrl) {
        card.style.backgroundImage = `url("${bgUrl}")`;
        card.style.backgroundSize = "cover";
        card.style.backgroundPosition = "center";
        card.style.backgroundRepeat = "no-repeat";
      }

      const close = () => modal.classList.remove("is-open");

      modal.addEventListener("click", (e) => {
        if (e.target === modal) close();
      });
      modal.querySelector(".mg-geoModal__close")?.addEventListener("click", close);
      modal.querySelector(".mg-geoModal__btn")?.addEventListener("click", close);

      return modal;
    };

    const openDeniedModal = () => {
      const modal = ensureDeniedModal();
      modal.classList.add("is-open");
    };

    const openGeoErrorModal = (title, text) => {
      let modal = document.querySelector(".mg-geoModal");
      if (!modal) modal = ensureDeniedModal();

      const t = modal.querySelector(".mg-geoModal__title");
      const p = modal.querySelector(".mg-geoModal__text");
      if (t) t.textContent = title;
      if (p) p.textContent = text;

      modal.classList.add("is-open");
    };

    const ensureNearStoresBox = () => {
      let box = form.querySelector(".mg-nearStores");
      if (box) return box;

      const geoWrap = form.querySelector(".geely-cotiza-geo");

      box = document.createElement("div");
      box.className = "mg-nearStores";
      box.style.display = "none";

      box.innerHTML = `
      <div class="mg-nearStores__title">
        También contamos con estos concesionarios a tu disposición:
      </div>

      <!-- botones de recomendaciones (1 a 5) -->
      <div class="mg-nearStores__row js-nearStores-row"></div>

      <button type="button" class="mg-nearStores__link js-nearStores-more" style="display:none;">
        Ver más concesionarios
      </button>
    `;

      if (geoWrap && geoWrap.parentNode) {
        geoWrap.parentNode.insertBefore(box, geoWrap.nextSibling);
      } else {
        form.appendChild(box);
      }

      return box;
    };

    const ensureOption = (selectEl, value, label) => {
      if (!selectEl || !value) return false;
      const exists = Array.from(selectEl.options).some((o) => o.value === value);
      if (!exists) {
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = label || value;
        selectEl.appendChild(opt);
      }
      return true;
    };

    const setSelectValue = (selectEl, value, label) => {
      if (!selectEl || !value) return false;

      ensureOption(selectEl, value, label);

      selectEl.value = value;
      selectEl.dispatchEvent(new Event("change", { bubbles: true }));
      selectEl.dispatchEvent(new Event("input", { bubbles: true }));
      return true;
    };

    // FLAG para no duplicar llamadas cuando el script setea tienda
    let __mgStoreChangeInternal = false;

    const setStoreByIdOnUI = (storeId) => {
      if (!storeUiEl || !storeId) return false;

      const v = String(storeId).trim();
      if (!v) return false;

      const opt = storeUiEl.querySelector(`option[value="${CSS.escape(v)}"]`);
      if (!opt) return false;

      __mgStoreChangeInternal = true;

      storeUiEl.value = v;
      storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
      storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));

      // sincroniza hidden CF7
      setFieldById(storeHiddenId, v, true);

      // liberar flag en el próximo tick
      setTimeout(() => {
        __mgStoreChangeInternal = false;
      }, 0);

      return true;
    };

    const waitForStoreOptionThenSelect = (storeId, triesMax = 40, everyMs = 200) => {
      return new Promise((resolve) => {
        let tries = 0;
        const t = setInterval(() => {
          tries++;
          const ok = setStoreByIdOnUI(storeId);
          if (ok) {
            clearInterval(t);
            resolve(true);
            return;
          }
          if (tries >= triesMax) {
            clearInterval(t);
            resolve(false);
          }
        }, everyMs);
      });
    };

    const clearRecommendationsUI = (box) => {
      const row = box.querySelector(".js-nearStores-row");
      const link = box.querySelector(".js-nearStores-more");
      if (row) row.innerHTML = "";
      if (link) link.style.display = "none";
    };

    const renderRecommendationsUI = (box, items, mainStoreId, opts = {}) => {
      const row = box.querySelector(".js-nearStores-row");
      const link = box.querySelector(".js-nearStores-more");
      if (!row || !link) return;

      clearRecommendationsUI(box);

      const recs = (items || [])
        .map((x) => ({
          id: String(x.id ?? x.value ?? "").trim(),
          name: (x.name || x.label || "").trim(),
        }))
        .filter((x) => x.id && x.id !== String(mainStoreId))
        .slice(0, 5);

      if (!recs.length) return;

      link.style.display = "inline-flex";
      link.onclick = (e) => {
        e.preventDefault();
        console.log("[MG_QUOTE] click Ver más concesionarios (TODO modal)");
      };

      const onPickRecommendation = async (storeId, storeName) => {
        const ok = setStoreByIdOnUI(storeId) || (await waitForStoreOptionThenSelect(storeId));
        if (!ok) {
          ensureOption(storeUiEl, storeId, storeName || storeId);
          setStoreByIdOnUI(storeId);
        }

        const deptNow = String(deptEl?.value || "").trim();
        if (deptNow === "16") {
          try {
            const newItems = await fetchRecommendations({
              regionId: "16",
              mainStoreId: storeId,
            });
            renderRecommendationsUI(box, newItems, storeId, opts);
          } catch (err) {
            console.warn("[MG_QUOTE] Error refrescando recomendaciones:", err);
          }
        }
      };

      recs.forEach((it) => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "mg-nearStores__btn";
        b.textContent = it.name || "Concesionario";
        b.addEventListener("click", () => onPickRecommendation(it.id, it.name));
        row.appendChild(b);
      });
    };

    const fetchNearestStore = async (lat, lng) => {
      try {
        const r1 = await ajaxPost("mg_quote_get_nearest_store", { lat, lng });
        if (r1?.success && r1?.data?.item) return { mode: "new", item: r1.data.item };
      } catch { }

      const r2 = await ajaxPost("mg_quote_nearest_stores", { lat, lng });
      const items = r2?.success ? r2.data?.items || [] : [];
      if (!items.length) return { mode: "old", item: null, items: [] };
      return { mode: "old", item: items[0], items };
    };

    const fetchRecommendations = async ({ mode = "new", regionId, mainStoreId, fallbackItems }) => {
      if (String(regionId) !== "16") return [];

      if (mode === "new") {
        try {
          const r = await ajaxPost("mg_quote_get_store_recommendations", {
            regionId,
            tiendaMainId: mainStoreId, // IMPORTANTE
          });
          return r?.success ? (r.data?.items || []) : [];
        } catch {
          return [];
        }
      }

      return (fallbackItems || []).slice(1);
    };

    const applyGeoSelection = async (nearest) => {
      if (!nearest) return false;

      const storeId = String(nearest.id ?? nearest.value ?? "").trim();
      const storeName = String(nearest.name || nearest.label || "").trim();

      const regionIdRaw = nearest.regionId ?? nearest.RegionId ?? nearest.region_id ?? "";
      const regionId = String(regionIdRaw).trim();

      if (!storeId || !regionId) {
        console.warn("[MG_QUOTE] nearest sin storeId/regionId:", nearest);
        return false;
      }

      setSelectValue(deptEl, regionId);

      const ok = await waitForStoreOptionThenSelect(storeId);

      if (!ok) {
        ensureOption(storeUiEl, storeId, storeName || storeId);
        setStoreByIdOnUI(storeId);
      }

      return true;
    };

    const showGeoResultUI = async ({ mode, nearest, fallbackItems }) => {
      const box = ensureNearStoresBox();

      const geoWrap = form.querySelector(".geely-cotiza-geo");
      if (geoWrap) geoWrap.style.display = "none";

      box.style.display = "block";

      if (!nearest) {
        clearRecommendationsUI(box);
        return;
      }

      await applyGeoSelection(nearest);

      const regionId = String(nearest.regionId ?? nearest.RegionId ?? nearest.region_id ?? "").trim();
      const mainStoreId = String(nearest.id ?? nearest.value ?? "").trim();

      if (regionId !== "16") {
        clearRecommendationsUI(box);
        return;
      }

      const recs = await fetchRecommendations({ mode, regionId, mainStoreId, fallbackItems });
      renderRecommendationsUI(box, recs, mainStoreId, { regionId, mode });
    };

    const requestGeo = async () => {
      let state = "unsupported";
      try {
        if (navigator.permissions?.query) {
          const st = await navigator.permissions.query({ name: "geolocation" });
          state = st?.state || "unknown";
        }
      } catch {
        state = "error";
      }

      if (state === "denied") {
        openDeniedModal();
        return;
      }

      navigator.geolocation.getCurrentPosition(
        async (pos) => {
          const { latitude, longitude } = pos.coords;
          setGeoHidden(latitude, longitude);

          try {
            const r = await fetchNearestStore(latitude, longitude);
            if (r?.mode === "new") {
              await showGeoResultUI({ mode: "new", nearest: r.item, fallbackItems: [] });
            } else {
              await showGeoResultUI({ mode: "old", nearest: r.item, fallbackItems: r.items || [] });
            }
          } catch (e) {
            console.warn("[MG_QUOTE] Geo flow error:", e);
            openGeoErrorModal("Ocurrió un error", "No se pudo determinar el concesionario cercano. Intenta nuevamente.");
          }
        },
        (err) => {
          if (err?.code === 1) return openDeniedModal();

          if (err?.code === 2)
            return openGeoErrorModal(
              "No se pudo obtener tu ubicación",
              "Tu dispositivo/navegador no pudo determinar la ubicación. Verifica que la ubicación esté activada y vuelve a intentar."
            );

          if (err?.code === 3)
            return openGeoErrorModal(
              "Tiempo de espera agotado",
              "No se pudo obtener tu ubicación a tiempo. Intenta nuevamente."
            );

          openGeoErrorModal("No se pudo obtener tu ubicación", "Ocurrió un error inesperado. Intenta nuevamente.");
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
      );
    };

    geoBtn.addEventListener("click", (e) => {
      e.preventDefault();
      requestGeo();
    });

    /** NUEVO: Si cambia el combo TIENDA manualmente, refresca recomendaciones (solo Lima) */
    let __mgRecsDebounce = null;
    storeUiEl.addEventListener(
      "change",
      () => {
        // si el script está seteando la tienda (GEO / auto), no duplicar
        if (__mgStoreChangeInternal) return;

        const regionId = String(deptEl.value || "").trim();
        const mainStoreId = String(storeUiEl.value || "").trim();

        if (regionId !== "16" || !mainStoreId) return;

        // Debounce chiquito por si hay múltiples change seguidos
        clearTimeout(__mgRecsDebounce);
        __mgRecsDebounce = setTimeout(async () => {
          try {
            const box = ensureNearStoresBox();
            box.style.display = "block"; // si quieres que siempre se vea al cambiar tienda

            const recs = await fetchRecommendations({
              mode: "new",
              regionId: "16",
              mainStoreId,
              fallbackItems: [],
            });

            renderRecommendationsUI(box, recs, mainStoreId, { regionId: "16", mode: "new" });
          } catch (err) {
            console.warn("[MG_QUOTE] Error al refrescar recomendaciones por cambio de tienda:", err);
          }
        }, 150);
      },
      true
    );

    return true;
  };

  /** =========================
 *  Polyfill / helpers (para evitar que se rompa el JS)
 *  - Si CSS.escape no existe, lo creamos.
 * ========================= */
  if (typeof window.CSS === "undefined") window.CSS = {};
  if (typeof window.CSS.escape !== "function") {
    window.CSS.escape = (value) => {
      const s = String(value);
      // escape básico suficiente para option[value="..."]
      return s.replace(/["\\#.;?%&,+*~':!^$[\]()=>|/@]/g, "\\$&");
    };
  }

  /** =========================
   *  5) Bloque de cotización (COLORES + CONTINUAR)
   *  - NO se quita nada: solo se hace más robusto
   *  - FIX: click en radio de año no debe resetear selección por el click del card
   * ========================= */
  const initQuoteBlocks = () => {
    const roots = window.__MG_QUOTE_BLOCKS__ || [];
    if (!roots.length) return;

    const q = (root, sel) => root.querySelector(sel);
    const qa = (root, sel) => Array.from(root.querySelectorAll(sel));

    const fillCf7Hidden = (data) => {
      const setVal = (id, val) => setFieldById(id, val ?? "", true);

      setVal("product_id", data.product_id);
      setVal("product_title", data.product_title);
      setVal("model_slug", data.model_slug);
      setVal("model_name", data.model_name);
      setVal("model_year", data.model_year);
      setVal("model_price_usd", data.model_price_usd);
      setVal("model_price_local", data.model_price_local);
      setVal("color_name", data.color_name);
      setVal("color_hex", data.color_hex);
      setVal("nid_marca", data.nid_marca);
      setVal("nid_modelo", data.nid_modelo);

      setVal("co_articulo", data.co_articulo);
      setVal("co_configuracion", data.co_configuracion);
      setVal("co_transmision", data.co_transmision);
      setVal("gp_version", data.gp_version);
    };

    const applyLeftSummary = (root, data) => {
      const modelNameEl = q(root, "[data-selected-model-name]");
      const modelYearEl = q(root, "[data-selected-model-year]");
      const carImgEl = q(root, ".mg-quote__carImg");
      const colorDotEl = q(root, "[data-selected-color-dot]");
      const colorNameEl = q(root, "[data-selected-color-name]");

      if (modelNameEl) modelNameEl.textContent = data.model_name || "";
      if (modelYearEl) modelYearEl.textContent = data.model_year ? `${data.model_year}` : "";
      if (carImgEl && data.model_image) carImgEl.src = data.model_image;

      root.style.setProperty("--selected-color", data.color_hex || "#027bff");
      if (colorDotEl) colorDotEl.style.backgroundColor = data.color_hex || "#027bff";
      if (colorNameEl) colorNameEl.textContent = data.color_name || "";
    };

    const renderColorsStep1 = (root, colors, onPick) => {
      const dotsWrap = q(root, "[data-colors-dots]");
      const nameEl = q(root, "[data-colors-name]");
      if (!dotsWrap) return;

      dotsWrap.innerHTML = "";

      const arr = Array.isArray(colors) ? colors : [];
      if (!arr.length) {
        if (nameEl) nameEl.textContent = "";
        return;
      }

      arr.forEach((c, idx) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "mg-quote__colorDotBtn" + (idx === 0 ? " is-active" : "");
        btn.style.setProperty("--dot", c.hex || "#ccc");
        btn.setAttribute("aria-label", c.name || "Color");

        btn.addEventListener("click", () => {
          Array.from(dotsWrap.querySelectorAll(".mg-quote__colorDotBtn")).forEach((x) => x.classList.remove("is-active"));
          btn.classList.add("is-active");
          if (nameEl) nameEl.textContent = c.name || "";
          onPick(c);
        });

        dotsWrap.appendChild(btn);
      });

      if (nameEl) nameEl.textContent = arr[0]?.name || "";
    };

    const updateTabsUI = (root, step) => {
      const tabs = qa(root, ".mg-quote__tab");
      const current = step >= 2 ? 2 : 1;

      tabs.forEach((t) => {
        const tabStep = Number(t.getAttribute("data-step-tab") || "0");
        const isActive = tabStep === current;

        t.classList.toggle("is-active", isActive);
        t.setAttribute("aria-selected", isActive ? "true" : "false");
        t.setAttribute("tabindex", isActive ? "0" : "-1");

        if (tabStep === 2) {
          const canGo2 = !!root.__mgSelected;
          t.classList.toggle("is-disabled", !canGo2);
        } else {
          t.classList.remove("is-disabled");
        }
      });
    };

    const setStep = (root, step) => {
      root.setAttribute("data-step", String(step));

      qa(root, ".mg-quote__panel").forEach((p) => p.classList.remove("is-active"));
      const panel = q(root, `.mg-quote__panel[data-step="${step}"]`);
      if (panel) panel.classList.add("is-active");

      updateTabsUI(root, step);

      if (step === 2 && root.__mgSelected) {
        applyLeftSummary(root, root.__mgSelected);
        fillCf7Hidden(root.__mgSelected);
        setTimeout(() => waitAndMountCf7Features(), 0);
      }
    };

    const makeFirstOptionPlaceholder = (selectEl) => {
      if (!selectEl) return;
      const first = selectEl.querySelector("option");
      if (!first) return;

      const txt = (first.textContent || "").trim().toLowerCase();
      if (txt.includes("selecciona")) {
        first.value = "";
        first.disabled = true;
        first.hidden = true;
        first.selected = true;
      }
    };

    roots.forEach((sel) => {
      const root = document.querySelector(sel);
      if (!root) return;

      makeFirstOptionPlaceholder(root.querySelector('select[name="cot_department"]'));

      const productId = root.getAttribute("data-product-id") || "";
      const productTitle = q(root, ".mg-quote__productName")?.textContent?.trim() || "";

      const cards = qa(root, "[data-model-card]");
      const nextBtn = q(root, "[data-next-step]");
      const tabs = qa(root, ".mg-quote__tab");

      let selected = null;

      const pickColor = (colorObj) => {
        if (!selected) return;
        selected.color_name = colorObj.name || "";
        selected.color_hex = colorObj.hex || "#ccc";
        selected.model_image = colorObj.imgD || selected.model_image;

        root.__mgSelected = selected;
        applyLeftSummary(root, selected);
        fillCf7Hidden(selected);
        updateTabsUI(root, Number(root.getAttribute("data-step") || "1"));
      };

      const getSelectedYearFromCard = (cardEl) => {
        const checked = cardEl.querySelector('[data-year-radio]:checked');
        if (checked && checked.value) return checked.value;
        return cardEl.getAttribute("data-model-year") || "";
      };

      const selectCard = (cardEl, opts = {}) => {
        cards.forEach((c) => c.classList.remove("is-selected"));
        cardEl.classList.add("is-selected");

        const colors = (() => {
          try {
            return JSON.parse(cardEl.getAttribute("data-model-colors") || "[]");
          } catch {
            return [];
          }
        })();

        const firstColor = (Array.isArray(colors) ? colors : [])[0] || null;
        const cardYear = opts.forceYear || getSelectedYearFromCard(cardEl);

        selected = {
          product_id: productId,
          product_title: productTitle,

          model_slug: cardEl.getAttribute("data-model-slug") || "",
          model_name: cardEl.getAttribute("data-model-name") || "",
          model_year: cardYear || "",

          model_price_usd: cardEl.getAttribute("data-model-price-usd") || "",
          model_price_local: cardEl.getAttribute("data-model-price-local") || "",

          nid_marca: root.getAttribute("data-nid-marca") || "",
          nid_modelo: cardEl.getAttribute("data-nid-modelo") || "",

          co_articulo: cardEl.getAttribute("data-co-articulo") || "",
          co_configuracion: cardEl.getAttribute("data-co-configuracion") || "",
          co_transmision: cardEl.getAttribute("data-co-transmision") || "",
          gp_version: cardEl.getAttribute("data-gp-version") || cardEl.getAttribute("data-model-name") || "",

          model_image: firstColor?.imgD || cardEl.getAttribute("data-model-image") || "",
          color_name: firstColor?.name || "",
          color_hex: firstColor?.hex || "#ccc",
          colors: Array.isArray(colors) ? colors : [],
        };

        root.__mgSelected = selected;

        if (selected.colors && selected.colors.length) {
          renderColorsStep1(root, selected.colors, pickColor);
        }

        applyLeftSummary(root, selected);
        fillCf7Hidden(selected);
        updateTabsUI(root, Number(root.getAttribute("data-step") || "1"));
      };

      // Selección inicial
      if (cards[0]) selectCard(cards[0]);

      // FIX: si haces click en radio de año dentro del card, NO disparar selectCard por el click del card
      cards.forEach((cardEl) => {
        cardEl.addEventListener("click", (e) => {
          const inYear = e.target?.closest?.("[data-year-radio]");
          if (inYear) return;
          selectCard(cardEl);
        });
      });

      // Tabs
      tabs.forEach((btn) => {
        btn.addEventListener("click", () => {
          const tabStep = Number(btn.getAttribute("data-step-tab") || "1");
          if (tabStep === 1) setStep(root, 1);
          if (tabStep === 2 && root.__mgSelected) setStep(root, 2);
        });
      });

      // Botón "Continuar"
      if (nextBtn) {
        nextBtn.addEventListener("click", () => {
          if (!root.__mgSelected) return;
          fillCf7Hidden(root.__mgSelected);
          setStep(root, 2);
        });
      }

      // Paso inicial
      setStep(root, 1);

      // Cambio de año
      root.addEventListener("change", (e) => {
        const t = e.target;
        if (!(t instanceof HTMLInputElement)) return;
        if (!t.matches("[data-year-radio]")) return;

        const card = t.closest("[data-model-card]");
        if (!card) return;

        if (!card.classList.contains("is-selected")) {
          selectCard(card, { forceYear: t.value });
          return;
        }

        if (root.__mgSelected) {
          root.__mgSelected.model_year = t.value || "";
          applyLeftSummary(root, root.__mgSelected);
          fillCf7Hidden(root.__mgSelected);
        }
      });

      // Después de enviar CF7: ir a paso 3
      document.addEventListener("wpcf7mailsent", () => {
        const step2 = q(root, '.mg-quote__panel[data-step="2"]');
        if (!step2 || !step2.classList.contains("is-active")) return;
        setStep(root, 3);
      });
    });
  };

  /** =========================
   *  6) Validaciones + Botón disabled
   *  Store: valida select UI (#cot_store_ui)
   * ========================= */
  const initCotizaValidation = () => {
    const MAX_TRIES = 80;
    const TRY_EVERY = 200;

    const findForm = () => document.querySelector(".wpcf7 form");

    const boot = (form) => {
      if (form.__mgValidationMounted) return true;
      form.__mgValidationMounted = true;

      form.setAttribute("novalidate", "novalidate");

      let showErrors = false;

      const docTypeEl = form.querySelector('select[name="cot_document_type"]');
      const docEl = form.querySelector('input[name="cot_document"]');
      const namesEl = form.querySelector('input[name="cot_names"]');
      const lastnamesEl = form.querySelector('input[name="cot_lastnames"]');
      const phoneEl = form.querySelector('input[name="cot_phone"]');
      const emailEl = form.querySelector('input[name="cot_email"]');
      const deptEl = form.querySelector('select[name="cot_department"]');

      const storeUiEl = document.getElementById("cot_store_ui");
      const submitBtns = $$(".wpcf7-submit", form);

      if (!docTypeEl || !docEl || !submitBtns.length) {
        form.__mgValidationMounted = false;
        return false;
      }

      const NAME_ALLOWED = /[^a-zA-Z0-9&ÑñáéíóúÁÉÍÓÚ'\-\s]/g;
      const docType = () => (docTypeEl.value || "").toUpperCase().trim();

      const isNumericDocType = () => ["DNI", "RUC"].includes(docType());
      const getDocMaxLen = () => (docType() === "DNI" ? 8 : docType() === "RUC" ? 11 : 20);
      const getDocDisallowedRegex = () => (isNumericDocType() ? /[^0-9]/g : /[^a-zA-Z0-9&]/g);

      let _syncQueued = false;
      const scheduleSync = () => {
        if (_syncQueued) return;
        _syncQueued = true;

        Promise.resolve().then(() => {
          requestAnimationFrame(() => {
            _syncQueued = false;
            syncSubmitDisabled();
          });
        });

        setTimeout(() => syncSubmitDisabled(), 0);
        setTimeout(() => syncSubmitDisabled(), 50);
      };

      const ensureErrorEl = (fieldEl) => {
        const wrap = fieldEl?.closest(".geely-cotiza-row__control") || fieldEl?.parentElement;
        if (!wrap) return null;

        let err = wrap.querySelector(".geely-field-error");
        if (!err) {
          err = document.createElement("span");
          err.className = "geely-field-error";
          err.setAttribute("aria-live", "polite");
          wrap.appendChild(err);
        }
        return err;
      };

      const paintError = (fieldEl, message) => {
        if (!fieldEl) return;
        const errEl = ensureErrorEl(fieldEl);
        fieldEl.classList.toggle("is-invalid", !!message);
        fieldEl.setCustomValidity?.(message || "");
        if (errEl) errEl.textContent = message || "";
      };

      const setFieldError = (fieldEl, message, silent = false) => {
        if (silent) return !message;

        const hasValue = !!String(fieldEl?.value || "").trim();

        if (!showErrors) {
          if (!hasValue) {
            paintError(fieldEl, "");
            return !message;
          }
          if (message) {
            paintError(fieldEl, message);
            return false;
          }
          paintError(fieldEl, "");
          return true;
        }

        paintError(fieldEl, message);
        return !message;
      };

      const sanitizeDoc = () => {
        const maxLen = getDocMaxLen();
        docEl.setAttribute("maxlength", String(maxLen));
        const before = docEl.value || "";
        const disallowed = getDocDisallowedRegex();
        let v = before.replace(disallowed, "");
        if (v.length > maxLen) v = v.slice(0, maxLen);
        if (v !== before) docEl.value = v;
      };

      const validateDoc = (silent = false) => {
        const t = docType();
        const v = (docEl.value || "").trim();
        const maxLen = getDocMaxLen();

        if (!v) return setFieldError(docEl, "Ingresa tu número de documento.", silent);
        if ((t === "DNI" || t === "RUC") && !/^\d+$/.test(v)) return setFieldError(docEl, "Solo se permiten números.", silent);
        if (t === "DNI" && v.length !== 8) return setFieldError(docEl, "DNI debe tener 8 dígitos.", silent);
        if (t === "RUC" && v.length !== 11) return setFieldError(docEl, "RUC debe tener 11 dígitos.", silent);
        if (v.length > maxLen) return setFieldError(docEl, `Máximo ${maxLen} caracteres.`, silent);

        setFieldError(docEl, "", silent);
        return true;
      };

      const sanitizeNameField = (el) => {
        if (!el) return;
        const before = el.value || "";
        const v = before.replace(NAME_ALLOWED, "");
        if (v !== before) el.value = v;
      };

      const validateNameField = (el, label, silent = false) => {
        if (!el) return true;
        const v = (el.value || "").trim();
        if (!v) return setFieldError(el, `Ingresa ${label}.`, silent);
        setFieldError(el, "", silent);
        return true;
      };

      const sanitizePhone = () => {
        if (!phoneEl) return;
        const before = phoneEl.value || "";
        let v = before.replace(/\D/g, "");
        if (v.length > 9) v = v.slice(0, 9);
        if (v !== before) phoneEl.value = v;
        phoneEl.setAttribute("maxlength", "9");
        phoneEl.setAttribute("inputmode", "numeric");
        phoneEl.setAttribute("pattern", "^9\\d{8}$");
      };

      const validatePhone = (silent = false) => {
        if (!phoneEl) return true;
        const v = (phoneEl.value || "").trim();

        if (!v) return setFieldError(phoneEl, "Ingresa tu celular.", silent);
        if (!/^\d{9}$/.test(v)) return setFieldError(phoneEl, "Celular debe tener 9 dígitos.", silent);
        if (!/^9\d{8}$/.test(v)) return setFieldError(phoneEl, "Celular debe iniciar con 9.", silent);

        setFieldError(phoneEl, "", silent);
        return true;
      };

      const validateEmail = (silent = false) => {
        if (!emailEl) return true;
        const v = (emailEl.value || "").trim();
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
        if (!v) return setFieldError(emailEl, "Ingresa tu email.", silent);
        if (!emailOk) return setFieldError(emailEl, "Ingresa un email válido.", silent);
        setFieldError(emailEl, "", silent);
        return true;
      };

      const validateDept = (silent = false) => {
        if (!deptEl) return true;
        const v = (deptEl.value || "").trim();
        if (!v) return setFieldError(deptEl, "Selecciona un departamento.", silent);
        if (v.toLowerCase().includes("selecciona")) return setFieldError(deptEl, "Selecciona un departamento.", silent);
        setFieldError(deptEl, "", silent);
        return true;
      };

      const validateStore = (silent = false) => {
        if (!storeUiEl) return true;
        const v = (storeUiEl.value || "").trim();
        if (!v) return setFieldError(storeUiEl, "Selecciona una tienda.", silent);
        if (v.toLowerCase().includes("selecciona")) return setFieldError(storeUiEl, "Selecciona una tienda.", silent);
        setFieldError(storeUiEl, "", silent);
        return true;
      };

      const validateAcceptance = () => true;

      const isAllValidSilent = () => {
        sanitizeDoc();
        sanitizePhone();
        sanitizeNameField(namesEl);
        sanitizeNameField(lastnamesEl);

        const ok1 = validateNameField(namesEl, "tus nombres", true);
        const ok2 = validateNameField(lastnamesEl, "tus apellidos", true);
        const ok3 = validateDoc(true);
        const ok4 = validatePhone(true);
        const ok5 = validateEmail(true);
        const ok6 = validateDept(true);
        const ok7 = validateStore(true);
        const ok8 = validateAcceptance();

        return ok1 && ok2 && ok3 && ok4 && ok5 && ok6 && ok7 && ok8;
      };

      const forceEnableBtn = (btn) => {
        btn.disabled = false;
        btn.removeAttribute("disabled");
        btn.removeAttribute("aria-disabled");
        btn.classList.remove("is-disabled-by-mg", "disabled", "wpcf7-disabled");
        btn.style.pointerEvents = "";
        btn.style.opacity = "";
        btn.style.filter = "";
      };

      const forceDisableBtn = (btn) => {
        btn.disabled = true;
        btn.setAttribute("disabled", "disabled");
        btn.setAttribute("aria-disabled", "true");
        btn.classList.add("is-disabled-by-mg");
      };

      const syncSubmitDisabled = () => {
        const allOk = isAllValidSilent();
        submitBtns.forEach((btn) => (allOk ? forceEnableBtn(btn) : forceDisableBtn(btn)));
      };

      const validateAllCustom = () => {
        sanitizeDoc();
        sanitizePhone();
        sanitizeNameField(namesEl);
        sanitizeNameField(lastnamesEl);

        const ok1 = validateNameField(namesEl, "tus nombres", false);
        const ok2 = validateNameField(lastnamesEl, "tus apellidos", false);
        const ok3 = validateDoc(false);
        const ok4 = validatePhone(false);
        const ok5 = validateEmail(false);
        const ok6 = validateDept(false);
        const ok7 = validateStore(false);
        const ok8 = validateAcceptance();

        return ok1 && ok2 && ok3 && ok4 && ok5 && ok6 && ok7 && ok8;
      };

      form.__mgValidateAll = () => {
        showErrors = true;
        const ok = validateAllCustom();
        scheduleSync();
        return ok;
      };

      submitBtns.forEach((btn) => {
        const moBtn = new MutationObserver(() => scheduleSync());
        moBtn.observe(btn, { attributes: true, attributeFilter: ["disabled", "class", "aria-disabled", "style"] });
      });

      const moForm = new MutationObserver(() => scheduleSync());
      moForm.observe(form, { childList: true, subtree: true });

      ["wpcf7invalid", "wpcf7submit", "wpcf7reset", "wpcf7init"].forEach((evt) => {
        document.addEventListener(evt, () => scheduleSync(), true);
      });

      form.addEventListener(
        "input",
        (e) => {
          const t = e.target;

          if (t === docEl) {
            sanitizeDoc();
            validateDoc(false);
          }
          if (t === namesEl) {
            sanitizeNameField(namesEl);
            validateNameField(namesEl, "tus nombres", false);
          }
          if (t === lastnamesEl) {
            sanitizeNameField(lastnamesEl);
            validateNameField(lastnamesEl, "tus apellidos", false);
          }
          if (t === phoneEl) {
            sanitizePhone();
            validatePhone(false);
          }
          if (t === emailEl) {
            validateEmail(false);
          }

          scheduleSync();
        },
        true
      );

      form.addEventListener(
        "change",
        (e) => {
          const t = e.target;

          if (t === docTypeEl) {
            sanitizeDoc();
            validateDoc(false);
          }
          if (t === deptEl) {
            validateDept(false);
          }
          if (t === storeUiEl) {
            validateStore(false);
          }

          scheduleSync();
        },
        true
      );

      submitBtns.forEach((btn) => {
        btn.addEventListener(
          "click",
          (e) => {
            showErrors = true;
            const ok = validateAllCustom();
            scheduleSync();

            if (!ok) {
              e.preventDefault();
              e.stopPropagation();
              const first = form.querySelector(".is-invalid");
              first?.scrollIntoView({ behavior: "smooth", block: "center" });
              first?.focus?.();
            }
          },
          true
        );
      });

      sanitizeDoc();
      sanitizePhone();
      validatePhone(false);

      scheduleSync();
      setTimeout(scheduleSync, 200);
      setTimeout(scheduleSync, 800);

      return true;
    };

    let tries = 0;
    const timer = setInterval(() => {
      tries++;
      const form = findForm();
      if (form) {
        const ok = boot(form);
        if (ok) clearInterval(timer);
      }
      if (tries >= MAX_TRIES) {
        clearInterval(timer);
        console.warn("[MG_VALIDATE] No se pudo montar en el tiempo esperado.");
      }
    }, TRY_EVERY);
  };

  /** =========================
   *  7) Modal: Política de Datos
   * ========================= */
  const initDataPolicyModal = () => {
    const roots = window.__MG_QUOTE_BLOCKS__ || [];
    if (!roots.length) return;

    const open = (modal) => {
      if (!modal) return;
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
      document.documentElement.classList.add("mg-no-scroll");
    };

    const close = (modal) => {
      if (!modal) return;
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      document.documentElement.classList.remove("mg-no-scroll");
    };

    roots.forEach((sel) => {
      const root = document.querySelector(sel);
      if (!root) return;

      const modal = root.querySelector("[data-data-policy-modal]") || document.querySelector("[data-data-policy-modal]");
      if (!modal) return;

      modal.querySelectorAll("[data-policy-close]").forEach((btn) => {
        btn.addEventListener("click", () => close(modal));
      });

      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") close(modal);
      });

      root.addEventListener("click", (e) => {
        const a = e.target.closest(".mg-open-data-policy") || e.target.closest("[data-open-data-policy]");
        if (a) {
          e.preventDefault();
          open(modal);
        }
      });

      root.addEventListener("click", (e) => {
        const link = e.target.closest("a");
        if (!link) return;

        const txt = (link.textContent || "").toLowerCase();
        const looksLikePolicy = txt.includes("política") && (txt.includes("datos") || txt.includes("protección"));
        if (!looksLikePolicy) return;

        const href = (link.getAttribute("href") || "").trim();
        const isFake = href === "" || href === "#" || href.startsWith("javascript:");

        if (isFake) {
          e.preventDefault();
          open(modal);
        }
      });
    });
  };

  /** =========================
 *  8) GEO AUTO INIT (NUEVO)
 *  - Si el navegador YA tiene permiso
 *  - Ejecuta el flujo GEO automáticamente
 *  - NO pisa selección manual
 * ========================= */
  const initAutoGeoIfPermitted = () => {
    const form = document.querySelector(".wpcf7 form");
    if (!form) return;

    // evitar ejecutar más de una vez
    if (window.__mgAutoGeoExecuted) return;
    window.__mgAutoGeoExecuted = true;

    const deptEl = form.querySelector('select[name="cot_department"]');
    const storeUiEl = document.getElementById("cot_store_ui");

    // Si el usuario YA seleccionó algo, no auto-geo
    const hasDept = deptEl && deptEl.value;
    const hasStore = storeUiEl && storeUiEl.value;

    if (hasDept || hasStore) {
      console.log("[MG_GEO] AutoGeo skip: selección manual detectada");
      return;
    }

    if (!navigator.geolocation) return;

    // Verificamos estado del permiso
    if (!navigator.permissions?.query) {
      // fallback: intentamos igual
      tryAutoGeo();
      return;
    }

    navigator.permissions
      .query({ name: "geolocation" })
      .then((status) => {
        if (status.state === "granted") {
          console.log("[MG_GEO] Permiso concedido, ejecutando AutoGeo");
          tryAutoGeo();
        } else {
          console.log("[MG_GEO] Permiso no concedido:", status.state);
        }
      })
      .catch(() => {
        // fallback silencioso
        tryAutoGeo();
      });

    function tryAutoGeo() {
      navigator.geolocation.getCurrentPosition(
        async (pos) => {
          const { latitude, longitude } = pos.coords;

          // reutilizamos la lógica GEO ya existente
          if (typeof window.__mgLoadStoresByDept !== "function") {
            console.warn("[MG_GEO] loadStoresByDept no disponible aún");
          }

          // usamos el mismo flujo que el botón GEO
          const fakeClick = document.getElementById("geely-cotiza-geo-btn");
          if (fakeClick) {
            fakeClick.click();
            return;
          }

          // fallback directo (por si el botón no existe)
          try {
            const r = await ajaxPost("mg_quote_get_nearest_store", {
              lat: latitude,
              lng: longitude,
            });

            if (r?.success && r.data?.item) {
              const nearest = r.data.item;

              const regionId =
                nearest.regionId ??
                nearest.RegionId ??
                nearest.region_id ??
                "";

              const storeId =
                nearest.id ?? nearest.value ?? "";

              if (!regionId || !storeId) return;

              // set departamento (dispara mg_quote_get_stores)
              deptEl.value = String(regionId);
              deptEl.dispatchEvent(new Event("change", { bubbles: true }));

              // esperar tiendas y setear tienda
              let tries = 0;
              const t = setInterval(() => {
                tries++;
                const opt = storeUiEl?.querySelector(
                  `option[value="${CSS.escape(String(storeId))}"]`
                );
                if (opt) {
                  storeUiEl.value = String(storeId);
                  storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
                  storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));
                  clearInterval(t);
                }
                if (tries > 40) clearInterval(t);
              }, 200);
            }
          } catch (e) {
            console.warn("[MG_GEO] AutoGeo fallback error:", e);
          }
        },
        () => {
          // silencio: no molestamos al usuario
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
      );
    }
  };

  /** =========================
   *  CF7 mount (Paso 2)
   * ========================= */
  const mountCf7FeaturesWhenReady = () => {
    const form = document.querySelector(".wpcf7 form");
    if (!form) return false;

    if (form.__mgCf7FeaturesMounted) return true;
    form.__mgCf7FeaturesMounted = true;

    initDeptStoreDynamic();
    initGeo();
    initCotizaValidation();
    initAutoGeoIfPermitted();

    return true;
  };

  const waitAndMountCf7Features = () => {
    let tries = 0;
    const maxTries = 80;
    const timer = setInterval(() => {
      tries++;
      if (mountCf7FeaturesWhenReady() || tries >= maxTries) clearInterval(timer);
    }, 200);
  };

  /** =========================
   *  BOOT
   * ========================= */
  document.addEventListener("DOMContentLoaded", () => {
    fillTrackingHidden();
    initCf7Logs();
    initQuoteBlocks();
    initDataPolicyModal();
    waitAndMountCf7Features();
  });

  document.addEventListener("wpcf7init", () => {
    waitAndMountCf7Features();
  });
})();
