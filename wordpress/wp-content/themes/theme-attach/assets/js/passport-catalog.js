(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    const catalog = document.querySelector(".passport-catalog");
    if (!catalog) return;

    let totalPages = parseInt(catalog.dataset.totalPages) || 1;
    let currentPage = 1;
    let currentCategory = "todos";
    let isLoading = false;

    // Elementos
    let items = document.querySelectorAll(".js-passport-item");
    const prevBtn = document.querySelector(".js-passport-prev");
    const nextBtn = document.querySelector(".js-passport-next");
    let pageButtons = document.querySelectorAll(".js-passport-page");
    const gridWrapper = document.querySelector(".js-passport-grid");
    const paginationContainer = document.querySelector(
      ".passport-catalog__pagination"
    );

    /**
     * Mostrar items de una página específica
     */
    function showPage(pageNumber) {
      // Solo funciona si hay items y más de una página
      if (!items.length || totalPages === 1) return;

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
    }

    /**
     * Event listeners para botones de paginación
     */
    if (items.length && totalPages > 1) {
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
    }

    /**
     * Filtrar pasaportes por categoría via AJAX
     */
    function filterPassports(categorySlug) {
      if (isLoading) return;
      isLoading = true;

      // Mostrar estado de carga
      if (gridWrapper) {
        gridWrapper.style.opacity = "0.5";
      }

      const formData = new FormData();
      formData.append("action", "passport_filter_by_category");
      formData.append("nonce", PASSPORT_CATALOG.nonce);
      formData.append("category", categorySlug);

      fetch(PASSPORT_CATALOG.ajax_url, {
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
            catalog.dataset.totalPages = totalPages;

            // Actualizar referencias a items
            items = document.querySelectorAll(".js-passport-item");

            // Regenerar paginación
            regeneratePagination();

            // Resetear a página 1
            currentPage = 1;
            currentCategory = categorySlug;

            // Restablecer opacidad
            if (gridWrapper) {
              gridWrapper.style.opacity = "1";
            }

            // Scroll al inicio del catálogo
            catalog.scrollIntoView({ behavior: "smooth", block: "start" });
          } else {
            console.error("Error al filtrar pasaportes:", data.data.message);
          }
        })
        .catch((error) => {
          console.error("Error en la petición AJAX:", error);
        })
        .finally(() => {
          isLoading = false;
        });
    }

    /**
     * Regenerar paginación después de AJAX
     */
    function regeneratePagination() {
      if (!paginationContainer) return;

      // Si hay 1 o menos páginas, ocultar paginación
      if (totalPages <= 1) {
        paginationContainer.style.display = "none";
        return;
      }

      paginationContainer.style.display = "flex";

      // Regenerar botones de página
      const pageButtonsWrapper = paginationContainer.querySelector(
        ".passport-catalog__page-buttons"
      );
      if (pageButtonsWrapper) {
        let buttonsHTML = "";
        for (let i = 1; i <= totalPages; i++) {
          buttonsHTML += `
            <button type="button" class="passport-catalog__page-button js-passport-page ${
              i === 1 ? "is-active" : ""
            }" data-page="${i}">
              ${i}
            </button>
          `;
        }
        pageButtonsWrapper.innerHTML = buttonsHTML;

        // Re-asignar referencias y event listeners
        pageButtons = document.querySelectorAll(".js-passport-page");
        pageButtons.forEach((btn) => {
          btn.addEventListener("click", function () {
            const page = parseInt(this.dataset.page);
            if (page !== currentPage) {
              showPage(page);
            }
          });
        });
      }

      // Actualizar estado de botones de navegación
      const newPrevBtn = paginationContainer.querySelector(
        ".js-passport-prev"
      );
      const newNextBtn = paginationContainer.querySelector(
        ".js-passport-next"
      );

      if (newPrevBtn) {
        newPrevBtn.disabled = true;
        newPrevBtn.dataset.page = "1";
        newPrevBtn.addEventListener("click", function () {
          if (currentPage > 1) {
            showPage(currentPage - 1);
          }
        });
      }

      if (newNextBtn) {
        newNextBtn.disabled = totalPages === 1;
        newNextBtn.dataset.page = "1";
        newNextBtn.addEventListener("click", function () {
          if (currentPage < totalPages) {
            showPage(currentPage + 1);
          }
        });
      }
    }

    /**
     * Event listeners para tabs de categorías
     */
    const tabs = document.querySelectorAll(".js-passport-tab");
    tabs.forEach((tab) => {
      tab.addEventListener("click", function () {
        const categorySlug = this.dataset.tab;

        // Si es la categoría actual, no hacer nada
        if (categorySlug === currentCategory) return;

        // Actualizar tab activo
        tabs.forEach((t) => t.classList.remove("passport-catalog__tab--active"));
        this.classList.add("passport-catalog__tab--active");

        // Filtrar pasaportes
        filterPassports(categorySlug);
      });
    });

    // Inicializar - mostrar página 1
    if (items.length && totalPages > 1) {
      showPage(1);
    }
  });
})();
