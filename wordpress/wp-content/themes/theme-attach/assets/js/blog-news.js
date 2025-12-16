document.addEventListener("click", async (e) => {
  const btn = e.target.closest(".js-blog-news-loadmore");
  if (!btn) return;

  const section = btn.closest(".blog-news");
  const grid = section.querySelector(".js-blog-news-grid");
  const totalPages = parseInt(section.dataset.totalPages || "1", 10);

  const currentPage = parseInt(btn.dataset.page || "1", 10);
  const nextPage = currentPage + 1;

  if (nextPage > totalPages) return;

  btn.disabled = true;
  btn.textContent = "Cargando...";

  try {
    const form = new FormData();
    form.append("action", "blog_news_load_more");
    form.append("nonce", BLOG_NEWS.nonce);
    form.append("page", String(nextPage));

    const res = await fetch(BLOG_NEWS.ajax_url, {
      method: "POST",
      body: form,
      credentials: "same-origin",
    });

    const data = await res.json();
    if (!data.success) throw new Error("Load more failed");

    grid.insertAdjacentHTML("beforeend", data.data.html);

    btn.dataset.page = String(nextPage);

    if (nextPage >= totalPages) {
      btn.remove();
    } else {
      btn.disabled = false;
      btn.textContent = "Ver más";
    }
  } catch (err) {
    btn.disabled = false;
    btn.textContent = "Ver más";
    // opcional: console.error(err);
  }
});
