(function () {
  "use strict";

  // Evitar romper si no está el form
  const form = document.querySelector(".geely-form");
  if (!form) return;

  const documentType = form.querySelector('select[name="document_type"]');
  const documentNumber = form.querySelector('input[name="document_number"]');
  const phone = form.querySelector('input[name="phone"]');

  const depSelect = form.querySelector("#geely-department");
  const storeSelect = form.querySelector("#geely-store");

  const geoBtn = form.querySelector("#geely-geo-btn");
  const geoStatus = form.querySelector("#geely-geo-status");

  const latInput = form.querySelector("#geely-lat");
  const lngInput = form.querySelector("#geely-lng");

  // 1) DATA: departamentos y tiendas desde WordPress
  const DATA =
    typeof GEELY_STORES_DATA !== "undefined" && GEELY_STORES_DATA.departments
      ? GEELY_STORES_DATA.departments
      : {};

  // 2) Inicializar selects
  function fillDepartments() {
    if (!depSelect) return;

    // Limpiar y agregar placeholder
    depSelect.innerHTML = "";
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Selecciona una opción";
    depSelect.appendChild(placeholder);

    // Agregar departamentos
    const departments = Object.keys(DATA).sort();
    departments.forEach((d) => {
      const opt = document.createElement("option");
      opt.value = d;
      opt.textContent = d;
      depSelect.appendChild(opt);
    });
  }

  function resetSelect(select, placeholderText) {
    if (!select) return;
    select.innerHTML = "";
    const ph = document.createElement("option");
    ph.value = "";
    ph.textContent = placeholderText || "Selecciona una opción";
    select.appendChild(ph);
  }

  // function fillStores(department) {
  //   resetSelect(storeSelect, "Selecciona una opción");
  //   if (!department || !DATA[department]) return;

  //   DATA[department].forEach((store) => {
  //     const opt = document.createElement("option");
  //     opt.value = store.title; // Usar el título de la tienda
  //     opt.textContent = store.title;
  //     storeSelect.appendChild(opt);
  //   });
  // }
  function setSpanishPlaceholder(select, text) {
    if (!select) return;

    const first = select.querySelector('option[value=""]');
    if (!first) return;

    first.textContent = text || "Selecciona una opción";
  }
  setSpanishPlaceholder(depSelect, "Selecciona una opción");
  setSpanishPlaceholder(storeSelect, "Selecciona una opción");
  
  function changeValueFirstOption(select, value = "first") {
    if (!select) return;
    select.classList.remove("first");
    const first = select.querySelector("option:checked")?.value;
    if (first === "") {
      select.classList.add("first");
    } else {
      select.classList.remove("first");
    }
  }
  changeValueFirstOption(depSelect);
  changeValueFirstOption(storeSelect);
  depSelect?.addEventListener("change", () => {
    changeValueFirstOption(depSelect);
  });
  storeSelect?.addEventListener("change", () => {
    changeValueFirstOption(storeSelect);
  });

  function fillStores(department, resetValue = true) {
    if (!storeSelect) return;

    if (resetValue) storeSelect.value = "";

    const allowedIds = new Set(
      (DATA[department] || []).map((s) => String(s.id))
    );

    Array.from(storeSelect.options).forEach((opt) => {
      const val = String(opt.value || "");
      if (!val) {
        opt.hidden = false;
        opt.disabled = false;
        return;
      }
      const allowed = allowedIds.has(val);
      opt.hidden = !allowed;
      opt.disabled = !allowed;
    });
  }

  // fillDepartments();
  // resetSelect(storeSelect, "Selecciona una opción");
  if (storeSelect) storeSelect.value = "";
  fillStores(depSelect?.value || "", false);

  depSelect?.addEventListener("change", (e) => {
    fillStores(e.target.value, true);
  });

  // 3) Validación DNI/RUC en front (UX)
  function applyDocRules() {
    const type = (documentType?.value || "").toLowerCase();

    if (!documentNumber) return;

    documentNumber.value = documentNumber.value.replace(/\D/g, "");

    if (type === "dni") {
      documentNumber.maxLength = 8;
      documentNumber.setAttribute("minlength", "8");
      documentNumber.setAttribute("maxlength", "8");
      // documentNumber.placeholder = "Ingresa tu DNI (8 dígitos)";
    } else if (type === "ruc") {
      documentNumber.maxLength = 11;
      documentNumber.setAttribute("minlength", "11");
      documentNumber.setAttribute("maxlength", "11");
      // documentNumber.placeholder = "Ingresa tu RUC (11 dígitos)";
    } else {
      documentNumber.removeAttribute("minlength");
      documentNumber.removeAttribute("maxlength");
      documentNumber.placeholder = "Ingresa tus datos";
    }
  }

  documentType?.addEventListener("change", applyDocRules);
  documentNumber?.addEventListener("input", applyDocRules);

  // 4) Tel: solo dígitos (Perú suele 9 dígitos, pero no te lo fuerzo)
  phone?.addEventListener("input", () => {
    phone.value = phone.value.replace(/\D/g, "");
  });

  // 5) Geolocalización (lat/lng + reverse geocode a "state" para departamento)
  async function reverseGeocode(lat, lng) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(
      lat
    )}&lon=${encodeURIComponent(lng)}`;
    const res = await fetch(url, {
      headers: {
        Accept: "application/json",
      },
    });
    if (!res.ok) throw new Error("No se pudo obtener la ubicación");
    return res.json();
  }

  function normalizeDepartment(name) {
    if (!name) return "";

    const normalized = name.toLowerCase().trim();

    // Mapeo de nombres de Nominatim a nombres de departamentos en WordPress
    const departmentMap = {
      lima: "Lima",
      "lima metropolitana": "Lima",
      "lima province": "Lima",
      arequipa: "Arequipa",
      cusco: "Cusco",
      cuzco: "Cusco",
      piura: "Piura",
      "la libertad": "La Libertad",
      callao: "Callao",
      lambayeque: "Lambayeque",
      ica: "Ica",
      junín: "Junín",
      junin: "Junín",
      ancash: "Áncash",
      áncash: "Áncash",
      cajamarca: "Cajamarca",
      puno: "Puno",
      loreto: "Loreto",
      huánuco: "Huánuco",
      huanuco: "Huánuco",
      "san martín": "San Martín",
      "san martin": "San Martín",
      tacna: "Tacna",
      ayacucho: "Ayacucho",
      ucayali: "Ucayali",
      moquegua: "Moquegua",
      apurímac: "Apurímac",
      apurimac: "Apurímac",
      huancavelica: "Huancavelica",
      amazonas: "Amazonas",
      pasco: "Pasco",
      tumbes: "Tumbes",
      "madre de dios": "Madre de Dios",
    };

    // Buscar coincidencia exacta
    if (departmentMap[normalized]) {
      return departmentMap[normalized];
    }

    // Buscar coincidencia parcial (contiene)
    for (const [key, value] of Object.entries(departmentMap)) {
      if (normalized.includes(key) || key.includes(normalized)) {
        return value;
      }
    }

    // Si no encuentra coincidencia, devolver el nombre capitalizado
    return name.charAt(0).toUpperCase() + name.slice(1).toLowerCase();
  }

  geoBtn?.addEventListener("click", (e) => {
    e.preventDefault();

    if (!("geolocation" in navigator)) {
      if (geoStatus) {
        geoStatus.textContent = "Tu navegador no soporta ubicación.";
      }
      return;
    }

    if (geoStatus) {
      geoStatus.textContent = "Obteniendo ubicación...";
    }

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        if (latInput) latInput.value = String(lat);
        if (lngInput) lngInput.value = String(lng);

        try {
          const data = await reverseGeocode(lat, lng);
          const state =
            data?.address?.state ||
            data?.address?.region ||
            data?.address?.county ||
            "";
          const dept = normalizeDepartment(state);

          console.log("Geocode response:", data.address); // Debug
          console.log("Departamento detectado:", state); // Debug
          console.log("Departamento normalizado:", dept); // Debug
          console.log("Departamentos disponibles:", Object.keys(DATA)); // Debug

          // Setear departamento si existe en DATA
          if (dept && DATA[dept]) {
            if (depSelect) {
              depSelect.value = dept;
              // Disparar evento change para llenar tiendas
              const event = new Event("change", { bubbles: true });
              depSelect.dispatchEvent(event);
            }
            if (geoStatus) {
              geoStatus.textContent = `✓ ${dept} detectado`;
            }
          } else {
            if (geoStatus) {
              geoStatus.textContent = state
                ? `Ubicación: "${state}" no está en nuestra lista`
                : "No se pudo determinar tu departamento";
            }
            console.warn("Departamento no encontrado en DATA:", dept);
          }
        } catch (err) {
          console.error("Error de geolocalización:", err);
          if (geoStatus) {
            geoStatus.textContent =
              "Error al obtener ubicación. Intenta de nuevo.";
          }
        }
      },
      () => {
        if (geoStatus) {
          geoStatus.textContent = "Permiso denegado o error de ubicación.";
        }
      },
      { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
    );
  });

  // Set inicial de reglas
  applyDocRules();

  // =========================
  // MODAL TÉRMINOS Y CONDICIONES
  // =========================
  
  const modal = document.getElementById('geely-terms-modal');
  
  if (modal) {
    // Función para abrir el modal
    function openModal() {
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }
    
    // Función para cerrar el modal
    function closeModal() {
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }
    
    // Interceptar clicks en enlaces de "Política de Protección"
    document.addEventListener('click', (e) => {
      const target = e.target;
      
      // Buscar si el click es en el enlace o en un elemento hijo del enlace
      const link = target.closest('a[href="#0"]');
      
      if (link && link.textContent.includes('Política de Protección')) {
        e.preventDefault();
        openModal();
      }
    });
    
    // Cerrar al hacer click en elementos con data-modal-close
    const closeButtons = modal.querySelectorAll('[data-modal-close]');
    closeButtons.forEach(btn => {
      btn.addEventListener('click', closeModal);
    });
    
    // Cerrar con tecla ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
        closeModal();
      }
    });
  }
})();
