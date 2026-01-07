<?php
/**
 * Bloque: Agendar Cita Post-Venta
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF del bloque
$section_title = get_field('after_sales_appointment_title');
$section_description = get_field('after_sales_appointment_description');
$cta_text = get_field('after_sales_appointment_cta_text');
$cta_url = get_field('after_sales_appointment_cta_url');

// Valores por defecto
if (empty($section_title)) {
  $section_title = 'AGENDA TU CITA HOY';
}

if (empty($section_description)) {
  $section_description = 'Encuentra tu taller cercano y agenda el mantenimiento de tu Geely en minutos.';
}

if (empty($cta_text)) {
  $cta_text = 'Agendar cita';
}
?>

<section class="after-sales-appointment">
  <div class="after-sales-appointment__inner">
    <!-- Contenido lado izquierdo -->
    <div class="after-sales-appointment__content">
      <?php if ($section_title): ?>
        <h2 class="after-sales-appointment__title title-2 title-mobile-sm-3">
          <?= esc_html($section_title); ?>
        </h2>
      <?php endif; ?>

      <?php if ($section_description): ?>
        <p class="after-sales-appointment__description paragraph-3">
          <?= esc_html($section_description); ?>
        </p>
      <?php endif; ?>

      <?php if ($cta_text && $cta_url): ?>
        <a href="<?= esc_url($cta_url); ?>" class="after-sales-appointment__cta">
          <?= esc_html($cta_text); ?>
        </a>
      <?php endif; ?>
    </div>

    <!-- Mapa lado derecho -->
    <div class="after-sales-appointment__map">
      <div id="after-sales-appointment-map" class="after-sales-appointment__map-container">
        <!-- El mapa se inicializará con JavaScript si es necesario -->
        <!-- Por ahora dejamos un placeholder -->
        <div class="after-sales-appointment__map-placeholder">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/>
          </svg>
          <p>Mapa de ubicaciones</p>
        </div>
      </div>
    </div>
  </div>
</section>
