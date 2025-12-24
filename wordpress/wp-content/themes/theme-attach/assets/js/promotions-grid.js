(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    const grid = document.querySelector(".promotions-grid");
    if (!grid) return;

    let totalPages = parseInt(grid.dataset.totalPages) || 1;
    let currentPage = 1;
    let currentCategory = "";
    let isLoading = false;

    // Elementos
    let items = document.querySelectorAll(".js-promo-item");
    const prevBtn = document.querySelector(".js-promo-prev");
    const nextBtn = document.querySelector(".js-promo-next");
    let pageButtons = document.querySelectorAll(".js-promo-page");
    const gridWrapper = document.querySelector(".js-promotions-grid");
    const paginationContainer = document.querySelector(".promotions-grid__pagination");

    if (!items.length || totalPages === 1) return;

    /**
     * Mostrar items de una página específica
     */
    function showPage(pageNumber) {
      // Ocultar todos los items
      items.forEach((item) => {
        item.style.display = "none";
      });

      // Mostrar items de la página actual
      items.forEach((item) => {
        if (parseInt(item.dataset.page) === pageNumber) {
          item.style.display = "block";
        }
      });

      // Actualizar botones de página
      pageButtons.forEach((btn) => {
        const btnPage = parseInt(btn.dataset.page);
        if (btnPage === pageNumber) {
          btn.classList.add("is-active");
        } else {
          btn.classList.remove("is-active");
        }
      });

      // Actualizar estado de botones de navegación
      if (prevBtn) {
        prevBtn.dataset.page = pageNumber;
        prevBtn.disabled = pageNumber === 1;
      }

      if (nextBtn) {
        nextBtn.dataset.page = pageNumber;
        nextBtn.disabled = pageNumber === totalPages;
      }

      currentPage = pageNumber;

      // Scroll al inicio de la grid
      // grid.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    /**
     * Event listeners para botones de paginación
     */
    pageButtons.forEach((btn) => {
      btn.addEventListener("click", function () {
        const page = parseInt(this.dataset.page);
        if (page !== currentPage) {
          showPage(page);
        }
      });
    });

    /**
     * Event listener para botón anterior
     */
    if (prevBtn) {
      prevBtn.addEventListener("click", function () {
        if (currentPage > 1) {
          showPage(currentPage - 1);
        }
      });
    }

    /**
     * Event listener para botón siguiente
     */
    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        if (currentPage < totalPages) {
          showPage(currentPage + 1);
        }
      });
    }

    /**
     * Filtrar promociones por categoría via AJAX
     */
    function filterPromotions(categorySlug) {
      if (isLoading) return;
      isLoading = true;

      // Mostrar estado de carga (opcional)
      if (gridWrapper) {
        gridWrapper.style.opacity = "0.5";
      }

      const formData = new FormData();
      formData.append("action", "promotions_filter_by_category");
      formData.append("nonce", PROMOTIONS_GRID.nonce);
      formData.append("category", categorySlug);

      fetch(PROMOTIONS_GRID.ajax_url, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            // Actualizar el grid con los nuevos resultados
            if (gridWrapper) {
              gridWrapper.innerHTML = data.data.html;
            }

            // Actualizar totalPages
            totalPages = parseInt(data.data.total_pages) || 1;
            grid.dataset.totalPages = totalPages;

            // Actualizar referencias a items
            items = document.querySelectorAll(".js-promo-item");

            // Regenerar paginación
            regeneratePagination();

            // Resetear a página 1
            currentPage = 1;
            currentCategory = categorySlug;

            // Restablecer opacidad
            if (gridWrapper) {
              gridWrapper.style.opacity = "1";
            }

            // Scroll al grid
            grid.scrollIntoView({ behavior: "smooth", block: "start" });
          }
          isLoading = false;
        })
        .catch((error) => {
          console.error("Error al filtrar promociones:", error);
          if (gridWrapper) {
            gridWrapper.style.opacity = "1";
          }
          isLoading = false;
        });
    }

    /**
     * Regenerar botones de paginación
     */
    function regeneratePagination() {
      if (!paginationContainer) return;

      const pagesContainer = document.querySelector(".js-promo-pages");
      if (!pagesContainer) return;

      // Limpiar páginas actuales
      pagesContainer.innerHTML = "";

      if (totalPages <= 1) {
        // Ocultar paginación si solo hay una página
        paginationContainer.style.display = "none";
        return;
      }

      paginationContainer.style.display = "flex";

      // Generar primeras 3 páginas
      for (let i = 1; i <= Math.min(3, totalPages); i++) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "promotions-grid__page js-promo-page";
        if (i === 1) btn.classList.add("is-active");
        btn.dataset.page = i;
        btn.textContent = i;
        btn.addEventListener("click", function () {
          const page = parseInt(this.dataset.page);
          if (page !== currentPage) {
            showPage(page);
          }
        });
        pagesContainer.appendChild(btn);
      }

      // Puntos suspensivos si hay muchas páginas
      if (totalPages > 5) {
        const dots = document.createElement("span");
        dots.className = "promotions-grid__dots";
        dots.textContent = "...";
        pagesContainer.appendChild(dots);
      }

      // Últimas páginas
      if (totalPages > 3) {
        for (let i = Math.max(4, totalPages - 1); i <= totalPages; i++) {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.className = "promotions-grid__page js-promo-page";
          btn.dataset.page = i;
          btn.textContent = i;
          btn.addEventListener("click", function () {
            const page = parseInt(this.dataset.page);
            if (page !== currentPage) {
              showPage(page);
            }
          });
          pagesContainer.appendChild(btn);
        }
      }

      // Actualizar referencias
      pageButtons = document.querySelectorAll(".js-promo-page");

      // Actualizar estado de botones de navegación
      if (prevBtn) {
        prevBtn.disabled = true;
      }
      if (nextBtn) {
        nextBtn.disabled = totalPages <= 1;
      }
    }

    /**
     * Funcionalidad de tabs
     */
    const tabs = document.querySelectorAll(".js-promo-tab");
    tabs.forEach((tab) => {
      tab.addEventListener("click", function () {
        // Remover clase activa de todos los tabs
        tabs.forEach((t) => t.classList.remove("promotions-hero__tab--active"));

        // Agregar clase activa al tab clickeado
        this.classList.add("promotions-hero__tab--active");

        // Obtener categoría del tab
        const tabCategory = this.dataset.tab; // 'ventas' o 'postventa'

        // Filtrar promociones por AJAX
        if (tabCategory) {
          filterPromotions(tabCategory);
        }
      });
    });
  });
})();
