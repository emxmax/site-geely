(function () {
  const drawer = document.getElementById("mg-mobile-drawer");
  const burger = document.querySelector(".mg-header__burger");
  const drawerNavHost = drawer ? drawer.querySelector(".mg-drawer__nav") : null;
  const headerNav = document.querySelector(".mg-header__nav");

  if (!drawer || !burger || !drawerNavHost || !headerNav) return;

  // placeholder para devolver el nav a su sitio original
  const placeholder = document.createElement("span");
  placeholder.className = "mg-nav-placeholder";
  headerNav.parentNode.insertBefore(placeholder, headerNav);

  function moveNavToDrawer() {
    if (drawerNavHost.contains(headerNav)) return;

    drawerNavHost.innerHTML = "";
    drawerNavHost.appendChild(headerNav);

    headerNav.classList.add("mg-nav--in-drawer");
    bindMobileAccordion();
  }

  function moveNavBackToHeader() {
    if (placeholder.parentNode) {
      placeholder.parentNode.insertBefore(headerNav, placeholder.nextSibling);
    }
    headerNav.classList.remove("mg-nav--in-drawer");
    headerNav.querySelectorAll(".is-subopen").forEach((el) => el.classList.remove("is-subopen"));
  }

  function openDrawer() {
    moveNavToDrawer();
    drawer.classList.add("is-open");
    drawer.setAttribute("aria-hidden", "false");
    burger.setAttribute("aria-expanded", "true");
    document.documentElement.classList.add("mg-no-scroll");
    document.body.classList.add("mg-no-scroll");
  }

  function closeDrawer() {
    drawer.classList.remove("is-open");
    drawer.setAttribute("aria-hidden", "true");
    burger.setAttribute("aria-expanded", "false");
    document.documentElement.classList.remove("mg-no-scroll");
    document.body.classList.remove("mg-no-scroll");
    moveNavBackToHeader();
  }

  burger.addEventListener("click", openDrawer);

  drawer.addEventListener("click", (e) => {
    const closeEl = e.target.closest("[data-mg-drawer-close]");
    if (closeEl) {
      closeDrawer();
      return;
    }

    // si tocan un link SIN hijos, cerramos drawer
    const link = e.target.closest(".wp-block-navigation a");
    if (link) {
      const li = link.closest(".wp-block-navigation-item");
      const hasChild = li && (li.classList.contains("has-child") || li.classList.contains("menu-item-has-children"));
      if (!hasChild) closeDrawer();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && drawer.classList.contains("is-open")) closeDrawer();
  });

  function bindMobileAccordion() {
    if (!headerNav.classList.contains("mg-nav--in-drawer")) return;

    const items = headerNav.querySelectorAll(".wp-block-navigation-item.has-child, .wp-block-navigation-item.menu-item-has-children");
    items.forEach((li) => {
      // click en el LABEL (link) => toggle (no navegar)
      const link = li.querySelector(":scope > a.wp-block-navigation-item__content");
      if (link && link.dataset.mgBound !== "1") {
        link.dataset.mgBound = "1";
        link.addEventListener("click", (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          li.classList.toggle("is-subopen");

          // cerrar otros
          items.forEach((other) => {
            if (other !== li) other.classList.remove("is-subopen");
          });
        });
      }

      // click en el BOTÓN flecha => toggle (por si WP lo renderiza)
      const btn = li.querySelector(":scope > button.wp-block-navigation__submenu-icon");
      if (btn && btn.dataset.mgBound !== "1") {
        btn.dataset.mgBound = "1";
        btn.addEventListener("click", (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          li.classList.toggle("is-subopen");

          items.forEach((other) => {
            if (other !== li) other.classList.remove("is-subopen");
          });
        });
      }
    });
  }

  // si cambias a desktop con drawer abierto, lo cerramos
  window.addEventListener("resize", () => {
    if (window.innerWidth > 900 && drawer.classList.contains("is-open")) closeDrawer();
  });
})();
