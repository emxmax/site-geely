(function () {
  "use strict";

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

  // MISMA clase del CF7: mg-open-data-policy
  document.addEventListener("click", (e) => {
    const link = e.target.closest(".mg-open-data-policy");
    if (!link) return;

    e.preventDefault();
    openModal();
  });

  // Cerrar (overlay/botón)
  termsModal.querySelectorAll("[data-modal-close]").forEach((btn) => {
    btn.addEventListener("click", closeModal);
  });

  // ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && termsModal.getAttribute("aria-hidden") === "false") {
      closeModal();
    }
  });

  // Ver más (tu lógica)
  const verMas = document.querySelector("#ver-mas");
  const content = document.querySelector(".promotions-single-section .promotions-single__inner");
  if (verMas && content) verMas.addEventListener("click", () => content.classList.add("all"));
})();
