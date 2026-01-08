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
$cta = get_field('after_sales_appointment_cta');
$image_map_desktop = get_field('after_sales_appointment_bg_desktop');
$image_map_mobile = get_field('after_sales_appointment_bg_mobile');

// Valores por defecto
if (empty($section_title)) {
  $section_title = 'AGENDA TU CITA HOY';
}

if (empty($section_description)) {
  $section_description = '<p>Encuentra tu taller cercano y agenda el mantenimiento de tu Geely en minutos.</p>';
}

$map_desktop_url = $image_map_desktop ? esc_url($image_map_desktop['url']) : '';
$map_mobile_url = $image_map_mobile ? esc_url($image_map_mobile['url']) : '';
?>

<section class="after-sales-appointment">
  <img src="<?= esc_url($map_desktop_url); ?>" alt="Mapa Ubicaciones"
    class="after-sales-appointment__bg after-sales-appointment__bg--desktop" />
  <div class="after-sales-appointment__inner">
    <!-- Contenido lado izquierdo -->
    <div class="after-sales-appointment__content">
      <?php if ($section_title): ?>
        <h2 class="after-sales-appointment__title title-3">
          <?= esc_html($section_title); ?>
        </h2>
      <?php endif; ?>

      <?php if ($section_description): ?>
        <div class="after-sales-appointment__description paragraph-3">
          <?= wp_kses_post($section_description); ?>
        </div>
      <?php endif; ?>

      <?php if ($cta): ?>
        <a href="<?= esc_url($cta['url']); ?>" class="after-sales-appointment__cta title-7">
          <?= esc_html($cta['title']); ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>