<?php
if (!defined('ABSPATH')) exit;

/**
 * ACF Repeater: slides
 * - hero_image_desktop
 * - hero_image_mobile
 * - hero_alt
 * - hero_link
 */

if (!function_exists('geely_hc_img_data')) {
  function geely_hc_img_data($value, $size = 'full') {
    $out = ['url' => '', 'w' => 0, 'h' => 0];

    if (empty($value)) return $out;

    // ACF image array
    if (is_array($value)) {
      if (!empty($value['sizes'][$size])) $out['url'] = $value['sizes'][$size];
      elseif (!empty($value['url'])) $out['url'] = $value['url'];

      if (!empty($value['width']))  $out['w'] = (int)$value['width'];
      if (!empty($value['height'])) $out['h'] = (int)$value['height'];

      if (!empty($value['ID'])) {
        $id = (int)$value['ID'];
        $src = wp_get_attachment_image_src($id, $size);
        if (is_array($src)) {
          $out['url'] = $out['url'] ?: ($src[0] ?? '');
          $out['w'] = (int)($src[1] ?? $out['w']);
          $out['h'] = (int)($src[2] ?? $out['h']);
        }
      }
      return $out;
    }

    // Attachment ID
    if (is_numeric($value)) {
      $id = (int)$value;
      $src = wp_get_attachment_image_src($id, $size);
      if (is_array($src)) {
        $out['url'] = (string)($src[0] ?? '');
        $out['w'] = (int)($src[1] ?? 0);
        $out['h'] = (int)($src[2] ?? 0);
      } else {
        $out['url'] = wp_get_attachment_image_url($id, $size) ?: '';
      }
      return $out;
    }

    // URL string
    if (is_string($value)) {
      $out['url'] = $value;
      return $out;
    }

    return $out;
  }
}

$slides = get_field('slides');
if (empty($slides) || !is_array($slides)) return;

$uid = 'hc-' . wp_unique_id();
?>

<section class="hero-carousel" id="<?php echo esc_attr($uid); ?>">
  <div class="hero-carousel__inner">
    <div class="hero-carousel__swiper swiper">
      <div class="swiper-wrapper">

        <?php foreach ($slides as $row): ?>
          <?php
          $desktop = $row['hero_image_desktop'] ?? '';
          $mobile  = $row['hero_image_mobile'] ?? '';
          $alt     = trim((string)($row['hero_alt'] ?? ''));
          $link    = $row['hero_link'] ?? '';

          $d = geely_hc_img_data($desktop, 'full');
          $m = geely_hc_img_data($mobile, 'full');

          $desktop_url = $d['url'];
          $mobile_url  = $m['url'] ?: $desktop_url;

          if (!$desktop_url) continue;

          $href = '';
          $target = '';
          $rel = '';

          if (is_array($link) && !empty($link['url'])) {
            $href = $link['url'];
            $target = !empty($link['target']) ? $link['target'] : '';
            $rel = ($target === '_blank') ? 'noopener noreferrer' : '';
          } elseif (is_string($link) && !empty($link)) {
            $href = $link;
          }

          $w = (int)($d['w'] ?: $m['w']);
          $h = (int)($d['h'] ?: $m['h']);
          ?>

          <div class="hero-carousel__slide swiper-slide">
            <div class="hero-carousel__link">
              <picture class="hero-carousel__picture">
                <?php if ($mobile_url): ?>
                  <source media="(max-width: 768px)" srcset="<?php echo esc_url($mobile_url); ?>">
                <?php endif; ?>

                <img
                  class="hero-carousel__img"
                  src="<?php echo esc_url($desktop_url); ?>"
                  alt="<?php echo esc_attr($alt); ?>"
                  loading="lazy"
                  decoding="async"
                  <?php if ($w > 0 && $h > 0): ?>
                    width="<?php echo esc_attr($w); ?>"
                    height="<?php echo esc_attr($h); ?>"
                  <?php endif; ?>
                >
              </picture>

              <?php if (!empty($href)): ?>
                <!-- Overlay link: toda la imagen clickeable -->
                <a
                  class="hero-carousel__overlay-link"
                  href="<?php echo esc_url($href); ?>"
                  <?php if (!empty($target)): ?>target="<?php echo esc_attr($target); ?>"<?php endif; ?>
                  <?php if (!empty($rel)): ?>rel="<?php echo esc_attr($rel); ?>"<?php endif; ?>
                  aria-label="<?php echo esc_attr($alt ?: 'Ir al enlace'); ?>"
                ></a>
              <?php endif; ?>
            </div>
          </div>

        <?php endforeach; ?>

      </div>

      <!-- Desktop arrows -->
      <button class="hero-carousel__nav hero-carousel__nav--prev" type="button" aria-label="Anterior">
        <span aria-hidden="true">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/arrow-before-white.png'); ?>" alt="Prev">
        </span>
      </button>
      <button class="hero-carousel__nav hero-carousel__nav--next" type="button" aria-label="Siguiente">
        <span aria-hidden="true">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/arrow-after-white.png'); ?>" alt="Next">
        </span>
      </button>

      <!-- Mobile paginations -->
      <div class="hero-carousel__pagination hero-carousel__pagination--v" aria-hidden="true"></div>
    </div>

    <div class="hero-carousel__pagination hero-carousel__pagination--h" aria-hidden="true"></div>
  </div>

  <script>
    window.__GEE_HC_INIT__ = window.__GEE_HC_INIT__ || [];
    window.__GEE_HC_INIT__.push("#<?php echo esc_js($uid); ?>");
  </script>
</section>
