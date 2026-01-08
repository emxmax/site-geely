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
          <h2 class="after-sales-services__title title-2">
            <?= esc_html($section_title); ?>
          </h2>
        <?php endif; ?>

        <?php if ($section_description): ?>
          <p class="after-sales-services__description paragraph-2 paragraph-sm-4">
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
              $service_features = $service['features'] ?? ''; // repetidor de textarea
              $service_cta = $service['cta'] ?? '';

              $image_url = '';
              if ($service_image) {
                $image_url = is_array($service_image) ? $service_image['url'] : $service_image;
              } ?>
              <div class="swiper-slide">
                <div class="after-sales-services__card">
                  <?php if ($image_url): ?>
                    <div class="after-sales-services__card-image">
                      <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($service_title); ?>"
                        class="after-sales-services__card-img">
                    </div>
                  <?php endif; ?>

                  <div class="after-sales-services__card-content">
                    <div class="after-sales-services__card-content-inner">
                      <?php if ($service_badge): ?>
                        <span class="after-sales-services__card-badge title-6">
                          <?= esc_html($service_badge); ?>
                        </span>
                      <?php endif; ?>

                      <?php if ($service_title): ?>
                        <h3 class="after-sales-services__card-title title-5">
                          <?= esc_html($service_title); ?>
                        </h3>
                      <?php endif; ?>

                      <?php if ($service_description): ?>
                        <div class="after-sales-services__card-description paragraph-4">
                          <?= wp_kses_post($service_description); ?>
                        </div>
                      <?php endif; ?>

                      <?php if ($service_features && is_array($service_features)): ?>
                        <ul class="after-sales-services__card-features">
                          <?php
                          foreach ($service_features as $feature_item):
                            $feature = $feature_item['item'] ?? '';
                            $feature = trim($feature);
                            if (!empty($feature)):
                              ?>
                              <li class="after-sales-services__card-feature">
                                <img src="<?= esc_url(IMG . '/icon-postventa-servicios-list.svg') ?>" alt="Icon Check" width="24"
                                  height="24" class="after-sales-services__card-feature-icon" />
                                <div class="after-sales-services__card-feature-text paragraph-4">
                                  <?= esc_html($feature); ?>
                                </div>
                              </li>
                              <?php
                            endif;
                          endforeach;
                          ?>
                        </ul>
                      <?php endif; ?>

                      <?php if (!empty($service_cta)): ?>
                        <div class="after-sales-services__cta-wrapper">
                          <a href="<?= esc_url($service_cta["url"]); ?>" class="after-sales-services__card-cta title-7">
                            <?= esc_html($service_cta["title"]); ?>
                          </a>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php get_template_part(
          'template-parts/partials/components/c-swiper-controls',
          null,
          [
            'id' => 'controls-postventa-services',
            'class' => 'controls-postventa-services',
          ]
        ) ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
  (function () {
    document.addEventListener('DOMContentLoaded', function () {
      const swiperEl = document.querySelector('#<?= esc_js($carousel_id); ?>');
      if (!swiperEl) return;
      const swiper = new Swiper(swiperEl, {
        loop: false,
        spaceBetween: 0,
        slidesPerView: 1,
        slidesPerGroup: 1,
        navigation: {
          nextEl: '#controls-postventa-services .c-swiper-controls__nav--next',
          prevEl: '#controls-postventa-services .c-swiper-controls__nav--prev',
        },
        pagination: {
          el: '#controls-postventa-services .c-swiper-controls__pagination',
          clickable: true,
        },
      });
    });
  })();
</script>