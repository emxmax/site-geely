(function () {
  "use strict";

  // Evitar romper si no está el form
  const form = document.querySelector('.geely-form');
  if (!form) return;

  const documentType = form.querySelector('select[name="document_type"]');
  const documentNumber = form.querySelector('input[name="document_number"]');
  const phone = form.querySelector('input[name="phone"]');

  const depSelect = form.querySelector('#geely-department');
  const storeSelect = form.querySelector('#geely-store');

  const geoBtn = form.querySelector('#geely-geo-btn');
  const geoStatus = form.querySelector('#geely-geo-status');

  const latInput = form.querySelector('#geely-lat');
  const lngInput = form.querySelector('#geely-lng');

  // 1) DATA: departamentos y tiendas (EDITÁ ESTO A TU REALIDAD)
  const DATA = {
    "Lima": ["San Isidro", "Surco", "Miraflores"],
    "Arequipa": ["Cayma", "Cerro Colorado"],
    "Cusco": ["Wanchaq"],
    "Piura": ["Piura Centro"]
  };

  // 2) Inicializar selects
  function fillDepartments() {
    if (!depSelect) return;
    const departments = Object.keys(DATA).sort();
    departments.forEach((d) => {
      const opt = document.createElement('option');
      opt.value = d;
      opt.textContent = d;
      depSelect.appendChild(opt);
    });
  }

  function resetSelect(select, placeholderText) {
    if (!select) return;
    select.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = placeholderText || 'Selecciona una opción';
    select.appendChild(ph);
  }

  function fillStores(department) {
    resetSelect(storeSelect, 'Selecciona una opción');
    if (!department || !DATA[department]) return;

    DATA[department].forEach((store) => {
      const opt = document.createElement('option');
      opt.value = store;
      opt.textContent = store;
      storeSelect.appendChild(opt);
    });
  }

  fillDepartments();
  resetSelect(storeSelect, 'Selecciona una opción');

  depSelect?.addEventListener('change', (e) => {
    fillStores(e.target.value);
  });

  // 3) Validación DNI/RUC en front (UX)
  function applyDocRules() {
    const type = (documentType?.value || '').toLowerCase();

    if (!documentNumber) return;

    documentNumber.value = documentNumber.value.replace(/\D/g, '');

    if (type === 'dni') {
      documentNumber.maxLength = 8;
      documentNumber.setAttribute('minlength', '8');
      documentNumber.setAttribute('maxlength', '8');
      documentNumber.placeholder = 'Ingresa tu DNI (8 dígitos)';
    } else if (type === 'ruc') {
      documentNumber.maxLength = 11;
      documentNumber.setAttribute('minlength', '11');
      documentNumber.setAttribute('maxlength', '11');
      documentNumber.placeholder = 'Ingresa tu RUC (11 dígitos)';
    } else {
      documentNumber.removeAttribute('minlength');
      documentNumber.removeAttribute('maxlength');
      documentNumber.placeholder = 'Ingresa tus datos';
    }
  }

  documentType?.addEventListener('change', applyDocRules);
  documentNumber?.addEventListener('input', applyDocRules);

  // 4) Tel: solo dígitos (Perú suele 9 dígitos, pero no te lo fuerzo)
  phone?.addEventListener('input', () => {
    phone.value = phone.value.replace(/\D/g, '');
  });

  // 5) Geolocalización (lat/lng + reverse geocode a "state" para departamento)
  async function reverseGeocode(lat, lng) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`;
    const res = await fetch(url, {
      headers: {
        'Accept': 'application/json'
      }
    });
    if (!res.ok) throw new Error('No se pudo obtener la ubicación');
    return res.json();
  }

  function normalizeDepartment(name) {
    if (!name) return '';
    // Ajuste común: "Lima Metropolitana" -> "Lima"
    if (name.toLowerCase().includes('lima')) return 'Lima';
    return name;
  }

  geoBtn?.addEventListener('click', (e) => {
    e.preventDefault();

    if (!('geolocation' in navigator)) {
      if (geoStatus) {
        geoStatus.textContent = 'Tu navegador no soporta ubicación.';
      }
      return;
    }

    if (geoStatus) {
      geoStatus.textContent = 'Obteniendo ubicación...';
    }

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        if (latInput) latInput.value = String(lat);
        if (lngInput) lngInput.value = String(lng);

        try {
          const data = await reverseGeocode(lat, lng);
          const state = data?.address?.state || data?.address?.region || '';
          const dept = normalizeDepartment(state);

          // Setear departamento si existe en DATA
          if (dept && DATA[dept]) {
            if (depSelect) {
              depSelect.value = dept;
              fillStores(dept);
            }
            if (geoStatus) {
              geoStatus.textContent = `Listo: ${dept}`;
            }
          } else {
            if (geoStatus) {
              geoStatus.textContent = 'Ubicación detectada, pero no se pudo mapear el departamento.';
            }
          }
        } catch (err) {
          if (geoStatus) {
            geoStatus.textContent = 'No se pudo resolver el departamento.';
          }
        }
      },
      () => {
        if (geoStatus) {
          geoStatus.textContent = 'Permiso denegado o error de ubicación.';
        }
      },
      { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
    );
  });

  // Set inicial de reglas
  applyDocRules();
})();
