/**
 * Stores Locator - Red de Atención
 * Google Maps + Filters + Geolocation + Products Carousel
 */

(function () {
  'use strict';

  // Configuración
  const CONFIG = {
    mapId: 'stores-map',
    defaultCenter: { lat: -12.0464, lng: -77.0428 }, // Lima, Perú
    defaultZoom: 12,
    markerIcon: {
      url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA0MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cGF0aCBkPSJNMjAgMEMxMC42IDAgMyA3LjYgMyAxN0MzIDI5LjUgMjAgNTAgMjAgNTBDMjAgNTAgMzcgMjkuNSAzNyAxN0MzNyA3LjYgMjkuNCA0IDIwIDBaTTIwIDIzQzE2LjcgMjMgMTQgMjAuMyAxNCAxN0MxNCAxMy43IDE2LjcgMTEgMjAgMTFDMjMuMyAxMSAyNiAxMy43IDI2IDE3QzI2IDIwLjMgMjMuMyAyMyAyMCAyM1oiIGZpbGw9IiMwMjdCRkYiLz4KPC9zdmc+',
      scaledSize: { width: 40, height: 50 },
    },
  };

  // Estado global
  let map = null;
  let markers = [];
  let infoWindow = null;
  let productsSwiper = null;

  /**
   * Inicialización principal
   */
  function init() {
    if (!document.getElementById(CONFIG.mapId)) return;

    // Esperar a que Google Maps esté cargado
    if (typeof google === 'undefined' || !google.maps) {
      loadGoogleMaps();
      return;
    }

    initMap();
    initFilters();
    initProductsCarousel();
  }

  /**
   * Cargar Google Maps API dinámicamente
   */
  function loadGoogleMaps() {
    const apiKey = STORES_LOCATOR.google_maps_api_key || '';
    
    if (!apiKey || apiKey === 'TU_API_KEY_AQUI') {
      console.error('⚠️ Google Maps API Key no configurada. Edita inc/page/assets.php');
      showMapError('Configuración de mapa pendiente. Contacta al administrador.');
      return;
    }

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=initStoresMap`;
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);

    // Callback global
    window.initStoresMap = () => {
      initMap();
      initFilters();
      initProductsCarousel();
    };
  }

  /**
   * Mostrar error en el mapa
   */
  function showMapError(message) {
    const mapContainer = document.getElementById(CONFIG.mapId);
    if (!mapContainer) return;

    mapContainer.innerHTML = `
      <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #6c757d; padding: 20px; text-align: center; font-family: var(--font-inter);">
        <div>
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" style="margin-bottom: 16px;">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#6c757d"/>
          </svg>
          <p style="margin: 0; font-size: 14px;">${message}</p>
        </div>
      </div>
    `;
  }

  /**
   * Inicializar Google Maps
   */
  function initMap() {
    const mapElement = document.getElementById(CONFIG.mapId);
    if (!mapElement) return;

    // Crear mapa
    map = new google.maps.Map(mapElement, {
      center: CONFIG.defaultCenter,
      zoom: CONFIG.defaultZoom,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
      zoomControl: true,
      styles: [
        {
          featureType: 'poi',
          elementType: 'labels',
          stylers: [{ visibility: 'off' }],
        },
      ],
    });

    // Info Window única (reutilizable)
    infoWindow = new google.maps.InfoWindow();

    // Crear markers para todas las tiendas
    createMarkers();

    // Ajustar bounds si hay markers
    if (markers.length > 0) {
      fitMapToMarkers();
    }
  }

  /**
   * Crear markers para todas las tiendas
   */
  function createMarkers() {
    const storeCards = document.querySelectorAll('.stores-locator__card');

    storeCards.forEach((card) => {
      const lat = parseFloat(card.dataset.lat);
      const lng = parseFloat(card.dataset.lng);
      const storeId = card.dataset.storeId;

      // Validar coordenadas
      if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
        console.warn(`Tienda ${storeId} sin coordenadas válidas`);
        return;
      }

      // Crear marker
      const marker = new google.maps.Marker({
        position: { lat, lng },
        map: map,
        icon: CONFIG.markerIcon,
        title: card.querySelector('.stores-locator__card-title')?.textContent || '',
        storeId: storeId,
      });

      // Click en marker: abrir info window
      marker.addListener('click', () => {
        showInfoWindow(marker, card);
        highlightStoreCard(storeId);
      });

      markers.push(marker);
    });
  }

  /**
   * Mostrar info window del marker
   */
  function showInfoWindow(marker, card) {
    const title = card.querySelector('.stores-locator__card-title')?.textContent || '';
    const address = card.querySelector('.stores-locator__card-item span')?.textContent || '';

    const content = `
      <div class="stores-locator__map-info">
        <h4>${title}</h4>
        <p>${address}</p>
      </div>
    `;

    infoWindow.setContent(content);
    infoWindow.open(map, marker);
  }

  /**
   * Ajustar mapa para mostrar todos los markers
   */
  function fitMapToMarkers(visibleMarkers = null) {
    const bounds = new google.maps.LatLngBounds();
    const markersToFit = visibleMarkers || markers;

    if (markersToFit.length === 0) {
      map.setCenter(CONFIG.defaultCenter);
      map.setZoom(CONFIG.defaultZoom);
      return;
    }

    markersToFit.forEach((marker) => {
      if (marker.getVisible()) {
        bounds.extend(marker.getPosition());
      }
    });

    map.fitBounds(bounds);

    // Evitar zoom excesivo si solo hay 1 marker
    google.maps.event.addListenerOnce(map, 'bounds_changed', () => {
      if (map.getZoom() > 15) {
        map.setZoom(15);
      }
    });
  }

  /**
   * Resaltar card de tienda
   */
  function highlightStoreCard(storeId) {
    // Remover clase activa de todas las cards
    document.querySelectorAll('.stores-locator__card').forEach((card) => {
      card.classList.remove('is-active');
    });

    // Agregar clase activa a la card seleccionada
    const activeCard = document.querySelector(`.stores-locator__card[data-store-id="${storeId}"]`);
    if (activeCard) {
      activeCard.classList.add('is-active');
      activeCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  /**
   * Inicializar filtros
   */
  function initFilters() {
    const serviceFilter = document.getElementById('stores-service-filter');
    const departmentFilter = document.getElementById('stores-department-filter');
    const useLocationCheckbox = document.getElementById('stores-use-location');

    // Event listeners
    if (serviceFilter) {
      serviceFilter.addEventListener('change', applyFilters);
    }

    if (departmentFilter) {
      departmentFilter.addEventListener('change', applyFilters);
    }

    if (useLocationCheckbox) {
      useLocationCheckbox.addEventListener('change', handleGeolocation);
    }

    // "Ver ubicación en el mapa" en cards
    document.querySelectorAll('.stores-locator__card-link[data-action="view-on-map"]').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const card = link.closest('.stores-locator__card');
        const storeId = card.dataset.storeId;
        const marker = markers.find((m) => m.storeId === storeId);

        if (marker) {
          map.setCenter(marker.getPosition());
          map.setZoom(16);
          google.maps.event.trigger(marker, 'click');
          
          // Scroll al mapa en mobile
          if (window.innerWidth <= 1023) {
            document.querySelector('.stores-locator__map-container')?.scrollIntoView({ 
              behavior: 'smooth', 
              block: 'start' 
            });
          }
        }
      });
    });
  }

  /**
   * Aplicar filtros de servicio y departamento
   */
  function applyFilters() {
    const serviceValue = document.getElementById('stores-service-filter')?.value || '';
    const departmentValue = document.getElementById('stores-department-filter')?.value || '';

    const storeCards = document.querySelectorAll('.stores-locator__card');
    const visibleMarkers = [];

    storeCards.forEach((card) => {
      const cardServices = (card.dataset.services || '').split(',').filter(Boolean);
      const cardDepartments = (card.dataset.departments || '').split(',').filter(Boolean);

      let showCard = true;

      // Filtro de servicio
      if (serviceValue && !cardServices.includes(serviceValue)) {
        showCard = false;
      }

      // Filtro de departamento
      if (departmentValue && !cardDepartments.includes(departmentValue)) {
        showCard = false;
      }

      // Mostrar/ocultar card
      card.classList.toggle('is-hidden', !showCard);

      // Mostrar/ocultar marker
      const storeId = card.dataset.storeId;
      const marker = markers.find((m) => m.storeId === storeId);
      if (marker) {
        marker.setVisible(showCard);
        if (showCard) {
          visibleMarkers.push(marker);
        }
      }
    });

    // Ajustar mapa a markers visibles
    if (map) {
      fitMapToMarkers(visibleMarkers);
    }
  }

  /**
   * Manejar geolocalización
   */
  function handleGeolocation(e) {
    const isChecked = e.target.checked;
    const departmentFilter = document.getElementById('stores-department-filter');

    if (!isChecked) {
      return;
    }

    // Verificar si el navegador soporta geolocalización
    if (!navigator.geolocation) {
      alert('Tu navegador no soporta geolocalización.');
      e.target.checked = false;
      return;
    }

    // Obtener ubicación del usuario
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;

        // Reverse geocoding para obtener departamento
        reverseGeocode(userLat, userLng, (department) => {
          if (department && departmentFilter) {
            // Buscar opción que coincida
            const options = Array.from(departmentFilter.options);
            const matchingOption = options.find((opt) => 
              opt.text.toLowerCase().includes(department.toLowerCase())
            );

            if (matchingOption) {
              departmentFilter.value = matchingOption.value;
              applyFilters();
            } else {
              console.warn('No se encontró departamento coincidente:', department);
            }
          }
        });

        // Centrar mapa en ubicación del usuario
        if (map) {
          map.setCenter({ lat: userLat, lng: userLng });
          map.setZoom(13);
        }
      },
      (error) => {
        console.error('Error de geolocalización:', error);
        alert('No se pudo obtener tu ubicación. Verifica los permisos del navegador.');
        e.target.checked = false;
      }
    );
  }

  /**
   * Reverse geocoding para obtener departamento del Perú
   */
  function reverseGeocode(lat, lng, callback) {
    if (!google || !google.maps) {
      callback(null);
      return;
    }

    const geocoder = new google.maps.Geocoder();
    const latlng = { lat, lng };

    geocoder.geocode({ location: latlng }, (results, status) => {
      if (status === 'OK' && results[0]) {
        // Buscar componente de nivel administrativo 1 (departamento)
        const addressComponents = results[0].address_components;
        const adminArea = addressComponents.find((comp) =>
          comp.types.includes('administrative_area_level_1')
        );

        const department = adminArea ? normalizeDepartment(adminArea.long_name) : null;
        callback(department);
      } else {
        callback(null);
      }
    });
  }

  /**
   * Normalizar departamento del Perú
   */
  function normalizeDepartment(location) {
    const departmentMap = {
      'amazonas': 'Amazonas',
      'áncash': 'Áncash',
      'ancash': 'Áncash',
      'apurímac': 'Apurímac',
      'apurimac': 'Apurímac',
      'arequipa': 'Arequipa',
      'ayacucho': 'Ayacucho',
      'cajamarca': 'Cajamarca',
      'callao': 'Callao',
      'cusco': 'Cusco',
      'cuzco': 'Cusco',
      'huancavelica': 'Huancavelica',
      'huánuco': 'Huánuco',
      'huanuco': 'Huánuco',
      'ica': 'Ica',
      'junín': 'Junín',
      'junin': 'Junín',
      'la libertad': 'La Libertad',
      'lambayeque': 'Lambayeque',
      'lima': 'Lima',
      'lima metropolitana': 'Lima',
      'loreto': 'Loreto',
      'madre de dios': 'Madre de Dios',
      'moquegua': 'Moquegua',
      'pasco': 'Pasco',
      'piura': 'Piura',
      'puno': 'Puno',
      'san martín': 'San Martín',
      'san martin': 'San Martín',
      'tacna': 'Tacna',
      'tumbes': 'Tumbes',
      'ucayali': 'Ucayali',
    };

    const normalized = location.toLowerCase().trim();
    return departmentMap[normalized] || location;
  }

  /**
   * Inicializar carrusel de productos (Swiper)
   */
  function initProductsCarousel() {
    const carouselElement = document.querySelector('.stores-locator__products-carousel');
    if (!carouselElement || typeof Swiper === 'undefined') return;

    productsSwiper = new Swiper('.stores-locator__products-carousel', {
      slidesPerView: 1,
      spaceBetween: 20,
      loop: false,
      navigation: {
        nextEl: '.stores-locator__carousel-next',
        prevEl: '.stores-locator__carousel-prev',
      },
      pagination: {
        el: '.stores-locator__carousel-pagination',
        clickable: true,
      },
      breakpoints: {
        640: {
          slidesPerView: 2,
          spaceBetween: 24,
        },
        1024: {
          slidesPerView: 3,
          spaceBetween: 32,
        },
      },
    });
  }

  // Auto-inicialización
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
