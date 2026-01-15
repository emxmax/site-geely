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
   *  2) CF7 logs (FIX: no usa "form" undefined)
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
   *  3) Carga dinámica: Tiendas por Departamento
   * ========================= */
  const initDeptStoreDynamic = () => {
    const form = document.querySelector(".wpcf7 form");
    if (!form) return;

    const deptEl = form.querySelector('select[name="cot_department"]');
    const storeEl = form.querySelector('select[name="cot_store"]');
    if (!deptEl || !storeEl) return;

    const setLoadingStores = (loading) => {
      storeEl.disabled = !!loading;
      storeEl.classList.toggle("is-loading", !!loading);
    };

    const fillStoreOptions = (items) => {
      const placeholderText = "Selecciona una opción";
      storeEl.innerHTML = "";
      const ph = document.createElement("option");
      ph.value = "";
      ph.textContent = placeholderText;
      ph.disabled = true;
      ph.hidden = true;
      ph.selected = true;
      storeEl.appendChild(ph);

      (items || []).forEach((it) => {
        const opt = document.createElement("option");
        // OJO: aquí usas el value que te devuelve el endpoint (id|name)
        opt.value = it.value || "";
        opt.textContent = it.label || it.name || "";
        storeEl.appendChild(opt);
      });
    };

    const loadStoresByDept = async (deptValue) => {
      if (!deptValue) {
        fillStoreOptions([]);
        return;
      }

      setLoadingStores(true);
      try {
        const res = await ajaxPost("mg_quote_get_stores", { department: deptValue });
        const items = res?.success ? (res.data?.items || []) : [];
        fillStoreOptions(items);
      } catch (err) {
        console.warn("[MG_QUOTE] Error loading stores:", err);
        fillStoreOptions([]);
      } finally {
        setLoadingStores(false);
      }
    };

    // expone función por si luego quieres usarla
    form.__mgLoadStoresByDept = loadStoresByDept;

    deptEl.addEventListener("change", () => {
      loadStoresByDept(deptEl.value || "");
    });
  };

  /** =========================
   *  4) GEO + recomendación tienda cercana
   * ========================= */
  const initGeo = () => {
    const form = document.querySelector(".wpcf7 form");
    if (!form) return;

    const geoBtn = $("#geely-cotiza-geo-btn");
    const latId = "geely-cotiza-lat";
    const lngId = "geely-cotiza-lng";

    const deptEl = form.querySelector('select[name="cot_department"]');
    const storeEl = form.querySelector('select[name="cot_store"]');

    if (!geoBtn || !deptEl || !storeEl) return;

    const ensureDeniedModal = () => {
      let modal = document.querySelector(".mg-geoModal");
      if (modal) return modal;

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

    // helpers para setear tienda en el select sin depender del departamento
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

    // IMPORTANTE: aquí solo seteamos la TIENDA
    // porque el usuario aún no ha elegido dept/tienda
    const setRecommendedStore = async (storeItem) => {
      // storeItem viene del backend con:
      // { id, name, department, distance_km, value: "id|name", label: name }
      const raw = String(storeItem?.value || "");
      if (!raw) return;

      const [id, name] = raw.includes("|") ? raw.split("|") : [raw, storeItem?.name || ""];
      const storeId = (id || "").trim();
      const storeName = (name || storeItem?.name || storeItem?.label || "").trim();

      if (!storeId) return;

      // como tu select principal maneja value=ID, seteamos ID
      setSelectValue(storeEl, storeId, storeName);

      // (OPCIONAL) si quieres setear dept SOLO si está vacío:
      // if (storeItem?.department && !deptEl.value) {
      //   deptEl.value = String(storeItem.department);
      //   deptEl.dispatchEvent(new Event("change", { bubbles: true }));
      // }
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
      console.clear();
      console.log("[MG_GEO] location.origin:", location.origin);
      console.log("[MG_GEO] location.href:", location.href);

      const hasGeo = !!navigator.geolocation;
      console.log("[MG_GEO] navigator.geolocation exists:", hasGeo);

      let state = "unsupported";
      try {
        if (navigator.permissions?.query) {
          const st = await navigator.permissions.query({ name: "geolocation" });
          state = st?.state || "unknown";
          console.log("[MG_GEO] permissions.query state:", state);
          st.onchange = () => console.log("[MG_GEO] permissions state changed ->", st.state);
        } else {
          console.log("[MG_GEO] Permissions API not available");
        }
      } catch (e) {
        console.warn("[MG_GEO] permissions.query error:", e);
        state = "error";
      }

      if (state === "denied") {
        console.warn("[MG_GEO] Blocked by browser -> showing denied modal");
        openDeniedModal();
        return;
      }

      console.log("[MG_GEO] Calling getCurrentPosition...");
      navigator.geolocation.getCurrentPosition(
        async (pos) => {
          console.log("[MG_GEO] SUCCESS coords:", pos.coords);
          const { latitude, longitude } = pos.coords;
          setGeoHidden(latitude, longitude);
          await showNearestStores(latitude, longitude);
        },
        (err) => {
          console.warn("[MG_GEO] ERROR:", err, "code:", err?.code, "message:", err?.message);

          if (err?.code === 1) {
            openDeniedModal();
            return;
          }

          if (err?.code === 2) {
            openGeoErrorModal(
              "No se pudo obtener tu ubicación",
              "Tu dispositivo/navegador no pudo determinar la ubicación. Verifica que la ubicación esté activada en tu sistema y vuelve a intentar."
            );
            return;
          }

          if (err?.code === 3) {
            openGeoErrorModal(
              "Tiempo de espera agotado",
              "No se pudo obtener tu ubicación a tiempo. Intenta nuevamente o prueba con otra red."
            );
            return;
          }

          openGeoErrorModal(
            "No se pudo obtener tu ubicación",
            "Ocurrió un error inesperado al obtener la ubicación. Intenta nuevamente."
          );
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

        if (selected.colors && selected.colors.length) {
          renderColorsStep1(root, selected.colors, pickColor);
        }

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
   *  6) Validaciones (primer click + en vivo)
   * ========================= */
  const initCotizaValidation = () => {
    const MAX_TRIES = 60;
    const TRY_EVERY = 250;

    const findForm = () => document.querySelector(".wpcf7 form");
    const findActionsWrap = (form) => form?.querySelector(".geely-cotiza-actions") || null;

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

      if (!docTypeEl || !docEl) {
        console.warn("[MG_VALIDATE] No encontró docType/doc input aún.");
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

      const setFieldError = (fieldEl, message) => {
        if (!showErrors) {
          if (!message) paintError(fieldEl, "");
          return;
        }
        paintError(fieldEl, message);
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

      const validateDoc = () => {
        const t = docType();
        const v = (docEl.value || "").trim();
        const maxLen = getDocMaxLen();

        if (!v) return (setFieldError(docEl, "Ingresa tu número de documento."), false);
        if ((t === "DNI" || t === "RUC") && !/^\d+$/.test(v)) return (setFieldError(docEl, "Solo se permiten números."), false);
        if (t === "DNI" && v.length !== 8) return (setFieldError(docEl, "DNI debe tener 8 dígitos."), false);
        if (t === "RUC" && v.length !== 11) return (setFieldError(docEl, "RUC debe tener 11 dígitos."), false);
        if (v.length > maxLen) return (setFieldError(docEl, `Máximo ${maxLen} caracteres.`), false);

        setFieldError(docEl, "");
        return true;
      };

      const sanitizeNameField = (el) => {
        if (!el) return;
        const before = el.value || "";
        const v = before.replace(NAME_ALLOWED, "");
        if (v !== before) el.value = v;
      };

      const validateNameField = (el, label) => {
        if (!el) return true;
        const v = (el.value || "").trim();
        if (!v) return (setFieldError(el, `Ingresa ${label}.`), false);
        setFieldError(el, "");
        return true;
      };

      const sanitizePhone = () => {
        if (!phoneEl) return;
        const before = phoneEl.value || "";
        let v = before.replace(/[^\d+]/g, "");
        if (v.includes("+")) {
          v = v.replace(/\+/g, "");
          v = "+" + v;
        }
        if (/^51\d/.test(v)) v = "+" + v;
        if (v.length > 13) v = v.slice(0, 13);
        if (v !== before) phoneEl.value = v;
      };

      const validatePhone = () => {
        if (!phoneEl) return true;
        const v = (phoneEl.value || "").trim();
        if (!v) return (setFieldError(phoneEl, "Ingresa tu celular."), false);
        if (!/^\+51\d{9}$/.test(v)) return (setFieldError(phoneEl, "Celular precisa del +51 y 9 numeros."), false);
        setFieldError(phoneEl, "");
        return true;
      };

      const validateEmail = () => {
        if (!emailEl) return true;
        const v = (emailEl.value || "").trim();
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
        if (!v) return (setFieldError(emailEl, "Ingresa tu email."), false);
        if (!emailOk) return (setFieldError(emailEl, "Ingresa un email válido."), false);
        setFieldError(emailEl, "");
        return true;
      };

      const validateDept = () => {
        if (!deptEl) return true;
        const v = (deptEl.value || "").trim();
        if (!v || v.toLowerCase().includes("selecciona")) return (setFieldError(deptEl, "Selecciona un departamento."), false);
        setFieldError(deptEl, "");
        return true;
      };

      const validateStore = () => {
        if (!storeEl) return true;
        const v = (storeEl.value || "").trim();
        if (!v || v.toLowerCase().includes("selecciona")) return (setFieldError(storeEl, "Selecciona una tienda."), false);
        setFieldError(storeEl, "");
        return true;
      };

      const validateAllCustom = () => {
        sanitizeDoc();
        sanitizePhone();
        sanitizeNameField(namesEl);
        sanitizeNameField(lastnamesEl);

        const ok1 = validateNameField(namesEl, "tus nombres");
        const ok2 = validateNameField(lastnamesEl, "tus apellidos");
        const ok3 = validateDoc();
        const ok4 = validatePhone();
        const ok5 = validateEmail();
        const ok6 = validateDept();
        const ok7 = validateStore();

        return ok1 && ok2 && ok3 && ok4 && ok5 && ok6 && ok7;
      };

      form.__mgValidateAll = () => {
        showErrors = true;
        return validateAllCustom();
      };

      docEl.addEventListener("input", () => {
        sanitizeDoc();
        if (showErrors) validateDoc();
      });
      docTypeEl.addEventListener("change", () => {
        sanitizeDoc();
        if (showErrors) validateDoc();
      });

      namesEl?.addEventListener("input", () => {
        sanitizeNameField(namesEl);
        if (showErrors) validateNameField(namesEl, "tus nombres");
        else clearFieldError(namesEl);
      });
      lastnamesEl?.addEventListener("input", () => {
        sanitizeNameField(lastnamesEl);
        if (showErrors) validateNameField(lastnamesEl, "tus apellidos");
        else clearFieldError(lastnamesEl);
      });

      phoneEl?.addEventListener("input", () => {
        sanitizePhone();
        if (showErrors) validatePhone();
        else clearFieldError(phoneEl);
      });
      emailEl?.addEventListener("input", () => {
        if (showErrors) validateEmail();
        else clearFieldError(emailEl);
      });

      deptEl?.addEventListener("change", () => {
        if (showErrors) validateDept();
        else clearFieldError(deptEl);
      });
      storeEl?.addEventListener("change", () => {
        if (showErrors) validateStore();
        else clearFieldError(storeEl);
      });

      const syncPointerEvents = () => {
        const actionsWrap = findActionsWrap(form);
        if (!actionsWrap || !submitBtn) return;

        const isDisabled = submitBtn.disabled || submitBtn.getAttribute("disabled") !== null;

        if (isDisabled) {
          actionsWrap.style.pointerEvents = "auto";
          submitBtn.style.pointerEvents = "none";
        } else {
          actionsWrap.style.pointerEvents = "";
          submitBtn.style.pointerEvents = "";
        }
      };

      const attachClickHandler = () => {
        const actionsWrap = findActionsWrap(form);
        if (!actionsWrap) return false;
        if (actionsWrap.__mgClickMounted) return true;
        actionsWrap.__mgClickMounted = true;

        syncPointerEvents();

        actionsWrap.addEventListener(
          "click",
          (e) => {
            showErrors = true;

            const ok = validateAllCustom();
            if (!ok) {
              e.preventDefault();
              e.stopPropagation();
              const first = form.querySelector(".is-invalid");
              first?.scrollIntoView({ behavior: "smooth", block: "center" });
              first?.focus?.();
              return;
            }

            if (submitBtn && !submitBtn.disabled) return;

            console.warn("[MG_VALIDATE] Todo OK, pero CF7 mantiene el botón disabled (acceptance u otra regla).");
          },
          true
        );

        return true;
      };

      attachClickHandler();

      if (submitBtn && !submitBtn.__mgBtnObserved) {
        submitBtn.__mgBtnObserved = true;
        const btnObs = new MutationObserver(() => syncPointerEvents());
        btnObs.observe(submitBtn, { attributes: true, attributeFilter: ["disabled", "class"] });
      }

      const obs = new MutationObserver(() => {
        attachClickHandler();
        syncPointerEvents();
      });
      obs.observe(form, { childList: true, subtree: true, attributes: true });

      sanitizeDoc();
      sanitizePhone();
      syncPointerEvents();

      return true;
    };

    let tries = 0;
    const timer = setInterval(() => {
      tries++;
      const form = findForm();
      const actions = findActionsWrap(form);

      if (form && actions) {
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
   *  BOOT
   * ========================= */
  document.addEventListener("DOMContentLoaded", () => {
    fillTrackingHidden();
    initCf7Logs();
    initQuoteBlocks();
    initDeptStoreDynamic();
    initGeo();
    initCotizaValidation();
  });
})();
