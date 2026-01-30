(() => {
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

  /** =========================
   *  GLOBALS (para evitar duplicados y compartir estado entre módulos)
   * ========================= */
  if (typeof window.__mgStoreChangeInternal !== "boolean") window.__mgStoreChangeInternal = false;
  if (typeof window.__mgLastRecommendationsKey !== "string") window.__mgLastRecommendationsKey = "";
  if (!window.__mgStoreCacheByDept) window.__mgStoreCacheByDept = {}; // deptId -> items[]

  /** =========================
   *  5) Bloque de cotización (COLORES + CONTINUAR) - IGUAL (no se recorta)
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

    const scrollToInner = (root) => {
      const el = root.querySelector("#mg-quote__inner") || document.getElementById("mg-quote__inner");
      if (!el) return;

      requestAnimationFrame(() => {
        el.scrollIntoView({ behavior: "smooth", block: "start" });
      });
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
          Array.from(dotsWrap.querySelectorAll(".mg-quote__colorDotBtn")).forEach((x) =>
            x.classList.remove("is-active")
          );
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

      if (step === 3) {
        scrollToInner(root);
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

      if (cards[0]) selectCard(cards[0]);

      cards.forEach((cardEl) => {
        cardEl.addEventListener("click", (e) => {
          const inYear = e.target?.closest?.("[data-year-radio]");
          if (inYear) return;
          selectCard(cardEl);
        });
      });

      tabs.forEach((btn) => {
        btn.addEventListener("click", () => {
          const tabStep = Number(btn.getAttribute("data-step-tab") || "1");
          if (tabStep === 1) setStep(root, 1);
          if (tabStep === 2 && root.__mgSelected) setStep(root, 2);
        });
      });

      if (nextBtn) {
        nextBtn.addEventListener("click", () => {
          if (!root.__mgSelected) return;
          fillCf7Hidden(root.__mgSelected);
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
          fillCf7Hidden(root, root.__mgSelected);
        }
      });

      document.addEventListener("wpcf7mailsent", () => {
        const step2 = q(root, '.mg-quote__panel[data-step="2"]');
        if (!step2 || !step2.classList.contains("is-active")) return;
        setStep(root, 3);
      });
    });
  };

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
    initQuoteBlocks();
    initDataPolicyModal();
  });

  document.addEventListener("wpcf7init", () => {
    waitAndMountCf7Features();
  });
})();
