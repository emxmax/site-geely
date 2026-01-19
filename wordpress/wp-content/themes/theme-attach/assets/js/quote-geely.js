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
      console.log("[MG_QUOTE] CLICK Cotizar", btn);
    });
  };

  /** =========================
   *  4) GEO + recomendación tienda cercana
   * ========================= */
  const initGeo = () => {
    const form = document.querySelector(".wpcf7 form");
    if (!form) return;

    const root = form.closest(".mg-quote") || document.querySelector(".mg-quote");

    const geoBtn = $("#geely-cotiza-geo-btn");
    const latId = "geely-cotiza-lat";
    const lngId = "geely-cotiza-lng";

    const deptEl = form.querySelector('select[name="cot_department"]');
    const storeEl = form.querySelector('select[name="cot_store"]');

    if (!geoBtn || !deptEl || !storeEl) return;

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
      <button type="button" class="mg-nearStores__btn"></button>
      <button type="button" class="mg-nearStores__link">
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

    const setGeoHidden = (lat, lng) => {
      setFieldById(latId, String(lat), true);
      setFieldById(lngId, String(lng), true);
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

    const setRecommendedStore = async (storeItem) => {
      const raw = String(storeItem?.value || "");
      if (!raw) return;

      const [id, name] = raw.includes("|") ? raw.split("|") : [raw, storeItem?.name || ""];
      const storeId = (id || "").trim();
      const storeName = (name || storeItem?.name || storeItem?.label || "").trim();

      if (!storeId) return;

      setSelectValue(form.querySelector('select[name="cot_store"]'), storeId, storeName);
    };

    const showNearestStores = async (lat, lng) => {
      const box = ensureNearStoresBox();
      const btn = box.querySelector(".mg-nearStores__btn");
      const link = box.querySelector(".mg-nearStores__link");

      const geoWrap = form.querySelector(".geely-cotiza-geo");
      if (geoWrap) geoWrap.style.display = "none";

      box.style.display = "block";
      btn.textContent = "Buscando concesionario cercano...";
      btn.disabled = true;

      try {
        const res = await ajaxPost("mg_quote_nearest_stores", { lat, lng });
        const items = res?.success ? (res.data?.items || []) : [];

        if (!items.length) {
          btn.textContent = "No se encontraron concesionarios cercanos.";
          btn.disabled = true;
          link.style.display = "none";
          return;
        }

        const nearest = items[0];
        btn.disabled = false;
        btn.textContent = nearest.name || "Concesionario recomendado";
        btn.onclick = () => setRecommendedStore(nearest);

        link.style.display = items.length > 1 ? "block" : "none";
        link.onclick = () => {
          let list = box.querySelector(".mg-nearStores__list");
          if (list) {
            list.remove();
            return;
          }
          list = document.createElement("div");
          list.className = "mg-nearStores__list";
          list.style.marginTop = "10px";

          items.slice(1).forEach((it) => {
            const b = document.createElement("button");
            b.type = "button";
            b.className = "mg-nearStores__btn";
            b.style.marginRight = "8px";
            b.style.marginBottom = "8px";
            b.textContent = it.name || "";
            b.addEventListener("click", () => setRecommendedStore(it));
            list.appendChild(b);
          });

          box.appendChild(list);
        };
      } catch (err) {
        console.warn("[MG_QUOTE] nearest stores error:", err);
        btn.textContent = "Ocurrió un error obteniendo concesionarios.";
        btn.disabled = true;
        link.style.display = "none";
      }
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
          await showNearestStores(latitude, longitude);
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
  };

  /** =========================
   *  5) Bloque de cotización (tabs/steps/selección modelos/colores)
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

      colors.forEach((c, idx) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "mg-quote__colorDotBtn" + (idx === 0 ? " is-active" : "");
        btn.style.setProperty("--dot", c.hex || "#ccc");
        btn.setAttribute("aria-label", c.name || "Color");

        btn.addEventListener("click", () => {
          Array.from(dotsWrap.querySelectorAll(".mg-quote__colorDotBtn")).forEach((x) =>
            x.classList.remove("is-active")
          );
          btn.classList.add("is-active");
          if (nameEl) nameEl.textContent = c.name || "";
          onPick(c);
        });

        dotsWrap.appendChild(btn);
      });

      if (nameEl) nameEl.textContent = colors[0]?.name || "";
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

      // Mantiene placeholder como "no seleccionable"
      makeFirstOptionPlaceholder(root.querySelector('select[name="cot_department"]'));
      makeFirstOptionPlaceholder(root.querySelector('select[name="cot_store"]'));

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

      const getSelectedYearFromCard = (cardBtn) => {
        const checked = cardBtn.querySelector('[data-year-radio]:checked');
        if (checked && checked.value) return checked.value;
        return cardBtn.getAttribute("data-model-year") || "";
      };

      const selectCard = (btn, opts = {}) => {
        cards.forEach((c) => c.classList.remove("is-selected"));
        btn.classList.add("is-selected");

        const colors = (() => {
          try {
            return JSON.parse(btn.getAttribute("data-model-colors") || "[]");
          } catch {
            return [];
          }
        })();

        const firstColor = colors[0] || null;
        const cardYear = opts.forceYear || getSelectedYearFromCard(btn);

        selected = {
          product_id: productId,
          product_title: productTitle,

          model_slug: btn.getAttribute("data-model-slug") || "",
          model_name: btn.getAttribute("data-model-name") || "",
          model_year: cardYear || "",

          model_price_usd: btn.getAttribute("data-model-price-usd") || "",
          model_price_local: btn.getAttribute("data-model-price-local") || "",

          nid_marca: root.getAttribute("data-nid-marca") || "",
          nid_modelo: btn.getAttribute("data-nid-modelo") || "",

          co_articulo: btn.getAttribute("data-co-articulo") || "",
          co_configuracion: btn.getAttribute("data-co-configuracion") || "",
          co_transmision: btn.getAttribute("data-co-transmision") || "",
          gp_version: btn.getAttribute("data-gp-version") || (btn.getAttribute("data-model-name") || ""),

          model_image: firstColor?.imgD || btn.getAttribute("data-model-image") || "",
          color_name: firstColor?.name || "",
          color_hex: firstColor?.hex || "#ccc",
          colors,
        };

        root.__mgSelected = selected;

        if (selected.colors && selected.colors.length) renderColorsStep1(root, selected.colors, pickColor);

        applyLeftSummary(root, selected);
        fillCf7Hidden(selected);
        updateTabsUI(root, Number(root.getAttribute("data-step") || "1"));
      };

      if (cards[0]) selectCard(cards[0]);
      cards.forEach((btn) => btn.addEventListener("click", () => selectCard(btn)));

      tabs.forEach((btn) => {
        btn.addEventListener("click", () => {
          const tabStep = Number(btn.getAttribute("data-step-tab") || "1");
          if (tabStep === 1) setStep(root, 1);
          if (tabStep === 2 && root.__mgSelected) setStep(root, 2);
        });
      });

      if (nextBtn) {
        nextBtn.addEventListener("click", () => {
          if (root.__mgSelected) fillCf7Hidden(root.__mgSelected);
          setStep(root, 2);
        });
      }

      setStep(root, 1);

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

      document.addEventListener("wpcf7mailsent", () => {
        const step2 = q(root, '.mg-quote__panel[data-step="2"]');
        if (!step2 || !step2.classList.contains("is-active")) return;
        setStep(root, 3);
      });
    });
  };

  /** =========================
   *  6) Validaciones + Botón disabled mientras falte algo
   * ========================= */
  const initCotizaValidation = () => {
    const MAX_TRIES = 60;
    const TRY_EVERY = 250;

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
      const storeEl = form.querySelector('select[name="cot_store"]');
      const submitBtn = form.querySelector(".wpcf7-submit");

      // Acceptance (si existe) -> para bloquear Cotizar hasta que acepte.
      // CHANGE: lo incluimos en el "disabled" general.
      const acceptanceChk =
        form.querySelector('.wpcf7-acceptance input[type="checkbox"]') ||
        form.querySelector('input[type="checkbox"][name*="accept"]');

      if (!docTypeEl || !docEl || !submitBtn) {
        form.__mgValidationMounted = false;
        return false;
      }

      const NAME_ALLOWED = /[^a-zA-Z0-9&ÑñáéíóúÁÉÍÓÚ'\-\s]/g;
      const docType = () => (docTypeEl.value || "").toUpperCase().trim();

      const isNumericDocType = () => ["DNI", "RUC"].includes(docType());
      const getDocMaxLen = () => (docType() === "DNI" ? 8 : docType() === "RUC" ? 11 : 20);
      const getDocDisallowedRegex = () => (isNumericDocType() ? /[^0-9]/g : /[^a-zA-Z0-9&]/g);

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
        fieldEl.setCustomValidity(message || "");
        if (errEl) errEl.textContent = message || "";
      };

      // CHANGE: ahora setFieldError soporta modo silencioso (no pinta),
      // para poder deshabilitar el botón sin ensuciar la UI mientras escribe.
      const setFieldError = (fieldEl, message, silent = false) => {
        if (silent) return !!message ? false : true;
        if (!showErrors) {
          if (!message) paintError(fieldEl, "");
          return !!message ? false : true;
        }
        paintError(fieldEl, message);
        return !!message ? false : true;
      };

      const clearFieldError = (fieldEl) => paintError(fieldEl, "");

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

      // CHANGE: Celular SOLO 9 dígitos y debe empezar con 9 (sin +51)
      const sanitizePhone = () => {
        if (!phoneEl) return;
        const before = phoneEl.value || "";
        let v = before.replace(/\D/g, ""); // solo dígitos
        if (v.length > 9) v = v.slice(0, 9);
        if (v !== before) phoneEl.value = v;
        phoneEl.setAttribute("maxlength", "9");
        phoneEl.setAttribute("inputmode", "numeric");
      };

      // CHANGE: regla nueva del celular
      const validatePhone = (silent = false) => {
        if (!phoneEl) return true;
        const v = (phoneEl.value || "").trim();
        if (!v) return setFieldError(phoneEl, "Ingresa tu celular.", silent);
        if (!/^\d{9}$/.test(v)) return setFieldError(phoneEl, "Celular debe tener 9 dígitos.", silent);
        if (!v.startsWith("9")) return setFieldError(phoneEl, "Celular debe iniciar con 9.", silent);
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
        if (!storeEl) return true;
        const v = (storeEl.value || "").trim();
        if (!v) return setFieldError(storeEl, "Selecciona una tienda.", silent);
        if (v.toLowerCase().includes("selecciona")) return setFieldError(storeEl, "Selecciona una tienda.", silent);
        setFieldError(storeEl, "", silent);
        return true;
      };

      const validateAcceptance = (silent = false) => {
        if (!acceptanceChk) return true;
        const ok = !!acceptanceChk.checked;
        if (!ok) return silent ? false : false; // no pintamos error, CF7 ya lo hace
        return true;
      };

      // CHANGE: valida en modo silencioso para controlar el disabled del botón
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
        const ok8 = validateAcceptance(true);

        return ok1 && ok2 && ok3 && ok4 && ok5 && ok6 && ok7 && ok8;
      };

      // CHANGE: deshabilita/ habilita visualmente el botón
      const syncSubmitDisabled = () => {
        if (!submitBtn) return;

        const allOk = isAllValidSilent();

        // Solo forzamos "disabled=true" cuando NO está ok.
        // Cuando está ok, NO forzamos enabled, porque CF7 podría tenerlo disabled (acceptance u otra regla).
        if (!allOk) {
          submitBtn.disabled = true;
          submitBtn.setAttribute("aria-disabled", "true");
          submitBtn.classList.add("is-disabled-by-mg");
        } else {
          // Si CF7 lo tiene disabled por aceptación u otra cosa, lo respetamos.
          // Solo quitamos nuestras marcas visuales.
          submitBtn.classList.remove("is-disabled-by-mg");
          submitBtn.removeAttribute("aria-disabled");
        }
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
        const ok8 = validateAcceptance(false);

        return ok1 && ok2 && ok3 && ok4 && ok5 && ok6 && ok7 && ok8;
      };

      form.__mgValidateAll = () => {
        showErrors = true;
        const ok = validateAllCustom();
        syncSubmitDisabled(); // CHANGE: refresca disabled luego de mostrar errores
        return ok;
      };

      // Eventos (input/change) + refresco del disabled
      docEl.addEventListener("input", () => {
        sanitizeDoc();
        if (showErrors) validateDoc(false);
        else clearFieldError(docEl);
        syncSubmitDisabled(); // CHANGE
      });

      docTypeEl.addEventListener("change", () => {
        sanitizeDoc();
        if (showErrors) validateDoc(false);
        syncSubmitDisabled(); // CHANGE
      });

      namesEl?.addEventListener("input", () => {
        sanitizeNameField(namesEl);
        if (showErrors) validateNameField(namesEl, "tus nombres", false);
        else clearFieldError(namesEl);
        syncSubmitDisabled(); // CHANGE
      });

      lastnamesEl?.addEventListener("input", () => {
        sanitizeNameField(lastnamesEl);
        if (showErrors) validateNameField(lastnamesEl, "tus apellidos", false);
        else clearFieldError(lastnamesEl);
        syncSubmitDisabled(); // CHANGE
      });

      phoneEl?.addEventListener("input", () => {
        sanitizePhone();
        if (showErrors) validatePhone(false);
        else clearFieldError(phoneEl);
        syncSubmitDisabled(); // CHANGE
      });

      emailEl?.addEventListener("input", () => {
        if (showErrors) validateEmail(false);
        else clearFieldError(emailEl);
        syncSubmitDisabled(); // CHANGE
      });

      deptEl?.addEventListener("change", () => {
        if (showErrors) validateDept(false);
        else clearFieldError(deptEl);
        syncSubmitDisabled(); // CHANGE
      });

      storeEl?.addEventListener("change", () => {
        if (showErrors) validateStore(false);
        else clearFieldError(storeEl);
        syncSubmitDisabled(); // CHANGE
      });

      acceptanceChk?.addEventListener("change", () => {
        syncSubmitDisabled(); // CHANGE
      });

      // Click en submit: si algo falla, bloquea y muestra el primer error
      submitBtn.addEventListener(
        "click",
        (e) => {
          showErrors = true;
          const ok = validateAllCustom();
          syncSubmitDisabled(); // CHANGE

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

      // Inicial
      sanitizeDoc();
      sanitizePhone();
      syncSubmitDisabled(); // CHANGE

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
   *  BOOT
   * ========================= */
  document.addEventListener("DOMContentLoaded", () => {
    fillTrackingHidden();
    initCf7Logs();
    initQuoteBlocks();
    initGeo();
    initDataPolicyModal();
    initCotizaValidation();
  });
})();
