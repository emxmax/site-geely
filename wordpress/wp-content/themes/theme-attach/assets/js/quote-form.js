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
   *  GLOBALS (para evitar duplicados y compartir estado entre módulos)
   * ========================= */
  if (typeof window.__mgStoreChangeInternal !== "boolean") window.__mgStoreChangeInternal = false;
  if (typeof window.__mgLastRecommendationsKey !== "string") window.__mgLastRecommendationsKey = "";
  if (!window.__mgStoreCacheByDept) window.__mgStoreCacheByDept = {}; // deptId -> items[]

  /** =========================
   *  MODAL MAPA: Concesionarios recomendados
   * ========================= */
  const __mgNormalizeStore = (x) => {
    const id = String(x?.id ?? x?.value ?? "").trim();
    const name = String(x?.name ?? x?.label ?? "").trim();
    const address = String(x?.address ?? x?.direccion ?? x?.dir ?? "").trim();

    const latRaw = x?.lat ?? x?.latitude ?? x?.Latitud ?? x?.latitud;
    const lngRaw = x?.lng ?? x?.longitude ?? x?.Longitud ?? x?.longitud;

    const lat = latRaw !== undefined && latRaw !== null ? Number(latRaw) : null;
    const lng = lngRaw !== undefined && lngRaw !== null ? Number(lngRaw) : null;

    return {
      id,
      name,
      address,
      lat: Number.isFinite(lat) ? lat : null,
      lng: Number.isFinite(lng) ? lng : null,
      raw: x,
    };
  };

  const __mgEnsureStoresMapModal = () => {
    let modal = document.querySelector(".mg-storesMapModal");
    if (modal) return modal;

    modal = document.createElement("div");
    modal.className = "mg-storesMapModal";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = `
      <div class="mg-storesMapModal__backdrop" data-close="1"></div>

      <div class="mg-storesMapModal__card" role="dialog" aria-modal="true" aria-label="Concesionarios disponibles">
        <button type="button" class="mg-storesMapModal__close" aria-label="Cerrar" data-close="1">×</button>

        <div class="mg-storesMapModal__head">
          <div class="mg-storesMapModal__title">Concesionarios disponibles</div>
        </div>

        <div class="mg-storesMapModal__body">
          <div class="mg-storesMapModal__map" id="mgStoresMap"></div>
          <div class="mg-storesMapModal__list" style="display:none"></div>
        </div>

        <div class="mg-storesMapModal__foot">
          <button type="button" class="mg-storesMapModal__btn" data-close="1">Continuar</button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    const close = () => {
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      document.documentElement.classList.remove("mg-no-scroll");
    };

    modal.addEventListener("click", (e) => {
      const t = e.target;
      if (t?.getAttribute?.("data-close") === "1") close();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal.classList.contains("is-open")) close();
    });

    modal.__mgClose = close;
    return modal;
  };

  const __mgOpenStoresMapModal = ({ title = "Concesionarios disponibles", items = [] }) => {
    const modal = __mgEnsureStoresMapModal();

    const titleEl = modal.querySelector(".mg-storesMapModal__title");
    const mapEl = modal.querySelector("#mgStoresMap");
    const listEl = modal.querySelector(".mg-storesMapModal__list");

    if (titleEl) titleEl.textContent = title || "Concesionarios disponibles";

    // limpiar
    if (mapEl) mapEl.innerHTML = "";
    if (listEl) listEl.innerHTML = "";

    // normalizar + filtrar
    const stores = (items || [])
      .map(__mgNormalizeStore)
      .filter((s) => s.id || s.name);

    // abrir modal
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.documentElement.classList.add("mg-no-scroll");

    const hasGoogleMaps = !!(window.google && window.google.maps);
    const storesWithCoords = stores.filter((s) => Number.isFinite(s.lat) && Number.isFinite(s.lng));

    // fallback lista
    if (!hasGoogleMaps || !storesWithCoords.length) {
      if (mapEl) mapEl.style.display = "none";
      if (listEl) listEl.style.display = "block";

      if (listEl) {
        const ul = document.createElement("ul");
        ul.className = "mg-storesMapModal__ul";

        stores.forEach((s) => {
          const li = document.createElement("li");
          li.className = "mg-storesMapModal__li";

          const name = s.name || "Concesionario";
          const q = s.lat && s.lng ? `${s.lat},${s.lng}` : encodeURIComponent(`${name} ${s.address || ""}`);

          li.innerHTML = `
            <div class="mg-storesMapModal__liTitle">${name}</div>
            <div class="mg-storesMapModal__liAddr">${s.address || ""}</div>
            <a class="mg-storesMapModal__liLink" href="https://www.google.com/maps?q=${q}" target="_blank" rel="noopener">
              Ver en Google Maps
            </a>
          `;
          ul.appendChild(li);
        });

        listEl.appendChild(ul);
      }
      return;
    }

    // Maps OK
    if (mapEl) mapEl.style.display = "block";
    if (listEl) listEl.style.display = "none";

    setTimeout(() => {
      const map = new window.google.maps.Map(mapEl, {
        zoom: 12,
        center: { lat: storesWithCoords[0].lat, lng: storesWithCoords[0].lng },
        mapTypeControl: true,
        fullscreenControl: true,
        streetViewControl: false,
      });

      const bounds = new window.google.maps.LatLngBounds();
      const info = new window.google.maps.InfoWindow();

      storesWithCoords.forEach((s) => {
        const pos = { lat: s.lat, lng: s.lng };
        bounds.extend(pos);

        const marker = new window.google.maps.Marker({
          position: pos,
          map,
          title: s.name || "Concesionario",
        });

        marker.addListener("click", () => {
          const name = s.name || "Concesionario";
          const addr = s.address || "";
          const q = `${s.lat},${s.lng}`;

          info.setContent(`
            <div style="min-width:220px">
              <div style="font-weight:600;margin-bottom:6px">${name}</div>
              ${addr ? `<div style="margin-bottom:8px">${addr}</div>` : ""}
              <a href="https://www.google.com/maps?q=${q}" target="_blank" rel="noopener">Cómo llegar</a>
            </div>
          `);
          info.open({ map, anchor: marker });
        });
      });

      if (storesWithCoords.length > 1) map.fitBounds(bounds);
    }, 0);
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
    // =========================
    // 1) Antes de enviar: valida custom y bloquea submit si hay errores
    // =========================
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

    // =========================
    // 2) Submit (siempre): logs de respuesta + payload/api que venga del backend
    // =========================
    document.addEventListener("wpcf7submit", (e) => {
      const res = e.detail?.apiResponse;
      console.log("[MG_QUOTE] wpcf7submit apiResponse:", res);

      if (res?.mg_payload) console.log("[MG_QUOTE] PAYLOAD SENT TO API (body):", res.mg_payload);
      else console.warn("[MG_QUOTE] No llegó mg_payload. Revisa el filtro wpcf7_ajax_json_echo.");

      if (res?.mg_api) console.log("[MG_QUOTE] API RESULT (backend):", res.mg_api);
      else console.warn("[MG_QUOTE] No llegó mg_api. Revisa el filtro wpcf7_ajax_json_echo.");
    });

    // =========================
    // 3) Enviado OK: log + LIMPIEZA (cot_store_ui + recomendaciones)
    // =========================
    document.addEventListener("wpcf7mailsent", (e) => {
      console.log("[MG_QUOTE] wpcf7mailsent (OK)", e.detail?.apiResponse);

      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;

      console.log("[MG_QUOTE] mailsent -> limpiar tienda UI + recomendaciones");

      // Reset dedupe keys y marca cambio interno para no disparar refresh extra
      window.__mgLastRecommendationsKey = "";
      window.__mgStoreChangeInternal = true;

      // 1) Limpiar y ocultar "También contamos..."
      const nearBox = form.querySelector(".mg-nearStores");
      if (nearBox) {
        const row = nearBox.querySelector(".js-nearStores-row");
        const more = nearBox.querySelector(".js-nearStores-more");

        if (row) row.innerHTML = "";
        if (more) {
          more.style.display = "none";
          more.onclick = null;
        }

        nearBox.style.display = "none";
      }

      // Helpers para limpiar error visual del select
      const clearFieldError = (fieldEl) => {
        if (!fieldEl) return;
        fieldEl.classList.remove("is-invalid");
        fieldEl.setCustomValidity?.("");
        const wrap = fieldEl.closest(".geely-cotiza-row__control") || fieldEl.parentElement;
        const err = wrap?.querySelector(".geely-field-error");
        if (err) err.textContent = "";
      };

      // 2) Resetear Tienda UI (#cot_store_ui) A SOLO PLACEHOLDER (lo más confiable)
      const storeUiEl = document.getElementById("cot_store_ui");
      const storeHiddenEl = document.getElementById("cot_store"); // hidden real que envía CF7

      if (storeUiEl) {
        // Reemplaza TODAS las opciones y deja el placeholder seleccionado
        storeUiEl.innerHTML = "";
        const ph = document.createElement("option");
        ph.value = "";
        ph.textContent = "Selecciona una opción";
        ph.selected = true;
        ph.disabled = true;
        ph.hidden = true;
        storeUiEl.appendChild(ph);

        // Forzar value vacío
        storeUiEl.value = "";

        // Quitar error de "Selecciona una tienda."
        clearFieldError(storeUiEl);

        // Disparar eventos para que validación/botón se sincronicen
        storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
        storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));
      }

      // 3) Limpiar hidden store también
      if (storeHiddenEl) {
        storeHiddenEl.value = "";
        storeHiddenEl.dispatchEvent(new Event("change", { bubbles: true }));
        storeHiddenEl.dispatchEvent(new Event("input", { bubbles: true }));
      }

      // 4) (Opcional) mostrar "Habilitar ubicación actual" si la ocultaste en GEO
      const geoWrap = form.querySelector(".geely-cotiza-geo");
      if (geoWrap) geoWrap.style.display = "";

      // 5) Si tu validación usa scheduleSync, esto ayuda a re-evaluar disabled
      if (typeof form.__mgValidateAll === "function") {
        // no mostramos errores, solo fuerza recalcular estados si tu lógica lo hace
        // (si no hace nada, igual no afecta)
        try { form.__mgValidateAll(); } catch { }
      }

      // Liberar flag interno
      setTimeout(() => {
        window.__mgStoreChangeInternal = false;
      }, 0);
    });

    // =========================
    // 4) Envío fallido: log
    // =========================
    document.addEventListener("wpcf7mailfailed", (e) => {
      console.warn("[MG_QUOTE] wpcf7mailfailed", e.detail?.apiResponse);
    });

    // =========================
    // 5) Invalid: log
    // =========================
    document.addEventListener("wpcf7invalid", (e) => {
      console.warn("[MG_QUOTE] wpcf7invalid (validación)", e.detail?.apiResponse);
    });

    // =========================
    // 6) Click en botón: log del estado disabled/aria
    // =========================
    document.addEventListener("click", (e) => {
      const btn = e.target.closest('input.wpcf7-submit, button.wpcf7-submit, .geely-cotiza-submit');
      if (!btn) return;
      console.log("[MG_QUOTE] CLICK Cotizar", btn, { disabled: btn.disabled, aria: btn.getAttribute("aria-disabled") });
    });
  };

  /** =========================
   *  3) Carga dinámica: Tiendas por Departamento (ROBUSTO)
   *  - FIX duplicados recomendaciones
   *  - storeCacheByDept global
   *  - __mgStoreChangeInternal global
   * ========================= */
  const initDeptStoreDynamic = () => {
    if (window.__mgDeptStoreGlobalMounted) return true;
    window.__mgDeptStoreGlobalMounted = true;

    const STORE_UI_ID = "cot_store_ui";
    const STORE_HIDDEN_ID = "cot_store";
    const PLACEHOLDER_TEXT = "Selecciona una opción";

    const storeCacheByDept = window.__mgStoreCacheByDept; // GLOBAL

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

    const ensureNearStoresBox = (form) => {
      if (!form) return null;

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

        <div class="mg-nearStores__row js-nearStores-row"></div>

        <button type="button" class="mg-nearStores__link js-nearStores-more" style="display:none;">
          Ver más concesionarios
        </button>
      `;

      if (geoWrap && geoWrap.parentNode) geoWrap.parentNode.insertBefore(box, geoWrap.nextSibling);
      else form.appendChild(box);

      return box;
    };

    const openAllStoresModalForDept = (deptId) => {
      const { deptEl, storeUiEl } = getCtx();
      const dept = String(deptId || deptEl?.value || "").trim();
      if (!dept) return;

      const items = (storeCacheByDept[dept] || []).map(__mgNormalizeStore);

      // título: usa el texto del option del dept si existe
      const deptName =
        deptEl?.selectedOptions?.[0]?.textContent?.trim() ||
        (dept === "16" ? "LIMA" : "tu zona");

      // si quieres centrar en la tienda seleccionada primero
      const selectedId = String(storeUiEl?.value || "").trim();
      const mainFromCache = items.find((x) => String(x.id) === selectedId);
      const listForModal = mainFromCache
        ? [mainFromCache, ...items.filter((x) => String(x.id) !== selectedId)]
        : items;

      __mgOpenStoresMapModal({
        title: `Concesionarios disponibles en ${deptName}`,
        items: listForModal,
      });
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
      const { form, storeUiEl } = getCtx();
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

      link.style.display = "inline-flex";
      link.onclick = (e) => {
        e.preventDefault();
        openAllStoresModalForDept(opts?.deptId);
      };

      const onPickRecommendation = async (storeId, storeName) => {
        const ok = setStoreByIdOnUI(storeId) || (await waitForStoreOptionThenSelect(storeId));
        if (!ok) {
          ensureOption(storeUiEl, storeId, storeName || storeId);
          setStoreByIdOnUI(storeId);
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

      // IMPORTANTE: marcar como cambio interno
      window.__mgStoreChangeInternal = true;

      storeUiEl.value = "";

      if (storeHiddenEl) {
        storeHiddenEl.value = "";
        storeHiddenEl.dispatchEvent(new Event("input", { bubbles: true }));
        storeHiddenEl.dispatchEvent(new Event("change", { bubbles: true }));
      }

      storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
      storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));

      setTimeout(() => {
        window.__mgStoreChangeInternal = false;
      }, 0);
    };

    const fillStoreOptions = (items) => {
      const { storeUiEl } = getCtx();
      if (!storeUiEl) return;

      resetStoreToPlaceholder();

      (items || []).forEach((it) => {
        const opt = document.createElement("option");
        opt.value = String(it.id ?? it.value ?? "").trim();
        opt.textContent = it.label || it.name || "";
        if (!opt.value) return;
        storeUiEl.appendChild(opt);
      });
    };

    const setStoreByIdOnUI = (storeId) => {
      const { storeUiEl, storeHiddenEl } = getCtx();
      if (!storeUiEl || !storeId) return false;

      const v = String(storeId).trim();
      if (!v) return false;

      const opt = storeUiEl.querySelector(`option[value="${CSS.escape(v)}"]`);
      if (!opt) return false;

      // marcar cambio interno global
      window.__mgStoreChangeInternal = true;

      storeUiEl.value = v;
      storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
      storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));

      if (storeHiddenEl) {
        storeHiddenEl.value = v;
        storeHiddenEl.dispatchEvent(new Event("input", { bubbles: true }));
        storeHiddenEl.dispatchEvent(new Event("change", { bubbles: true }));
      }

      setTimeout(() => {
        window.__mgStoreChangeInternal = false;
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

    const fetchRecommendationsLima = async ({ regionId, mainStoreId }) => {
      try {
        const r = await ajaxPost("mg_quote_get_store_recommendations", {
          regionId,
          tiendaMainId: mainStoreId,
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

      // Guard anti-duplicado (dept|store)
      const key = `${deptId}|${storeId}`;
      if (window.__mgLastRecommendationsKey === key) {
        // console.log("[MG_QUOTE] refreshRecommendations SKIP duplicate:", key);
        return;
      }
      window.__mgLastRecommendationsKey = key;

      if (!deptId || !storeId) {
        clearRecommendationsUI();
        return;
      }

      if (deptId === "16") {
        const items = await fetchRecommendationsLima({ regionId: "16", mainStoreId: storeId });
        if (!items || !items.length) {
          clearRecommendationsUI();
          return;
        }
        renderRecommendationsUI(items, storeId, { mode: "lima-api", deptId });
        return;
      }

      const stores = storeCacheByDept[deptId] || [];
      const recs = stores.filter((x) => String(x.id ?? x.value ?? "").trim() !== storeId);

      if (!recs.length) {
        clearRecommendationsUI(); // si solo hay 1 tienda, NO se muestra
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

      ensureOption(storeUiEl, firstId, firstLabel);

      // marcar cambio interno global
      window.__mgStoreChangeInternal = true;

      storeUiEl.value = firstId;
      storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
      storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));

      if (storeHiddenEl) {
        storeHiddenEl.value = firstId;
        storeHiddenEl.dispatchEvent(new Event("input", { bubbles: true }));
        storeHiddenEl.dispatchEvent(new Event("change", { bubbles: true }));
      }

      setTimeout(() => {
        window.__mgStoreChangeInternal = false;
      }, 0);

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

      // reset dedupe key al cambiar dept
      window.__mgLastRecommendationsKey = "";

      if (!dept) {
        resetStoreToPlaceholder();
        clearRecommendationsUI();
        return;
      }

      resetStoreToPlaceholder();
      clearRecommendationsUI();
      setLoadingStores(storeUiEl, true);

      try {
        const res = await ajaxPost("mg_quote_get_stores", { department: dept });
        console.log("[MG_QUOTE] stores res:", res);

        const items = res?.success ? (res.data?.items || []) : [];
        storeCacheByDept[dept] = items; // cache global

        fillStoreOptions(items);

        const { form } = getCtx();
        const box = ensureNearStoresBox(form);
        if (box) {
          const more = box.querySelector(".js-nearStores-more");
          if (more) {
            more.style.display = items.length ? "inline-flex" : "none";
            more.onclick = (e) => {
              e.preventDefault();
              openAllStoresModalForDept(dept);
            };
          }
        }

        await autoSelectFirstStoreIfAny(dept, items);
      } catch (err) {
        console.warn("[MG_QUOTE] Error loading stores:", err);
        resetStoreToPlaceholder();
        clearRecommendationsUI();
      } finally {
        setLoadingStores(storeUiEl, false);
      }
    };

    Object.defineProperty(window, "__mgLoadStoresByDept", {
      value: loadStoresByDept,
      configurable: true,
    });

    // Dept change
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

    // Store change (único punto de refresh) + sync hidden
    document.addEventListener(
      "change",
      async (e) => {
        const t = e.target;
        if (!(t instanceof HTMLSelectElement)) return;
        if (t.id !== STORE_UI_ID) return;

        const v = (t.value || "").trim();
        const { storeHiddenEl, deptEl } = getCtx();

        // sync hidden siempre
        if (storeHiddenEl) {
          storeHiddenEl.value = v;
          storeHiddenEl.dispatchEvent(new Event("input", { bubbles: true }));
          storeHiddenEl.dispatchEvent(new Event("change", { bubbles: true }));
        }

        // Si el cambio lo hace el script (auto/geo), NO refrescamos para evitar duplicados.
        if (window.__mgStoreChangeInternal) return;

        const deptNow = String(deptEl?.value || "").trim();
        await refreshRecommendations({ dept: deptNow, selectedStoreId: v });
      },
      true
    );

    // init
    resetStoreToPlaceholder();
    clearRecommendationsUI();

    const { deptEl } = getCtx();
    if (deptEl?.value) loadStoresByDept(deptEl.value);

    return true;
  };

  /** =========================
   *  Polyfill / helpers
   * ========================= */
  if (typeof window.CSS === "undefined") window.CSS = {};
  if (typeof window.CSS.escape !== "function") {
    window.CSS.escape = (value) => {
      const s = String(value);
      return s.replace(/["\\#.;?%&,+*~':!^$[\]()=>|/@]/g, "\\$&");
    };
  }

  // ====== (La Parte 2/3 continúa con GEO + QuoteBlocks completo) ======
  /** =========================
   *  4) GEO + recomendación tienda cercana (CORREGIDO)
   *  - NO duplica recomendaciones (usa dedupe global)
   *  - NO agrega listener extra a tienda (ya lo hace DeptStoreDynamic)
   *  - comparte cache global window.__mgStoreCacheByDept
   *  - usa flag global window.__mgStoreChangeInternal
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

    const storeCacheByDept = window.__mgStoreCacheByDept; // GLOBAL

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

        <div class="mg-nearStores__row js-nearStores-row"></div>

        <button type="button" class="mg-nearStores__link js-nearStores-more" style="display:none;">
          Ver más concesionarios
        </button>
      `;

      if (geoWrap && geoWrap.parentNode) geoWrap.parentNode.insertBefore(box, geoWrap.nextSibling);
      else form.appendChild(box);

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

    const setStoreByIdOnUI = (storeId, storeName) => {
      if (!storeUiEl || !storeId) return false;

      const v = String(storeId).trim();
      if (!v) return false;

      const opt = storeUiEl.querySelector(`option[value="${CSS.escape(v)}"]`);
      if (!opt) {
        // si no existe aún, lo agregamos (fallback)
        ensureOption(storeUiEl, v, storeName || v);
      }

      // cambio interno global
      window.__mgStoreChangeInternal = true;

      storeUiEl.value = v;
      storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
      storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));

      setFieldById(storeHiddenId, v, true);

      setTimeout(() => {
        window.__mgStoreChangeInternal = false;
      }, 0);

      return true;
    };

    const waitForStoreOptionThenSelect = (storeId, storeName, triesMax = 40, everyMs = 200) => {
      return new Promise((resolve) => {
        let tries = 0;
        const t = setInterval(() => {
          tries++;
          const opt = storeUiEl.querySelector(`option[value="${CSS.escape(String(storeId))}"]`);
          if (opt) {
            clearInterval(t);
            setStoreByIdOnUI(storeId, storeName);
            resolve(true);
            return;
          }
          if (tries >= triesMax) {
            clearInterval(t);
            // fallback: agregar option y setear
            setStoreByIdOnUI(storeId, storeName);
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
          address: (x.address || x.direccion || x.dir || "").trim(),
          lat: x.lat ?? x.latitude ?? x.latitud ?? null,
          lng: x.lng ?? x.longitude ?? x.longitud ?? null,
        }))
        .filter((x) => x.id && x.id !== String(mainStoreId))
        .slice(0, 5);

      if (!recs.length) return;

      link.style.display = "inline-flex";
      link.onclick = (e) => {
        e.preventDefault();

        const regionId = String(opts?.regionId || "").trim();
        const mainId = String(mainStoreId || "").trim();

        const cached = (storeCacheByDept[regionId] || []).map(__mgNormalizeStore);
        const mainFromCache = cached.find((s) => String(s.id) === mainId);

        const mainFallback = __mgNormalizeStore({
          id: mainId,
          name: storeUiEl?.selectedOptions?.[0]?.textContent || "Tienda seleccionada",
        });

        const mainStore = mainFromCache || mainFallback;

        const recsAll = (items || [])
          .map(__mgNormalizeStore)
          .filter((x) => x.id && String(x.id) !== mainId)
          .slice(0, 50);

        __mgOpenStoresMapModal({
          title: `Concesionarios disponibles en ${regionId === "16" ? "LIMA" : "tu zona"}`,
          items: [mainStore, ...recsAll],
        });
      };

      const onPickRecommendation = async (storeId, storeName) => {
        await waitForStoreOptionThenSelect(storeId, storeName);
        // OJO: no refrescamos recomendaciones aquí. El refresh se hace en el listener ÚNICO del DeptStoreDynamic
        // y además está protegido por __mgStoreChangeInternal para no duplicar.
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
      // SOLO LIMA usa recommendations endpoint
      if (String(regionId) !== "16") return [];

      // dedupe global (region|store)
      const key = `${regionId}|${mainStoreId}`;
      if (window.__mgLastRecommendationsKey === key) return [];
      window.__mgLastRecommendationsKey = key;

      if (mode === "new") {
        try {
          const r = await ajaxPost("mg_quote_get_store_recommendations", {
            regionId,
            tiendaMainId: mainStoreId,
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

      // reset dedupe al cambiar dept
      window.__mgLastRecommendationsKey = "";

      // set dept (esto disparará mg_quote_get_stores desde DeptStoreDynamic)
      setSelectValue(deptEl, regionId);

      // esperar que carguen options y setear tienda
      await waitForStoreOptionThenSelect(storeId, storeName);

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

      // NO LIMA => no mostrar recomendaciones (la regla la maneja DeptStoreDynamic con cache)
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

    // IMPORTANTE:
    // Aquí YA NO ponemos listener de storeUiEl change, porque:
    // - DeptStoreDynamic ya hace sync y refresh
    // - y está protegido contra cambios internos

    return true;
  };

  // ====== (La Parte 3/3 continúa con Validaciones + PolicyModal + AutoGeo + mount + boot y cierre del IIFE) ======
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
   *  8) GEO AUTO INIT
   * ========================= */
  const initAutoGeoIfPermitted = () => {
    const form = document.querySelector(".wpcf7 form");
    if (!form) return;

    if (window.__mgAutoGeoExecuted) return;
    window.__mgAutoGeoExecuted = true;

    const deptEl = form.querySelector('select[name="cot_department"]');
    const storeUiEl = document.getElementById("cot_store_ui");

    const hasDept = deptEl && deptEl.value;
    const hasStore = storeUiEl && storeUiEl.value;

    if (hasDept || hasStore) {
      console.log("[MG_GEO] AutoGeo skip: selección manual detectada");
      return;
    }

    if (!navigator.geolocation) return;

    if (!navigator.permissions?.query) {
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
        tryAutoGeo();
      });

    function tryAutoGeo() {
      navigator.geolocation.getCurrentPosition(
        async (pos) => {
          const { latitude, longitude } = pos.coords;

          const fakeClick = document.getElementById("geely-cotiza-geo-btn");
          if (fakeClick) {
            fakeClick.click();
            return;
          }

          try {
            const r = await ajaxPost("mg_quote_get_nearest_store", {
              lat: latitude,
              lng: longitude,
            });

            if (r?.success && r.data?.item) {
              const nearest = r.data.item;

              const regionId = nearest.regionId ?? nearest.RegionId ?? nearest.region_id ?? "";
              const storeId = nearest.id ?? nearest.value ?? "";

              if (!regionId || !storeId || !deptEl || !storeUiEl) return;

              deptEl.value = String(regionId);
              deptEl.dispatchEvent(new Event("change", { bubbles: true }));

              let tries = 0;
              const t = setInterval(() => {
                tries++;
                const opt = storeUiEl?.querySelector(`option[value="${CSS.escape(String(storeId))}"]`);
                if (opt) {
                  window.__mgStoreChangeInternal = true;

                  storeUiEl.value = String(storeId);
                  storeUiEl.dispatchEvent(new Event("change", { bubbles: true }));
                  storeUiEl.dispatchEvent(new Event("input", { bubbles: true }));

                  setTimeout(() => {
                    window.__mgStoreChangeInternal = false;
                  }, 0);

                  clearInterval(t);
                }
                if (tries > 40) clearInterval(t);
              }, 200);
            }
          } catch (e) {
            console.warn("[MG_GEO] AutoGeo fallback error:", e);
          }
        },
        () => { },
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
    initDataPolicyModal();
    waitAndMountCf7Features();
  });

  document.addEventListener("wpcf7init", () => {
    waitAndMountCf7Features();
  });
})();
