<?php
/**
 * Bloque: Hero Pasaportes de Servicio
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF
$block_hero_title = get_field('block_hero_title') ?: 'PASAPORTES DE SERVICIO GEELY';
$block_hero_description = get_field('block_hero_description') ?: 'Descarga y gestiona los documentos de mantenimiento de tu vehículo.';
$block_hero_image_desktop = get_field('block_hero_image_desktop');
$block_hero_image_mobile = get_field('block_hero_image_mobile');

// Procesar imagen desktop
$image_desktop_url = '';
$image_desktop_alt = 'Pasaportes de servicio Geely';

if ($block_hero_image_desktop) {
  if (is_array($block_hero_image_desktop)) {
    $image_desktop_url = $block_hero_image_desktop['url'] ?? '';
    $image_desktop_alt = $block_hero_image_desktop['alt'] ?? $image_desktop_alt;
  } elseif (is_numeric($block_hero_image_desktop)) {
    $image_desktop_url = wp_get_attachment_image_url($block_hero_image_desktop, 'full') ?: '';
    $image_desktop_alt = get_post_meta($block_hero_image_desktop, '_wp_attachment_image_alt', true) ?: $image_desktop_alt;
  } else {
    $image_desktop_url = $block_hero_image_desktop;
  }
}

// Procesar imagen mobile
$image_mobile_url = '';
$image_mobile_alt = 'Pasaportes de servicio Geely';

if ($block_hero_image_mobile) {
  if (is_array($block_hero_image_mobile)) {
    $image_mobile_url = $block_hero_image_mobile['url'] ?? '';
    $image_mobile_alt = $block_hero_image_mobile['alt'] ?? $image_mobile_alt;
  } elseif (is_numeric($block_hero_image_mobile)) {
    $image_mobile_url = wp_get_attachment_image_url($block_hero_image_mobile, 'full') ?: '';
    $image_mobile_alt = get_post_meta($block_hero_image_mobile, '_wp_attachment_image_alt', true) ?: $image_mobile_alt;
  } else {
    $image_mobile_url = $block_hero_image_mobile;
  }
}
?>

<section class="passport-hero">
  <?php if ($image_desktop_url): ?>
    <img src="<?= esc_url($image_desktop_url); ?>" alt="<?= esc_attr($image_desktop_alt); ?>"
      class="passport-hero__bg passport-hero__bg--desktop">
  <?php endif; ?>
  <?php if ($image_mobile_url): ?>
    <img src="<?= esc_url($image_mobile_url); ?>" alt="<?= esc_attr($image_mobile_alt); ?>"
      class="passport-hero__bg passport-hero__bg--mobile">
  <?php endif; ?>

  <div class="passport-hero__inner">
    <div class="passport-hero__content">
      <?php if ($block_hero_title): ?>
        <h1 class="passport-hero__title title-1">
          <?= esc_html($block_hero_title); ?>
        </h1>
      <?php endif; ?>

      <?php if ($block_hero_description): ?>
        <div class="passport-hero__description paragraph-2 paragraph-sm-5">
          <?= wp_kses_post(wpautop($block_hero_description)); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>