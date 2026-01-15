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

  const safeText = (el, text) => {
    if (!el) return;
    el.textContent = text;
  };

  /** =========================
   *  1) Tracking + product_id por URL
   * ========================= */
  const params = getUrlParams();

  const fillTrackingHidden = () => {
    // Hidden tracking
    setFieldById("utm_source", params.utm_source || "", true);
    setFieldById("utm_medium", params.utm_medium || "", true);
    setFieldById("utm_campaign", params.utm_campaign || "", true);
    setFieldById("utm_term", params.utm_term || "", true);
    setFieldById("utm_content", params.utm_content || "", true);
    setFieldById("gclid", params.gclid || "", true);
    setFieldById("fbclid", params.fbclid || "", true);

    // Si llega por URL:
    if (params.product_id) setFieldById("product_id", params.product_id, true);
  };

  /** =========================
   *  2) GEO (botón "Habilitar ubicación actual")
   * ========================= */
  const initGeo = () => {
    const geoBtn = $("#geely-cotiza-geo-btn");
    const geoStatus = $("#geely-cotiza-geo-status");
    const latId = "geely-cotiza-lat";
    const lngId = "geely-cotiza-lng";

    const setGeo = (lat, lng) => {
      setFieldById(latId, String(lat), true);
      setFieldById(lngId, String(lng), true);
    };

    const setGeoStatus = (msg) => safeText(geoStatus, msg);

    if (!geoBtn) return;

    geoBtn.addEventListener("click", (e) => {
      e.preventDefault();

      if (!navigator.geolocation) {
        setGeoStatus("Tu navegador no soporta geolocalización.");
        return;
      }

      setGeoStatus("Obteniendo ubicación...");

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const { latitude, longitude } = pos.coords;
          setGeo(latitude, longitude);
          setGeoStatus("Ubicación registrada");
        },
        (err) => {
          if (err.code === 1) setGeoStatus("Permiso denegado. Activa la ubicación en tu navegador.");
          else if (err.code === 2) setGeoStatus("No se pudo obtener tu ubicación.");
          else if (err.code === 3) setGeoStatus("Tiempo de espera agotado. Intenta nuevamente.");
          else setGeoStatus("Error obteniendo ubicación.");
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  };

  /** =========================
   *  3) CF7 logs (payload frontend + respuesta backend)
   * ========================= */
  const initCf7Logs = () => {
    // Snapshot del FormData ANTES de enviar (frontend)
    document.addEventListener("wpcf7beforesubmit", (e) => {
      // asegura que sea ESTE form (por si hay más CF7 en la página)
      if (e.target !== form) return;

      const ok = validateAll({ showNative: true });

      if (!ok) {
        // Evita que CF7 continúe el envío AJAX
        e.preventDefault();

        // Opcional: baja al primer error visible
        const firstInvalid = form.querySelector(".is-invalid");
        if (firstInvalid) {
          firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
          firstInvalid.focus?.();
        }
      }
    });

    // Resultado de CF7 (aquí llega mg_api)
    document.addEventListener("wpcf7submit", (e) => {
      const res = e.detail?.apiResponse;
      console.log("[MG_QUOTE] wpcf7submit apiResponse:", res);

      if (res?.mg_payload) {
        console.log("[MG_QUOTE] PAYLOAD SENT TO API (body):", res.mg_payload);
      } else {
        console.warn("[MG_QUOTE] No llegó mg_payload. Revisa el filtro wpcf7_ajax_json_echo.");
      }

      if (res?.mg_api) {
        console.log("[MG_QUOTE] API RESULT (backend):", res.mg_api);
      } else {
        console.warn("[MG_QUOTE] No llegó mg_api. Revisa el filtro wpcf7_ajax_json_echo.");
      }
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

    // Log al click del botón
    document.addEventListener("click", (e) => {
      const btn = e.target.closest('input.wpcf7-submit, button.wpcf7-submit, .geely-cotiza-submit');
      if (!btn) return;
      console.log("[MG_QUOTE] CLICK Cotizar", btn);
    });
  };

  /** =========================
   *  4) Bloque de cotización (tabs/steps/selección modelos/colores)
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

      // extras para tu payload (si los agregas como hidden en CF7)
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

      // Placeholder no seleccionable (solo UI)
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

          // extras para tu payload
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
   *  5) Validaciones en vivo (typing) + click (CF7 async-safe)
   *
   *  Objetivos:
   *  Al PRIMER click en "Cotizar" debe mostrar errores (debajo, con .geely-field-error)
   *  NO usar tooltips nativos del browser ("Por favor rellene...")
   *  Luego del 1er intento, valida en vivo mientras escribe/cambia
   *  Celular: +51 y 9 números => +519XXXXXXXX
   *  Doc: DNI (8 num), RUC (11 num), otros alfanumérico+&
   *  NO “matar” el submit: si está enabled => CF7 envía normal.
   *  si está disabled => click en wrapper muestra errores (sin enviar).
   * ========================= */
  const initCotizaValidation = () => {
    const MAX_TRIES = 60;
    const TRY_EVERY = 250;

    const findForm = () => document.querySelector(".wpcf7 form");
    const findActionsWrap = (form) =>
      form?.querySelector(".geely-cotiza-actions") || null;

    const boot = (form) => {
      if (form.__mgValidationMounted) return true;
      form.__mgValidationMounted = true;

      // evita tooltips nativos del browser
      form.setAttribute("novalidate", "novalidate");

      // solo mostramos errores después del primer intento
      let showErrors = false;

      // ===== Campos =====
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

      // ===== Helpers =====
      const NAME_ALLOWED = /[^a-zA-Z0-9&ÑñáéíóúÁÉÍÓÚ'\-\s]/g;

      const docType = () => (docTypeEl.value || "").toUpperCase().trim();

      const isNumericDocType = () => {
        const t = docType();
        return t === "DNI" || t === "RUC";
      };

      const getDocMaxLen = () => {
        const t = docType();
        if (t === "DNI") return 8;
        if (t === "RUC") return 11;
        return 20;
      };

      const getDocDisallowedRegex = () => {
        if (isNumericDocType()) return /[^0-9]/g; // DNI/RUC: solo números
        return /[^a-zA-Z0-9&]/g; // Otros: alfanumérico + &
      };

      const ensureErrorEl = (fieldEl) => {
        const wrap =
          fieldEl?.closest(".geely-cotiza-row__control") ||
          fieldEl?.parentElement;
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
        // antes del 1er intento no pintamos nada
        if (!showErrors) {
          // pero sí limpiamos estado previo si existía
          if (!message) {
            paintError(fieldEl, "");
          }
          return;
        }
        paintError(fieldEl, message);
      };

      const clearFieldError = (fieldEl) => paintError(fieldEl, "");

      // ===== Documento =====
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

        if (!v) {
          setFieldError(docEl, "Ingresa tu número de documento.");
          return false;
        }

        if ((t === "DNI" || t === "RUC") && !/^\d+$/.test(v)) {
          setFieldError(docEl, "Solo se permiten números.");
          return false;
        }

        if (t === "DNI" && v.length !== 8) {
          setFieldError(docEl, "DNI debe tener 8 dígitos.");
          return false;
        }

        if (t === "RUC" && v.length !== 11) {
          setFieldError(docEl, "RUC debe tener 11 dígitos.");
          return false;
        }

        if (v.length > maxLen) {
          setFieldError(docEl, `Máximo ${maxLen} caracteres.`);
          return false;
        }

        setFieldError(docEl, "");
        return true;
      };

      // ===== Nombres/Apellidos =====
      const sanitizeNameField = (el) => {
        if (!el) return;
        const before = el.value || "";
        const v = before.replace(NAME_ALLOWED, "");
        if (v !== before) el.value = v;
      };

      const validateNameField = (el, label) => {
        if (!el) return true;
        const v = (el.value || "").trim();
        if (!v) {
          setFieldError(el, `Ingresa ${label}.`);
          return false;
        }
        setFieldError(el, "");
        return true;
      };

      // ===== Celular (+51 y 9 números) =====
      // Formato requerido: +519XXXXXXXX
      const sanitizePhone = () => {
        if (!phoneEl) return;
        const before = phoneEl.value || "";

        // deja solo + y dígitos
        let v = before.replace(/[^\d+]/g, "");

        // normaliza + (máximo un + al inicio)
        if (v.includes("+")) {
          v = v.replace(/\+/g, "");
          v = "+" + v;
        }

        // si escribe 51xxxx sin + -> +51xxxx
        if (/^51\d/.test(v)) v = "+" + v;

        // limita longitud máxima razonable
        if (v.length > 13) v = v.slice(0, 13);

        if (v !== before) phoneEl.value = v;
      };

      const validatePhone = () => {
        if (!phoneEl) return true;
        const v = (phoneEl.value || "").trim();

        if (!v) {
          setFieldError(phoneEl, "Ingresa tu celular.");
          return false;
        }

        // exacto +51 y 9 números
        if (!/^\+51\d{9}$/.test(v)) {
          setFieldError(phoneEl, "Celular precisa del +51 y 9 numeros.");
          return false;
        }

        setFieldError(phoneEl, "");
        return true;
      };

      // ===== Email =====
      const validateEmail = () => {
        if (!emailEl) return true;
        const v = (emailEl.value || "").trim();
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);

        if (!v) {
          setFieldError(emailEl, "Ingresa tu email.");
          return false;
        }
        if (!emailOk) {
          setFieldError(emailEl, "Ingresa un email válido.");
          return false;
        }

        setFieldError(emailEl, "");
        return true;
      };

      // ===== Selects =====
      const validateDept = () => {
        if (!deptEl) return true;
        const v = (deptEl.value || "").trim();
        if (!v || v.toLowerCase().includes("selecciona")) {
          setFieldError(deptEl, "Selecciona un departamento.");
          return false;
        }
        setFieldError(deptEl, "");
        return true;
      };

      const validateStore = () => {
        if (!storeEl) return true;
        const v = (storeEl.value || "").trim();
        if (!v || v.toLowerCase().includes("selecciona")) {
          setFieldError(storeEl, "Selecciona una tienda.");
          return false;
        }
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

      // ===== En vivo (solo después del 1er intento) =====
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

      /** =========================
       *  Pointer-events sync:
       *   - submit ENABLED => click normal (CF7 envía)
       *   - submit DISABLED => click wrapper para mostrar errores
       * ========================= */
      const syncPointerEvents = () => {
        const actionsWrap = findActionsWrap(form);
        if (!actionsWrap || !submitBtn) return;

        const isDisabled =
          submitBtn.disabled || submitBtn.getAttribute("disabled") !== null;

        if (isDisabled) {
          actionsWrap.style.pointerEvents = "auto";
          submitBtn.style.pointerEvents = "none";
        } else {
          actionsWrap.style.pointerEvents = "";
          submitBtn.style.pointerEvents = "";
        }
      };

      /** =========================
       *  CLICK handler:
       *   - Siempre enciende showErrors en el primer click
       *   - Si submit está disabled => valida y muestra errores
       *   - Si submit está enabled => NO bloquea, CF7 envía.
       * ========================= */
      const attachClickHandler = () => {
        const actionsWrap = findActionsWrap(form);
        if (!actionsWrap) return false;

        if (actionsWrap.__mgClickMounted) return true;
        actionsWrap.__mgClickMounted = true;

        syncPointerEvents();

        actionsWrap.addEventListener(
          "click",
          (e) => {
            // primer intento: desde aquí ya se pintan errores
            showErrors = true;

            // Si submit está enabled, dejamos que CF7 haga submit (no interceptamos)
            if (submitBtn && !submitBtn.disabled) {
              // pero aprovechamos para validar y mostrar errores si hubiera (por si CF7 no los muestra como quieres)
              const ok = validateAllCustom();
              if (!ok) {
                // bloquea (porque si no, CF7 intentará enviar y te dará mensaje genérico)
                e.preventDefault();
                e.stopPropagation();
                const first = form.querySelector(".is-invalid");
                first?.scrollIntoView({ behavior: "smooth", block: "center" });
                first?.focus?.();
              }
              return;
            }

            // Submit disabled => solo validamos y mostramos errores
            const okCustom = validateAllCustom();

            if (!okCustom) {
              e.preventDefault();
              e.stopPropagation();

              const first = form.querySelector(".is-invalid");
              if (first) {
                first.scrollIntoView({ behavior: "smooth", block: "center" });
                first.focus?.();
              }
              return;
            }

            console.warn(
              "[MG_VALIDATE] Todo OK, pero CF7 mantiene el botón disabled (acceptance u otra regla)."
            );
          },
          true
        );

        console.log("[MG_VALIDATE] Click handler montado ✅");
        return true;
      };

      attachClickHandler();

      // Observa cambios del botón (disabled/spinner)
      if (submitBtn && !submitBtn.__mgBtnObserved) {
        submitBtn.__mgBtnObserved = true;

        const btnObs = new MutationObserver(() => syncPointerEvents());
        btnObs.observe(submitBtn, {
          attributes: true,
          attributeFilter: ["disabled", "class"],
        });
      }

      // Si CF7 re-renderiza el form/footer, re-monta handler y re-sync
      const obs = new MutationObserver(() => {
        attachClickHandler();
        syncPointerEvents();
      });
      obs.observe(form, { childList: true, subtree: true, attributes: true });

      // Estado inicial (NO pintar errores)
      sanitizeDoc();
      sanitizePhone();
      syncPointerEvents();

      console.log("[MG_VALIDATE] initCotizaValidation montado");
      return true;
    };

    // ===== Reintentos =====
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
    initGeo();
    initCf7Logs();
    initQuoteBlocks();
    initCotizaValidation();
  });
})();
