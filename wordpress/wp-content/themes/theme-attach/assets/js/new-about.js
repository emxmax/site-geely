(function () {
  document.addEventListener("DOMContentLoaded", function () {
    const section = document.querySelector(".new-about");
    if (!section) return;

    const grid = section.querySelector(".js-new-about-grid");
    const prevBtn = section.querySelector(".js-new-about-prev");
    const nextBtn = section.querySelector(".js-new-about-next");
    // Botones de paginación
    const pageButtons = section.querySelectorAll(".js-new-about-page");
    const totalPages = parseInt(section.dataset.totalPages) || 1;

    let currentPage = 1;

    function loadPage(page) {
      // 
      if (page < 1 || page > totalPages || page === currentPage) return;

      grid.classList.add("is-loading");

      const formData = new FormData();
      formData.append("action", "new_about_load_page");
      formData.append("nonce", NEW_ABOUT.nonce);
      formData.append("page", page);

      fetch(NEW_ABOUT.ajax_url, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success && data.data.html) {
            grid.innerHTML = data.data.html;
            currentPage = page;
            updatePagination();
            window.scrollTo({
              top: section.offsetTop - 100,
              behavior: "smooth",
            });
          }
        })
        .catch((err) => console.error("Error loading page:", err))
        .finally(() => {
          grid.classList.remove("is-loading");
        });
    }

    function updatePagination() {
      // Update prev/next buttons
      prevBtn.disabled = currentPage <= 1;
      nextBtn.disabled = currentPage >= totalPages;

      prevBtn.dataset.page = currentPage;
      nextBtn.dataset.page = currentPage;

      // Update page numbers
      pageButtons.forEach((btn) => {
        const btnPage = parseInt(btn.dataset.page);
        btn.classList.toggle("is-active", btnPage === currentPage);
      });
    }

    // Event listeners
    prevBtn.addEventListener("click", () => {
      loadPage(currentPage - 1);
    });

    nextBtn.addEventListener("click", () => {
      loadPage(currentPage + 1);
    });

    pageButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const page = parseInt(btn.dataset.page);
        loadPage(page);
      });
    });
  });
})();
