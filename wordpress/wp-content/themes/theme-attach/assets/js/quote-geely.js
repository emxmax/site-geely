document.addEventListener("DOMContentLoaded", () => {
  const roots = window.__MG_QUOTE_BLOCKS__ || [];
  if (!roots.length) return;

  const q = (root, sel) => root.querySelector(sel);
  const qa = (root, sel) => Array.from(root.querySelectorAll(sel));

  const updateConfirm = (root) => {
    const selected = root.__mgSelected;
    if (!selected) return;

    const hero = q(root, "[data-confirm-hero]");
    if (hero && selected.model_image) hero.src = selected.model_image;

    // si quieres, también actualiza el nombre del producto en el texto
    const p1 = q(root, "[data-confirm-product]");
    const p2 = q(root, "[data-confirm-product-2]");
    if (p1) p1.textContent = selected.product_title || p1.textContent;
    if (p2) p2.textContent = selected.product_title || p2.textContent;
  };

  const setStep = (root, step) => {
    root.setAttribute("data-step", String(step));

    qa(root, ".mg-quote__panel").forEach(p => p.classList.remove("is-active"));
    const panel = q(root, `.mg-quote__panel[data-step="${step}"]`);
    if (panel) panel.classList.add("is-active");

    qa(root, ".mg-quote__step").forEach(s => s.classList.remove("is-active"));
    const ind = q(root, `.mg-quote__step[data-step-indicator="${Math.min(step, 2)}"]`);
    if (ind) ind.classList.add("is-active");

    if (step === 3) updateConfirm(root);
  };

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
    if (modelYearEl) modelYearEl.textContent = data.model_year ? `Año ${data.model_year}` : "";

    if (carImgEl && data.model_image) carImgEl.src = data.model_image;

    if (colorDotEl) colorDotEl.style.background = data.color_hex || "#ccc";
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
        Array.from(dotsWrap.querySelectorAll(".mg-quote__colorDotBtn"))
          .forEach(x => x.classList.remove("is-active"));
        btn.classList.add("is-active");
        if (nameEl) nameEl.textContent = c.name || "";
        onPick(c);
      });

      dotsWrap.appendChild(btn);
    });

    if (nameEl) nameEl.textContent = colors[0]?.name || "";
  };

  roots.forEach((sel) => {
    const root = document.querySelector(sel);
    if (!root) return;

    const productId = root.getAttribute("data-product-id") || "";
    const productTitle = q(root, ".mg-quote__productName")?.textContent?.trim() || "";

    const cards = qa(root, "[data-model-card]");
    const nextBtn = q(root, "[data-next-step]");

    let selected = null;

    const pickColor = (colorObj) => {
      if (!selected) return;
      selected.color_name = colorObj.name || "";
      selected.color_hex = colorObj.hex || "#ccc";
      selected.model_image = colorObj.imgD || selected.model_image;

      root.__mgSelected = selected;
      applyLeftSummary(root, selected);
      fillCf7Hidden(selected);
    };

    const selectCard = (btn) => {
      cards.forEach(c => c.classList.remove("is-selected"));
      btn.classList.add("is-selected");

      const colors = (() => {
        try { return JSON.parse(btn.getAttribute("data-model-colors") || "[]"); }
        catch { return []; }
      })();

      const firstColor = colors[0] || null;

      selected = {
        product_id: productId,
        product_title: productTitle,
        model_slug: btn.getAttribute("data-model-slug") || "",
        model_name: btn.getAttribute("data-model-name") || "",
        model_year: btn.getAttribute("data-model-year") || "",
        model_price_usd: btn.getAttribute("data-model-price-usd") || "",
        model_price_local: btn.getAttribute("data-model-price-local") || "",
        model_image: firstColor?.imgD || btn.getAttribute("data-model-image") || "",
        color_name: firstColor?.name || "",
        color_hex: firstColor?.hex || "#ccc",
        colors
      };

      root.__mgSelected = selected;

      if (selected.colors && selected.colors.length) {
        renderColorsStep1(root, selected.colors, pickColor);
      }

      applyLeftSummary(root, selected);
      fillCf7Hidden(selected);
    };

    if (cards[0]) selectCard(cards[0]);
    cards.forEach(btn => btn.addEventListener("click", () => selectCard(btn)));

    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        if (selected) fillCf7Hidden(selected);
        setStep(root, 2);
      });
    }

    setStep(root, 1);

    // CF7 success => step 3
    document.addEventListener("wpcf7mailsent", () => {
      const step2 = q(root, '.mg-quote__panel[data-step="2"]');
      if (!step2 || !step2.classList.contains("is-active")) return;
      setStep(root, 3);
    });
  });
});
