<?php
if (!defined('ABSPATH'))
  exit;

/**
 * ACF Repeater: slides
 * - hero_image_desktop
 * - hero_image_mobile
 * - hero_alt
 * - hero_link
 */

if (!function_exists('geely_hc_img_url')) {
  function geely_hc_img_url($value, $size = 'full')
  {
    if (empty($value))
      return '';

    // ACF image array
    if (is_array($value)) {
      if (!empty($value['sizes'][$size]))
        return $value['sizes'][$size];
      if (!empty($value['url']))
        return $value['url'];
      if (!empty($value['ID']))
        return wp_get_attachment_image_url((int) $value['ID'], $size) ?: '';
      return '';
    }

    // Attachment ID
    if (is_numeric($value)) {
      return wp_get_attachment_image_url((int) $value, $size) ?: '';
    }

    // URL string
    if (is_string($value)) {
      return $value;
    }

    return '';
  }
}

$slides = get_field('slides');
if (empty($slides) || !is_array($slides))
  return;

// ID único por bloque (evita choques)
$uid = 'hc-' . wp_unique_id();
?>

<section class="hero-carousel" id="<?php echo esc_attr($uid); ?>">
  <div class="hero-carousel__inner">
    <div class="hero-carousel__swiper swiper">
      <div class="swiper-wrapper">

        <?php foreach ($slides as $row): ?>
          <?php
          $desktop = $row['hero_image_desktop'] ?? '';
          $mobile = $row['hero_image_mobile'] ?? '';
          $alt = trim((string) ($row['hero_alt'] ?? ''));
          $link = $row['hero_link'] ?? '';

          $desktop_url = geely_hc_img_url($desktop, 'full');
          $mobile_url = geely_hc_img_url($mobile, 'full');
          if (!$mobile_url)
            $mobile_url = $desktop_url;

          if (!$desktop_url)
            continue;

          $href = '';
          $target = '';
          $rel = '';
          $text = '';

          // ACF Link field o texto
          if (is_array($link) && !empty($link['url'])) {
            $href = $link['url'];
            $target = !empty($link['target']) ? $link['target'] : '';
            $rel = ($target === '_blank') ? 'noopener noreferrer' : '';
            $text = !empty($link['title']) ? $link['title'] : '';
          } elseif (is_string($link) && !empty($link)) {
            $href = $link;
          } ?>

          <div class="hero-carousel__slide swiper-slide">


            <div class="hero-carousel__link">

              <picture class="hero-carousel__picture">
                <?php if ($mobile_url): ?>
                  <source media="(max-width: 768px)" srcset="<?= esc_url($mobile_url); ?>">
                <?php endif; ?>
                <img class="hero-carousel__img" src="<?= esc_url($desktop_url); ?>" alt="<?= esc_attr($alt); ?>"
                  loading="lazy" decoding="async">
              </picture>
              <?php if (!empty($href)): ?>
                <a class="hero-carousel__a" href="<?= esc_url($href); ?>" <?php if (!empty($target)): ?>target="<?= esc_attr($target); ?>" <?php endif; ?>     <?php if (!empty($rel)): ?>rel="<?= esc_attr($rel); ?>" <?php endif; ?>>
                  <?= $text ?>
                </a>
              <?php endif; ?>
            </div>

          </div>

        <?php endforeach; ?>

      </div>

      <!-- Desktop arrows -->
      <button class="hero-carousel__nav hero-carousel__nav--prev" type="button" aria-label="Anterior">
        <span aria-hidden="true">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/arrow-before-white.png'); ?>"
            alt="Prev">
        </span>
      </button>
      <button class="hero-carousel__nav hero-carousel__nav--next" type="button" aria-label="Siguiente">
        <span aria-hidden="true">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/arrow-after-white.png'); ?>"
            alt="Prev">
        </span>
      </button>

      <!-- Mobile paginations -->
      <div class="hero-carousel__pagination hero-carousel__pagination--v" aria-hidden="true"></div>
    </div>
    <div class="hero-carousel__pagination hero-carousel__pagination--h" aria-hidden="true"></div>
  </div>
</section>