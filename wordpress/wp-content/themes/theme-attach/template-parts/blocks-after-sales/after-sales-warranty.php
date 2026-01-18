<?php
/**
 * Bloque: Garantía Post-Venta
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF del bloque
$section_title = get_field('after_sales_warranty_title');
$section_description = get_field('after_sales_warranty_description');
$warranty_image_desktop = get_field('after_sales_warranty_image_desktop');
$warranty_image_mobile = get_field('after_sales_warranty_image_mobile');

// Valores por defecto
if (empty($section_title)) {
  $section_title = 'TU SEGURIDAD, NUESTRA GARANTÍA';
}

if (empty($section_description)) {
  $section_description = 'En Geely, te ofrecemos una garantía líder en la industria, con respaldo internacional, atención experta y el compromiso de cuidar tu seguridad en cada visita.';
}

$image_url_desktop = '';
if ($warranty_image_desktop) {
  $image_url_desktop = is_array($warranty_image_desktop) ? $warranty_image_desktop['url'] : '';
}
$image_url_mobile = '';
if ($warranty_image_mobile) {
  $image_url_mobile = is_array($warranty_image_mobile) ? $warranty_image_mobile['url'] : '';
} ?>

<section class="after-sales-warranty section-xxl-full">
  <?php if ($image_url_desktop): ?>
    <img src="<?= esc_url($image_url_desktop); ?>" alt="<?= esc_attr($section_title); ?>"
      class="after-sales-warranty__bg after-sales-warranty__bg--desktop">
  <?php endif; ?>

  <?php if ($image_url_mobile): ?>
    <img src="<?= esc_url($image_url_mobile); ?>" alt="<?= esc_attr($section_title); ?>"
      class="after-sales-warranty__bg after-sales-warranty__bg--mobile">
  <?php endif; ?>

  <div class="after-sales-warranty__inner">
    <div class="after-sales-warranty__content">
      <?php if ($section_title): ?>
        <h2 class="after-sales-warranty__title title-1 title-mobile-sm-2">
          <?= esc_html($section_title); ?>
        </h2>
      <?php endif; ?>

      <?php if ($section_description): ?>
        <div class="after-sales-warranty__description paragraph-2 paragraph-sm-5">
          <?= wp_kses_post($section_description); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>