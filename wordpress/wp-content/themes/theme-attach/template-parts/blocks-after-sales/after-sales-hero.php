<?php
/**
 * Bloque: Hero de Post-Venta
 */
if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF del bloque
$title = get_field('after_sales_hero_title');
$description = get_field('after_sales_hero_description');
$cta_primary = get_field('after_sales_hero_cta_primary');
$cta_secondary = get_field('after_sales_hero_cta_secondary');
$background_image = get_field('after_sales_hero_background_image');
$background_image_mob = get_field('after_sales_hero_background_image_mob');

// Valores por defecto
if (empty($title)) {
  $title = 'TU GEELY SIEMPRE COMO NUEVO';
}
$bg_url = '';
if ($background_image) {
  $bg_url = is_array($background_image) ? $background_image['url'] : $background_image;
} ?>
<section class="after-sales-hero">
  <div class="after-sales-hero__inner">
    <img src="<?= esc_url($bg_url) ?>" alt="Banner Hero" width="1440" height="600" class="after-sales-hero__bg" />
    <div class="after-sales-hero__content">
      <?php if (!empty($title)): ?>
        <h1 class="after-sales-hero__title title-1 title-mobile-sm-2"><?= esc_html($title); ?></h1>
      <?php endif; ?>

      <?php if (!empty($description)): ?>
        <div class="after-sales-hero__description paragraph-2 paragraph-sm-4">
          <?= wp_kses_post($description); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($cta_primary)): ?>
        <div class="after-sales-hero__actions">
          <?php if (!empty($cta_primary["url"])): ?>
            <a href="<?= esc_url($cta_primary["url"]); ?>" class="after-sales-hero__cta after-sales-hero__cta--primary">
              <?= esc_html($cta_primary["title"]); ?>
            </a>
          <?php endif; ?>

          <?php if (!empty($cta_secondary)): ?>
            <a href="<?= esc_url($cta_secondary["url"]); ?>" class="after-sales-hero__cta after-sales-hero__cta--secondary">
              <?= esc_html($cta_secondary["title"]); ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>