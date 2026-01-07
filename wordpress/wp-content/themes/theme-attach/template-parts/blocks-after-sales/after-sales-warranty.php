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
$warranty_image = get_field('after_sales_warranty_image');
$warranty_features = get_field('after_sales_warranty_features'); // Repeater o textarea

// Valores por defecto
if (empty($section_title)) {
  $section_title = 'TU SEGURIDAD, NUESTRA GARANTÍA';
}

if (empty($section_description)) {
  $section_description = 'En Geely, te ofrecemos una garantía líder en la industria, con respaldo internacional, atención experta y el compromiso de cuidar tu seguridad en cada visita.';
}

$image_url = '';
if ($warranty_image) {
  $image_url = is_array($warranty_image) ? $warranty_image['url'] : $warranty_image;
}
?>

<section class="after-sales-warranty">
  <div class="after-sales-warranty__inner">
    <?php if ($image_url): ?>
      <div class="after-sales-warranty__image">
        <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($section_title); ?>">
      </div>
    <?php endif; ?>

    <div class="after-sales-warranty__content">
      <?php if ($section_title): ?>
        <h2 class="after-sales-warranty__title title-2 title-mobile-sm-3">
          <?= esc_html($section_title); ?>
        </h2>
      <?php endif; ?>

      <?php if ($section_description): ?>
        <div class="after-sales-warranty__description paragraph-3">
          <?= wp_kses_post($section_description); ?>
        </div>
      <?php endif; ?>

      <?php if ($warranty_features): ?>
        <div class="after-sales-warranty__features">
          <?php if (is_array($warranty_features) && count($warranty_features) > 0): ?>
            <!-- Si es un repeater -->
            <?php foreach ($warranty_features as $feature):
              $feature_text = $feature['text'] ?? $feature;
            ?>
              <div class="after-sales-warranty__feature">
                <svg class="after-sales-warranty__feature-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
                </svg>
                <span><?= esc_html($feature_text); ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Si es texto plano, dividir por líneas -->
            <?php
            $features_text = is_string($warranty_features) ? $warranty_features : '';
            $features_array = array_filter(explode("\n", $features_text));
            foreach ($features_array as $feature):
              $feature = trim($feature);
              if (!empty($feature)):
            ?>
              <div class="after-sales-warranty__feature">
                <svg class="after-sales-warranty__feature-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
                </svg>
                <span><?= esc_html($feature); ?></span>
              </div>
            <?php
              endif;
            endforeach;
            ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
