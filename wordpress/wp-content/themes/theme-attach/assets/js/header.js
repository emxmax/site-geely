(function () {
  const drawer = document.getElementById("mg-mobile-drawer");
  const burger = document.querySelector(".mg-header__burger");
  const drawerNavHost = drawer ? drawer.querySelector(".mg-drawer__nav") : null;

  // Nav desktop real
  const headerNav = document.querySelector(".mg-header__nav");
  const headerUl = headerNav ? headerNav.querySelector(".mg-nav") : null;

  if (!drawer || !burger || !drawerNavHost || !headerNav || !headerUl) return;

  /** =========================
   *  MEGA MENU (DESKTOP)
   * ========================= */
  const closeAllDesktopMegas = () => {
    headerNav.querySelectorAll(".mg-nav__item--has-mega.is-open").forEach((li) => {
      li.classList.remove("is-open");
      const btn = li.querySelector("[data-mg-mega-trigger]");
      if (btn) btn.setAttribute("aria-expanded", "false");
    });
  };

  const toggleDesktopMega = (triggerBtn) => {
    const li = triggerBtn.closest(".mg-nav__item--has-mega");
    if (!li) return;

    const isOpen = li.classList.contains("is-open");
    closeAllDesktopMegas();

    if (!isOpen) {
      li.classList.add("is-open");
      triggerBtn.setAttribute("aria-expanded", "true");
    }
  };

  const bindDesktopMega = () => {
    const triggers = headerNav.querySelectorAll("[data-mg-mega-trigger]");
    triggers.forEach((btn) => {
      if (btn.dataset.mgBound === "1") return;
      btn.dataset.mgBound = "1";

      btn.addEventListener("click", (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        toggleDesktopMega(btn);
      });
    });
  };

  /** =========================
   *  BUILD MOBILE NAV (CLONE + LINK + TOGGLE)
   * ========================= */
  function buildMobileNav() {
    const mobileUl = headerUl.cloneNode(true);
    mobileUl.classList.add("mg-nav--in-drawer");

    // limpiar estados
    mobileUl.querySelectorAll(".is-open, .is-subopen").forEach((el) => {
      el.classList.remove("is-open", "is-subopen");
    });

    // detectar item Postventa (mega)
    const postventaItem = mobileUl.querySelector(".mg-nav__item--has-mega");
    if (postventaItem) {
      const trigger = postventaItem.querySelector(":scope > [data-mg-mega-trigger]");
      const mega = postventaItem.querySelector(":scope > [data-mg-mega]");

      // obtener links del mega (title links)
      let subLinks = [];
      if (mega) {
        subLinks = Array.from(mega.querySelectorAll(".mg-mega__title"))
          .map((a) => ({
            href: (a.getAttribute("href") || "").trim(),
            text: (a.textContent || "").trim(),
          }))
          .filter((x) => x.href && x.text);
      }

      if (!subLinks.length) {
        subLinks = [
          { href: "/postventa", text: "Postventa Geely" },
          { href: "/pasaportes-de-servicio-geely", text: "Pasaporte de servicio" },
          { href: "/promociones", text: "Promociones postventa" },
        ];
      }

      // 1) Reemplazar el trigger por un "row" con link + botón flecha
      const mainHref = "/postventa";
      const mainText = "Postventa";

      const row = document.createElement("div");
      row.className = "mg-mobile-row";

      const link = document.createElement("a");
      link.className = "mg-nav__link";
      link.href = mainHref;
      link.textContent = mainText;

      const toggleBtn = document.createElement("button");
      toggleBtn.type = "button";
      toggleBtn.className = "mg-mobile-toggle";
      toggleBtn.setAttribute("aria-label", "Desplegar Postventa");
      toggleBtn.setAttribute("aria-expanded", "false");
      toggleBtn.setAttribute("data-mg-mobile-toggle", "postventa");

      // flecha (la dibujas con CSS usando .mg-nav__arrow)
      toggleBtn.innerHTML = `<span class="mg-nav__arrow" aria-hidden="true"></span>`;

      row.appendChild(link);
      row.appendChild(toggleBtn);

      if (trigger) {
        trigger.replaceWith(row);
      } else {
        // por si no hay trigger (fallback)
        postventaItem.insertBefore(row, postventaItem.firstChild);
      }

      // 2) Eliminar mega del clon
      if (mega) mega.remove();

      // 3) Crear submenu UL (oculto por defecto)
      const subUl = document.createElement("ul");
      subUl.className = "mg-mobile-submenu";
      subUl.hidden = true;

      subLinks.forEach(({ href, text }) => {
        const li = document.createElement("li");
        li.className = "mg-nav__item mg-nav__item--sub";
        li.innerHTML = `<a class="mg-nav__link mg-nav__link--sub" href="${href}">${text}</a>`;
        subUl.appendChild(li);
      });

      postventaItem.appendChild(subUl);

      // 4) Quitar marcadores de mega para que no se intente usar acordeón
      postventaItem.classList.remove("mg-nav__item--has-mega");
      mobileUl.querySelectorAll("[data-mg-mega-trigger]").forEach((el) => el.removeAttribute("data-mg-mega-trigger"));
      mobileUl.querySelectorAll("[data-mg-mega]").forEach((el) => el.removeAttribute("data-mg-mega"));
    }

    return mobileUl;
  }

  function bindMobileToggleHandlers(root) {
    const toggle = root.querySelector('[data-mg-mobile-toggle="postventa"]');
    const item = root.querySelector(".mg-nav__item"); // primer item, pero buscamos el submenu dentro
    const postventaItem = root.querySelector(".mg-mobile-submenu") ? root.querySelector(".mg-mobile-submenu").closest(".mg-nav__item") : null;

    if (!toggle || !postventaItem) return;

    const sub = postventaItem.querySelector(".mg-mobile-submenu");
    if (!sub) return;

    toggle.addEventListener("click", (ev) => {
      ev.preventDefault();
      ev.stopPropagation();

      const isOpen = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", String(!isOpen));
      sub.hidden = isOpen;

      // rotación flecha usando clase
      postventaItem.classList.toggle("is-subopen", !isOpen);
    });
  }

  /** =========================
   *  DRAWER OPEN/CLOSE
   * ========================= */
  function openDrawer() {
    closeAllDesktopMegas();

    drawerNavHost.innerHTML = "";
    const mobileUl = buildMobileNav();
    drawerNavHost.appendChild(mobileUl);

    // bind toggle del submenu
    bindMobileToggleHandlers(drawerNavHost);

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

    drawerNavHost.innerHTML = "";
  }

  burger.addEventListener("click", () => {
    if (drawer.classList.contains("is-open")) closeDrawer();
    else openDrawer();
  });

  drawer.addEventListener("click", (e) => {
    const closeEl = e.target.closest("[data-mg-drawer-close]");
    if (closeEl) {
      closeDrawer();
      return;
    }

    // Si hicieron click en el botón de toggle (flecha), NO cerrar
    const toggleBtn = e.target.closest("[data-mg-mobile-toggle]");
    if (toggleBtn) return;

    // Si click en un link dentro del drawer, cerramos
    const a = e.target.closest("a");
    if (a) {
      closeDrawer();
      return;
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (drawer.classList.contains("is-open")) closeDrawer();
      closeAllDesktopMegas();
    }
  });

  /** =========================
   *  CLICK FUERA (DESKTOP MEGA)
   * ========================= */
  document.addEventListener("click", (e) => {
    if (drawer.classList.contains("is-open")) return;

    const insideMegaItem = e.target.closest(".mg-nav__item--has-mega");
    if (insideMegaItem) return;

    closeAllDesktopMegas();
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 900 && drawer.classList.contains("is-open")) closeDrawer();
    if (window.innerWidth <= 900) closeAllDesktopMegas();
  });

  bindDesktopMega();
})();
