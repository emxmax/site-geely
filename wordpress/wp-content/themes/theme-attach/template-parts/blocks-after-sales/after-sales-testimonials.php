<?php
/**
 * Bloque: Testimonios Post-Venta
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF del bloque
$section_title = get_field('after_sales_testimonials_title');
$section_description = get_field('after_sales_testimonials_description');
$testimonials = get_field('after_sales_testimonials_items'); // Repeater

// Valores por defecto
if (empty($section_title)) {
  $section_title = 'EXPERIENCIA Y CONFIANZA';
}

if (empty($section_description)) {
  $section_description = 'Conoce la experiencia de quienes ya disfrutan de nuestros servicios.';
}

$carousel_id = 'after-sales-testimonials-' . uniqid();
?>

<section class="after-sales-testimonials">
  <img src="<?= IMG . '/bg-postventa-experiencia-confianza.webp' ?>" alt=""
    class="after-sales-testimonials__bg after-sales-testimonials__bg--desktop" alt="">
  <img src="<?= IMG . '/bg-postventa-experiencia-confianza-mobile.webp' ?>" alt=""
    class="after-sales-testimonials__bg after-sales-testimonials__bg--mobile" alt="">
  <div class="after-sales-testimonials__inner">
    <?php if ($section_title || $section_description): ?>
      <div class="after-sales-testimonials__header">
        <?php if ($section_title): ?>
          <h2 class="after-sales-testimonials__title title-2">
            <?= esc_html($section_title); ?>
          </h2>
        <?php endif; ?>

        <?php if ($section_description): ?>
          <p class="after-sales-testimonials__description paragraph-2">
            <?= esc_html($section_description); ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($testimonials && is_array($testimonials) && count($testimonials) > 0): ?>
      <div class="after-sales-testimonials__carousel">
        <div class="swiper" id="<?= esc_attr($carousel_id); ?>">
          <div class="swiper-wrapper">
            <?php foreach ($testimonials as $testimonial):
              $customer_name = $testimonial['name'] ?? '';
              $customer_photo = $testimonial['photo'] ?? '';
              $customer_quote = $testimonial['quote'] ?? '';


              $photo_url = '';
              if ($customer_photo) {
                $photo_url = is_array($customer_photo) ? $customer_photo['url'] : $customer_photo;
              }
              ?>

              <div class="swiper-slide">
                <div class="after-sales-testimonials__card">

                  <div class="after-sales-testimonials__card-content">
                    <div class="after-sales-testimonials__card-content-inner">
                      <div class="after-sales-testimonials__card-quote-icon">
                        <img src="<?= esc_attr(IMG . '/icon-postventa-quote.svg') ?>" width="48" height="48"
                          class="after-sales-testimonials__card-quote-icon-image" />
                      </div>
                      <div class="after-sales-testimonials__card-customer">

                        <?php if ($customer_name): ?>
                          <p class="after-sales-testimonials__card-name title-4">
                            <?= esc_html($customer_name); ?>
                          </p>
                        <?php endif; ?>
                      </div>
                      <?php if ($customer_quote): ?>
                        <blockquote class="after-sales-testimonials__card-quote paragraph-4">
                          <?= wp_kses_post($customer_quote); ?>
                        </blockquote>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if ($photo_url): ?>
                    <div class="after-sales-testimonials__card-photo">
                      <img src="<?= esc_url($photo_url); ?>" alt="<?= esc_attr($customer_name); ?>"
                        class="after-sales-testimonials__card-photo-image">
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php get_template_part(
          'template-parts/partials/components/c-swiper-controls',
          null,
          [
            'id' => 'controls-postventa-testimonials',
            'class' => 'controls-postventa-testimonials',
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
          nextEl: '#controls-postventa-testimonials .c-swiper-controls__nav--next',
          prevEl: '#controls-postventa-testimonials .c-swiper-controls__nav--prev',
        },
        pagination: {
          el: '#controls-postventa-testimonials .c-swiper-controls__pagination',
          clickable: true,
        },
      });
    });
  })();
</script>