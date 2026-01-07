<?php
/**
 * Bloque: Beneficios Post-Venta
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF del bloque
$section_title = get_field('after_sales_benefits_title');
$section_subtitle = get_field('after_sales_benefits_subtitle');
$benefits = get_field('after_sales_benefits_items'); // Repeater

// Valores por defecto
if (empty($section_title)) {
  $section_title = 'VIAJA SEGURO CON GEELY';
}
?>

<section class="after-sales-benefits">
  <div class="after-sales-benefits__inner">
    <?php if ($section_title || $section_subtitle): ?>
      <div class="after-sales-benefits__header">
        <?php if ($section_title): ?>
          <h2 class="after-sales-benefits__title title-2 title-mobile-sm-3">
            <?= esc_html($section_title); ?>
          </h2>
        <?php endif; ?>

        <?php if ($section_subtitle): ?>
          <p class="after-sales-benefits__subtitle paragraph-3">
            <?= esc_html($section_subtitle); ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($benefits && is_array($benefits)): ?>
      <div class="after-sales-benefits__grid">
        <?php foreach ($benefits as $benefit):
          $benefit_icon = $benefit['icon'] ?? '';
          $benefit_title = $benefit['title'] ?? '';
          $benefit_description = $benefit['description'] ?? '';
          $benefit_image = $benefit['image'] ?? '';
          
          $icon_url = '';
          if ($benefit_icon) {
            $icon_url = is_array($benefit_icon) ? $benefit_icon['url'] : $benefit_icon;
          }
          
          $image_url = '';
          if ($benefit_image) {
            $image_url = is_array($benefit_image) ? $benefit_image['url'] : $benefit_image;
          }
        ?>
          <div class="after-sales-benefits__item">
            <?php if ($image_url): ?>
              <div class="after-sales-benefits__item-image">
                <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($benefit_title); ?>">
              </div>
            <?php endif; ?>

            <div class="after-sales-benefits__item-content">
              <?php if ($icon_url): ?>
                <div class="after-sales-benefits__item-icon">
                  <img src="<?= esc_url($icon_url); ?>" alt="<?= esc_attr($benefit_title); ?>">
                </div>
              <?php endif; ?>

              <?php if ($benefit_title): ?>
                <h3 class="after-sales-benefits__item-title">
                  <?= esc_html($benefit_title); ?>
                </h3>
              <?php endif; ?>

              <?php if ($benefit_description): ?>
                <p class="after-sales-benefits__item-description">
                  <?= esc_html($benefit_description); ?>
                </p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
