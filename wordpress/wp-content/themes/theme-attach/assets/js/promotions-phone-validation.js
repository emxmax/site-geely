/**
 * Validación Frontend para campo Celular (phone) en formularios CF7
 * Requisitos:
 * - Solo números (0-9)
 * - Exactamente 9 dígitos
 * - Debe empezar con 9
 */

(function () {
  'use strict';

  // Configuración
  const CONFIG = {
    fieldName: 'phone',
    regex: /^9\d{8}$/,
    maxLength: 9,
    errorMessage: 'Ingresa un celular válido (9 dígitos y empieza con 9).',
  };

  /**
   * Sanitizar input: solo números, max 9 dígitos, primer dígito debe ser 9
   */
  function sanitizePhoneInput(input) {
    let value = input.value.replace(/\D/g, ''); // Solo dígitos

    // Si hay valor y NO empieza con 9, forzar el 9
    if (value.length > 0 && !value.startsWith('9')) {
      value = '9' + value.substring(0, CONFIG.maxLength - 1);
    }

    // Limitar a 9 dígitos
    if (value.length > CONFIG.maxLength) {
      value = value.substring(0, CONFIG.maxLength);
    }

    input.value = value;
  }

  /**
   * Validar formato de celular
   */
  function isValidPhone(value) {
    return CONFIG.regex.test(value);
  }

  /**
   * Mostrar error cerca del campo
   */
  function showError(input) {
    const wrapper = input.closest('.wpcf7-form-control-wrap');
    if (!wrapper) return;

    // Eliminar error previo
    removeError(input);

    // Crear elemento de error
    const errorElement = document.createElement('span');
    errorElement.className = 'wpcf7-not-valid-tip';
    errorElement.setAttribute('aria-hidden', 'false');
    errorElement.textContent = CONFIG.errorMessage;

    wrapper.appendChild(errorElement);
    input.setAttribute('aria-invalid', 'true');
  }

  /**
   * Remover mensaje de error
   */
  function removeError(input) {
    const wrapper = input.closest('.wpcf7-form-control-wrap');
    if (!wrapper) return;

    const errorElement = wrapper.querySelector('.wpcf7-not-valid-tip');
    if (errorElement) {
      errorElement.remove();
    }
    input.removeAttribute('aria-invalid');
  }

  /**
   * Validar antes del submit
   */
  function validateBeforeSubmit(event) {
    const form = event.target;
    const phoneInput = form.querySelector(`input[name="${CONFIG.fieldName}"]`);

    if (!phoneInput) return;

    const value = phoneInput.value.trim();

    if (!isValidPhone(value)) {
      event.preventDefault();
      showError(phoneInput);
      phoneInput.focus();
    }
  }

  /**
   * Inicializar validación
   */
  function init() {
    const cf7Forms = document.querySelectorAll('.wpcf7-form');

    cf7Forms.forEach((form) => {
      const phoneInput = form.querySelector(`input[name="${CONFIG.fieldName}"]`);

      if (!phoneInput) return;

      // Sanitizar mientras escribe
      phoneInput.addEventListener('input', function () {
        sanitizePhoneInput(this);
        removeError(this);
      });

      // Validar en blur (cuando pierde foco)
      phoneInput.addEventListener('blur', function () {
        const value = this.value.trim();
        if (value.length > 0 && !isValidPhone(value)) {
          showError(this);
        }
      });

      // Validar antes del submit
      form.addEventListener('submit', validateBeforeSubmit);
    });
  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
