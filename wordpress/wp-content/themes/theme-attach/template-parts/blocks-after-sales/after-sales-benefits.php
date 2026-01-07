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
$benefits = get_field('after_sales_benefits_items'); // Repeater
?>

<section class="after-sales-benefits">
  <div class="after-sales-benefits__inner">
    <div class="after-sales-benefits__header">
      <?php if ($section_title): ?>
        <h2 class="after-sales-benefits__title title-2 title-mobile-sm-3">
          <?= esc_html($section_title); ?>
        </h2>
      <?php endif; ?>
    </div>

    <?php if ($benefits && is_array($benefits)): ?>
      <div class="after-sales-benefits__grid">
        <?php foreach ($benefits as $benefit):
          $benefit_title = $benefit['title'] ?? '';
          $benefit_description = $benefit['description'] ?? '';
          $benefit_image = $benefit['image'] ?? '';

          $image_url = '';
          if ($benefit_image) {
            $image_url = is_array($benefit_image) ? $benefit_image['url'] : $benefit_image;
          }
          ?>
          <div class="after-sales-benefits__item">
            <?php if ($image_url): ?>
              <div class="after-sales-benefits__item-image">
                <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($benefit_title); ?>"
                  class="after-sales-benefits__item-img">
              </div>
            <?php endif; ?>

            <div class=" after-sales-benefits__item-content">
              <?php if ($benefit_title): ?>
                <h3 class="after-sales-benefits__item-title title-4">
                  <?= esc_html($benefit_title); ?>
                </h3>
              <?php endif; ?>

              <?php if ($benefit_description): ?>
                <p class="after-sales-benefits__item-description paragraph-4">
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