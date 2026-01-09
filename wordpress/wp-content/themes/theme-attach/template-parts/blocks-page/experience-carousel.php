<?php
if (! defined('ABSPATH')) exit;

$title    = get_field('experience_title') ?: '';
$subtitle = get_field('experience_subtitle') ?: '';
$slides   = get_field('experience_slides');

$uid = 'exp-' . uniqid();
?>

<section class="exp-carousel" id="<?php echo esc_attr($uid); ?>">
  <div class="exp-carousel__inner">

    <?php if ($title): ?>
      <h2 class="exp-carousel__title"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>

    <?php if ($subtitle): ?>
      <p class="exp-carousel__subtitle"><?php echo esc_html($subtitle); ?></p>
    <?php endif; ?>

    <div class="exp-carousel__slider-wrapper">
      <div class="swiper exp-carousel__swiper">
        <div class="swiper-wrapper">

          <?php if (!empty($slides) && is_array($slides)): ?>
            <?php foreach ($slides as $row):

              $img   = $row['slide_image'] ?? null;
              $st    = $row['slide_title'] ?? '';
              $sd    = $row['slide_description'] ?? '';
              $btext = $row['slide_button_text'] ?? 'Ver más';
              $burl  = $row['slide_button_url'] ?? '';
              $newtab = !empty($row['slide_button_new_tab']);

              $img_url = '';
              $img_alt = '';

              if (is_array($img)) {
                $img_url = $img['url'] ?? '';
                $img_alt = $img['alt'] ?? '';
              } elseif (is_numeric($img)) {
                $img_url = wp_get_attachment_image_url((int)$img, 'large');
                $img_alt = get_post_meta((int)$img, '_wp_attachment_image_alt', true);
              } elseif (is_string($img)) {
                $img_url = $img;
              }

              $target = $newtab ? '_blank' : '_self';
              $rel    = $newtab ? 'noopener noreferrer' : '';
            ?>
              <div class="swiper-slide exp-carousel__slide">
                <div class="exp-carousel__card">

                  <div class="exp-carousel__media">
                    <?php if ($img_url): ?>
                      <img class="exp-carousel__image" src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>">
                    <?php endif; ?>
                  </div>

                  <div class="exp-carousel__content">
                    <?php if ($st): ?>
                      <h3 class="exp-carousel__card-title"><?php echo esc_html($st); ?></h3>
                    <?php endif; ?>

                    <?php if ($sd): ?>
                      <p class="exp-carousel__card-desc"><?php echo esc_html($sd); ?></p>
                    <?php endif; ?>

                    <?php if ($burl): ?>
                      <a class="exp-carousel__btn"
                        href="<?php echo esc_url($burl); ?>"
                        target="<?php echo esc_attr($target); ?>"
                        rel="<?php echo esc_attr($rel); ?>">
                        <?php echo esc_html($btext ?: 'Ver más'); ?>
                      </a>
                    <?php endif; ?>
                  </div>

                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </div>

      <div class="exp-carousel__controls">
        <button class="exp-carousel__nav exp-carousel__nav--prev" type="button" aria-label="Anterior">
          <img
            src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
            alt="Prev">
        </button>
        <div class="exp-carousel__pagination"></div>
        <button class="exp-carousel__nav exp-carousel__nav--next" type="button" aria-label="Siguiente">
          <img
            src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
            alt="Next">
        </button>
      </div>
    </div>

  </div>

  <script>
    window.__EXP_CAROUSELS__ = window.__EXP_CAROUSELS__ || [];
    window.__EXP_CAROUSELS__.push("#<?php echo esc_js($uid); ?>");
  </script>
</section>