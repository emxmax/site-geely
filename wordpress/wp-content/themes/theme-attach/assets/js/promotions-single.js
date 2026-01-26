(function () {
  "use strict";

  // =========================
  // Helpers (igual idea que quote-form.js)
  // =========================
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

  const sanitizeMoney = (v) => {
    // deja solo números y punto decimal (opcional)
    // "1,234.50" -> "1234.50"
    return String(v ?? "")
      .replace(/[^\d.,]/g, "")
      .replace(/,/g, "");
  };

  const ensureHiddenById = (form, id, name) => {
    let el = document.getElementById(id);
    if (el) return el;

    el = document.createElement("input");
    el.type = "hidden";
    el.id = id;
    el.name = name || id;
    el.value = "";
    form.appendChild(el);
    return el;
  };

  // =========================
  // 1) PROMO: copiar precios a hidden del CF7
  // =========================
  const initPromotionPrices = () => {
    const form = document.querySelector(".wpcf7 form");
    if (!form) return;

    // Evita duplicar listeners
    if (form.__mgPromoPricesMounted) return;
    form.__mgPromoPricesMounted = true;

    // Inputs visibles que llena el usuario (AJUSTA estos selectores)
    const usdInput = document.querySelector("#promo-usd");
    const penInput = document.querySelector("#promo-pen");

    if (!usdInput && !penInput) return;

    // Hidden que tu quote-api.php ya lee:
    // model_price_usd  -> pr_pub_usd
    // model_price_local -> pr_pub_pen
    ensureHiddenById(form, "model_price_usd", "model_price_usd");
    ensureHiddenById(form, "model_price_local", "model_price_local");

    const sync = () => {
      if (usdInput) setFieldById("model_price_usd", sanitizeMoney(usdInput.value), true);
      if (penInput) setFieldById("model_price_local", sanitizeMoney(penInput.value), true);
    };

    usdInput && usdInput.addEventListener("input", sync);
    penInput && penInput.addEventListener("input", sync);

    sync(); // inicial
  };

  // =========================
  // 2) Tu lógica actual (modal + ver más)
  // =========================
  const termsModal = document.getElementById("geely-terms-modal");
  if (!termsModal) return;

  const openModal = () => {
    termsModal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  };

  const closeModal = () => {
    termsModal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  };

  document.addEventListener("click", (e) => {
    const link = e.target.closest(".mg-open-data-policy");
    if (!link) return;

    e.preventDefault();
    openModal();
  });

  termsModal.querySelectorAll("[data-modal-close]").forEach((btn) => {
    btn.addEventListener("click", closeModal);
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && termsModal.getAttribute("aria-hidden") === "false") {
      closeModal();
    }
  });

  const verMas = document.querySelector("#ver-mas");
  const content = document.querySelector(".promotions-single-section .promotions-single__inner");
  if (verMas && content) verMas.addEventListener("click", () => content.classList.add("all"));

  // =========================
  // 3) Boot
  // =========================
  document.addEventListener("DOMContentLoaded", () => {
    initPromotionPrices();
  });

  // Por si CF7 renderiza tarde o por ajax
  document.addEventListener("wpcf7init", () => {
    initPromotionPrices();
  });
})();
