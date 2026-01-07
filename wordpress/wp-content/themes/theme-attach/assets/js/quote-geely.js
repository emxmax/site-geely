document.addEventListener("DOMContentLoaded", () => {
  const roots = window.__MG_QUOTE_BLOCKS__ || [];
  if (!roots.length) return;

  const q = (root, sel) => root.querySelector(sel);
  const qa = (root, sel) => Array.from(root.querySelectorAll(sel));

  // CF7 hidden fields (IDs deben coincidir con id:... en el form)
  const fillCf7Hidden = (data) => {
    const setVal = (id, val) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.value = val ?? "";
      el.dispatchEvent(new Event("input", { bubbles: true }));
      el.dispatchEvent(new Event("change", { bubbles: true }));
    };

    setVal("product_id", data.product_id);
    setVal("product_title", data.product_title);
    setVal("model_slug", data.model_slug);
    setVal("model_name", data.model_name);
    setVal("model_year", data.model_year);
    setVal("model_price_usd", data.model_price_usd);
    setVal("model_price_local", data.model_price_local);
    setVal("color_name", data.color_name);
    setVal("color_hex", data.color_hex);
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

    if (colorDotEl) {
      colorDotEl.style.backgroundColor = data.color_hex || "#027bff";
    }

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
        Array.from(dotsWrap.querySelectorAll(".mg-quote__colorDotBtn")).forEach(
          (x) => x.classList.remove("is-active")
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

    // en Step 2 queremos asegurar que el resumen muestre la selección actual
    if (step === 2 && root.__mgSelected) {
      applyLeftSummary(root, root.__mgSelected);
      fillCf7Hidden(root.__mgSelected);
    }

    // step 3 ya lo manejas con CF7 (si necesitas otra lógica, se agrega aquí)
  };

  roots.forEach((sel) => {
    const root = document.querySelector(sel);
    if (!root) return;

    const productId = root.getAttribute("data-product-id") || "";
    const productTitle =
      q(root, ".mg-quote__productName")?.textContent?.trim() || "";

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

      // ✅ año real = el radio marcado dentro del card (o forzado por opts)
      const cardYear = opts.forceYear || getSelectedYearFromCard(btn);

      selected = {
        product_id: productId,
        product_title: productTitle,
        model_slug: btn.getAttribute("data-model-slug") || "",
        model_name: btn.getAttribute("data-model-name") || "",
        model_year: cardYear || "",
        model_price_usd: btn.getAttribute("data-model-price-usd") || "",
        model_price_local: btn.getAttribute("data-model-price-local") || "",
        model_image:
          firstColor?.imgD || btn.getAttribute("data-model-image") || "",
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

    // Inicial
    if (cards[0]) selectCard(cards[0]);

    // Click card
    cards.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        // si el click viene de un label/radio de año, igual seleccionamos la card,
        // pero leyendo el año marcado (ya lo hace getSelectedYearFromCard)
        selectCard(btn);
      });
    });

    // Tabs click
    tabs.forEach((btn) => {
      btn.addEventListener("click", () => {
        const tabStep = Number(btn.getAttribute("data-step-tab") || "1");
        if (tabStep === 1) setStep(root, 1);
        if (tabStep === 2 && root.__mgSelected) setStep(root, 2);
      });
    });

    // Next => Step 2
    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        if (root.__mgSelected) fillCf7Hidden(root.__mgSelected);
        setStep(root, 2);
      });
    }

    setStep(root, 1);

    // ✅ Cambiar año (radios) => NO cambia de modelo, solo actualiza año del modelo seleccionado.
    root.addEventListener("change", (e) => {
      const t = e.target;
      if (!(t instanceof HTMLInputElement)) return;
      if (!t.matches("[data-year-radio]")) return;

      const card = t.closest("[data-model-card]");
      if (!card) return;

      // si cambian el año en una card no seleccionada, seleccionamos esa card con ese año
      if (!card.classList.contains("is-selected")) {
        selectCard(card, { forceYear: t.value });
        return;
      }

      // card seleccionada => actualizar año seleccionado
      if (root.__mgSelected) {
        root.__mgSelected.model_year = t.value || "";
        applyLeftSummary(root, root.__mgSelected);
        fillCf7Hidden(root.__mgSelected);
      }
    });

    // CF7 success => step 3
    document.addEventListener("wpcf7mailsent", () => {
      const step2 = q(root, '.mg-quote__panel[data-step="2"]');
      if (!step2 || !step2.classList.contains("is-active")) return;
      setStep(root, 3);
    });
  });
});
