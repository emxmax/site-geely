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
}

$bg_url_mobile = "";
if ($background_image_mob) {
  $bg_url_mobile = is_array($background_image_mob) ? $background_image_mob['url'] : $background_image_mob;
} ?>

<section class="after-sales-hero">

  <?php if (!empty($bg_url)): ?>
    <img src="<?= esc_url($bg_url) ?>" alt="Banner Hero" width="1440" height="600"
      class="after-sales-hero__bg after-sales-hero__bg--desktop" />
  <?php endif; ?>

  <?php if (!empty($bg_url_mobile)): ?>
    <img src="<?= esc_url($bg_url_mobile) ?>" alt="Banner Hero" width="768" height="600"
      class="after-sales-hero__bg after-sales-hero__bg--mobile" />
  <?php endif; ?>

  <div class="after-sales-hero__inner">
    <div class="after-sales-hero__content">
      <div>
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
              <a href="<?= esc_url($cta_secondary["url"]); ?>"
                class="after-sales-hero__cta after-sales-hero__cta--secondary">
                <?= esc_html($cta_secondary["title"]); ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>