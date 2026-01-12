<?php
if (!defined('ABSPATH'))
  exit;

// Block fields
$block_evolution_title = get_field('block_evolution_title') ?: 'NUESTRA EVOLUCIÓN';
$block_evolution_description = get_field('block_evolution_description') ?: '';

$block_evolution_timeline = get_field('block_evolution_timeline'); // Repeater ?>

<section class="about-evolution">
  <div class="about-evolution__container">
    <?php if ($block_evolution_title): ?>
      <h2 class="about-evolution__title title-2 title-sm-3"><?= esc_html($block_evolution_title); ?></h2>
    <?php endif; ?>

    <?php if ($block_evolution_description): ?>
      <div class="about-evolution__description paragraph-3 paragraph-sm-4">
        <?= wp_kses_post($block_evolution_description); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($block_evolution_timeline) && is_array($block_evolution_timeline)): ?>
      <!-- Timeline horizontal con años -->
      <div class="about-evolution__timeline">
        <?php foreach ($block_evolution_timeline as $index => $milestone):
          $year = $milestone['anio'] ?? '';
          $is_active = ($index === 0); // Primer año activo por defecto
          ?>
          <button class="about-evolution__year <?= $is_active ? 'is-active' : ''; ?>" data-index="<?= esc_attr($index); ?>"
            aria-label="<?= esc_attr(sprintf(__('Ver hito del año %s', 'theme-attach'), $year)); ?>">
            <span class="about-evolution__year-text title-4 title-mobile-sm-5"><?= esc_html($year); ?></span>
            <!-- <span class="about-evolution__year-dot"></span> -->
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Swiper para el contenido de cada hito -->
      <div class="about-evolution__swiper swiper">
        <div class="swiper-wrapper">
          <?php foreach ($block_evolution_timeline as $milestone):
            $year = $milestone['anio'] ?? '';
            $title = $milestone['title'] ?? '';
            $description = $milestone['description'] ?? '';
            $image = $milestone['image'] ?? null;

            // Obtener URL y alt de la imagen
            $image_url = '';
            $image_alt = $title ?: sprintf(__('Hito del año %s', 'theme-attach'), $year);

            if ($image) {
              if (is_array($image)) {
                $image_url = $image['url'] ?? '';
                $image_alt = $image['alt'] ?? $image_alt;
              } elseif (is_numeric($image)) {
                $image_url = wp_get_attachment_image_url($image, 'large') ?: '';
                $image_alt = get_post_meta($image, '_wp_attachment_image_alt', true) ?: $image_alt;
              } else {
                $image_url = $image;
              }
            }
            ?>
            <div class="swiper-slide">
              <div class="about-evolution__milestone">
                <?php if ($image_url): ?>
                  <div class="about-evolution__milestone-image-wrapper">
                    <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($image_alt); ?>"
                      class="about-evolution__milestone-image">
                  </div>
                <?php endif; ?>
                <div class="about-evolution__milestone-content-wrapper">
                  <div class="about-evolution__milestone-content">
                    <?php if ($year): ?>
                      <span class="about-evolution__milestone-year title-3 title-mobile-sm-2">
                        <?= esc_html($year); ?>
                      </span>
                    <?php endif; ?>

                    <?php if ($title): ?>
                      <h3 class="about-evolution__milestone-title title-4 title-mobile-sm-4">
                        <?= esc_html($title); ?>
                      </h3>
                    <?php endif; ?>

                    <?php if ($description): ?>
                      <div class="about-evolution__milestone-description paragraph-3 paragraph-sm-4">
                        <?= wp_kses_post($description); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>