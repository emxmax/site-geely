<?php
/**
 * Bloque: Servicios Post-Venta (Carousel)
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF del bloque
$section_title = get_field('after_sales_services_title');
$section_description = get_field('after_sales_services_description');
$services = get_field('after_sales_services_items'); // Repeater

// Valores por defecto
if (empty($section_title)) {
  $section_title = 'SERVICIOS';
}

if (empty($section_description)) {
  $section_description = 'Acompaña tu estilo de vida con el confort, la tecnología y la seguridad que mereces.';
}

$carousel_id = 'after-sales-services-' . uniqid();
?>

<section class="after-sales-services">
  <div class="after-sales-services__inner">
    <?php if ($section_title || $section_description): ?>
      <div class="after-sales-services__header">
        <?php if ($section_title): ?>
          <h2 class="after-sales-services__title title-2 title-mobile-sm-3">
            <?= esc_html($section_title); ?>
          </h2>
        <?php endif; ?>

        <?php if ($section_description): ?>
          <p class="after-sales-services__description paragraph-3">
            <?= esc_html($section_description); ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($services && is_array($services) && count($services) > 0): ?>
      <div class="after-sales-services__carousel">
        <div class="swiper" id="<?= esc_attr($carousel_id); ?>">
          <div class="swiper-wrapper">
            <?php foreach ($services as $service):
              $service_image = $service['image'] ?? '';
              $service_badge = $service['badge'] ?? '';
              $service_title = $service['title'] ?? '';
              $service_description = $service['description'] ?? '';
              $service_features = $service['features'] ?? ''; // Puede ser array o string
              $service_cta_text = $service['cta_text'] ?? '';
              $service_cta_url = $service['cta_url'] ?? '';
              
              $image_url = '';
              if ($service_image) {
                $image_url = is_array($service_image) ? $service_image['url'] : $service_image;
              }
            ?>
              <div class="swiper-slide">
                <div class="after-sales-services__card">
                  <?php if ($image_url): ?>
                    <div class="after-sales-services__card-image">
                      <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($service_title); ?>">
                    </div>
                  <?php endif; ?>

                  <div class="after-sales-services__card-content">
                    <?php if ($service_badge): ?>
                      <span class="after-sales-services__card-badge">
                        <?= esc_html($service_badge); ?>
                      </span>
                    <?php endif; ?>

                    <?php if ($service_title): ?>
                      <h3 class="after-sales-services__card-title">
                        <?= esc_html($service_title); ?>
                      </h3>
                    <?php endif; ?>

                    <?php if ($service_description): ?>
                      <div class="after-sales-services__card-description">
                        <?= wp_kses_post($service_description); ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($service_features): ?>
                      <ul class="after-sales-services__card-features">
                        <?php
                        $features_array = is_array($service_features) ? $service_features : explode("\n", $service_features);
                        foreach ($features_array as $feature):
                          $feature = trim($feature);
                          if (!empty($feature)):
                        ?>
                          <li><?= esc_html($feature); ?></li>
                        <?php
                          endif;
                        endforeach;
                        ?>
                      </ul>
                    <?php endif; ?>

                    <?php if ($service_cta_text && $service_cta_url): ?>
                      <a href="<?= esc_url($service_cta_url); ?>" class="after-sales-services__card-cta">
                        <?= esc_html($service_cta_text); ?>
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Navigation -->
          <div class="swiper-button-prev"></div>
          <div class="swiper-button-next"></div>

          <!-- Pagination -->
          <div class="swiper-pagination"></div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
