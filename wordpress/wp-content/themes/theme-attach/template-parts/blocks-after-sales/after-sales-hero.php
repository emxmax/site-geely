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
$cta_primary_text = get_field('after_sales_hero_cta_primary_text');
$cta_primary_url = get_field('after_sales_hero_cta_primary_url');
$cta_secondary_text = get_field('after_sales_hero_cta_secondary_text');
$cta_secondary_url = get_field('after_sales_hero_cta_secondary_url');
$background_image = get_field('after_sales_hero_background_image');

// Valores por defecto
if (empty($title)) {
  $title = 'TU GEELY SIEMPRE COMO NUEVO';
}

if (empty($description)) {
  $description = 'Acompaña tu estilo de vida con el confort, la tecnología y la seguridad que mereces.';
}

$bg_url = '';
if ($background_image) {
  $bg_url = is_array($background_image) ? $background_image['url'] : $background_image;
}
?>

<section class="after-sales-hero" <?php if ($bg_url): ?>style="background-image: url('<?= esc_url($bg_url); ?>');"<?php endif; ?>>
  <div class="after-sales-hero__inner">
    <div class="after-sales-hero__content">
      <?php if (!empty($title)): ?>
        <h1 class="after-sales-hero__title title-1 title-mobile-sm-2"><?= esc_html($title); ?></h1>
      <?php endif; ?>

      <?php if ($description): ?>
        <div class="after-sales-hero__description paragraph-2 paragraph-sm-4">
          <?= wp_kses_post($description); ?>
        </div>
      <?php endif; ?>

      <?php if ($cta_primary_text || $cta_secondary_text): ?>
        <div class="after-sales-hero__actions">
          <?php if ($cta_primary_text && $cta_primary_url): ?>
            <a href="<?= esc_url($cta_primary_url); ?>" class="after-sales-hero__cta after-sales-hero__cta--primary">
              <?= esc_html($cta_primary_text); ?>
            </a>
          <?php endif; ?>

          <?php if ($cta_secondary_text && $cta_secondary_url): ?>
            <a href="<?= esc_url($cta_secondary_url); ?>" class="after-sales-hero__cta after-sales-hero__cta--secondary">
              <?= esc_html($cta_secondary_text); ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
