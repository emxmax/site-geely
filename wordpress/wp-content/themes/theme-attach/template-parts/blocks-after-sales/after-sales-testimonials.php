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
  <div class="after-sales-testimonials__inner">
    <?php if ($section_title || $section_description): ?>
      <div class="after-sales-testimonials__header">
        <?php if ($section_title): ?>
          <h2 class="after-sales-testimonials__title title-2 title-mobile-sm-3">
            <?= esc_html($section_title); ?>
          </h2>
        <?php endif; ?>

        <?php if ($section_description): ?>
          <p class="after-sales-testimonials__description paragraph-3">
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
              $service_image = $testimonial['service_image'] ?? '';
              
              $photo_url = '';
              if ($customer_photo) {
                $photo_url = is_array($customer_photo) ? $customer_photo['url'] : $customer_photo;
              }
              
              $service_img_url = '';
              if ($service_image) {
                $service_img_url = is_array($service_image) ? $service_image['url'] : $service_image;
              }
            ?>
              <div class="swiper-slide">
                <div class="after-sales-testimonials__card">
                  <div class="after-sales-testimonials__card-content">
                    <div class="after-sales-testimonials__card-quote-icon">
                      <svg width="40" height="32" viewBox="0 0 40 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 32V16C0 7.168 6.272 0 16 0v8c-4.8 0-8 3.2-8 8h8v16H0zm24 0V16c0-8.832 6.272-16 16-16v8c-4.8 0-8 3.2-8 8h8v16H24z" fill="currentColor"/>
                      </svg>
                    </div>

                    <?php if ($customer_quote): ?>
                      <blockquote class="after-sales-testimonials__card-quote">
                        <?= wp_kses_post($customer_quote); ?>
                      </blockquote>
                    <?php endif; ?>

                    <div class="after-sales-testimonials__card-customer">
                      <?php if ($photo_url): ?>
                        <div class="after-sales-testimonials__card-photo">
                          <img src="<?= esc_url($photo_url); ?>" alt="<?= esc_attr($customer_name); ?>">
                        </div>
                      <?php endif; ?>

                      <?php if ($customer_name): ?>
                        <p class="after-sales-testimonials__card-name">
                          <?= esc_html($customer_name); ?>
                        </p>
                      <?php endif; ?>
                    </div>
                  </div>

                  <?php if ($service_img_url): ?>
                    <div class="after-sales-testimonials__card-image">
                      <img src="<?= esc_url($service_img_url); ?>" alt="Servicio <?= esc_attr($customer_name); ?>">
                    </div>
                  <?php endif; ?>
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
