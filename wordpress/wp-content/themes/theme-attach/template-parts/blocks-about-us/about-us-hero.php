<?php
if (!defined('ABSPATH'))
  exit;

// Block fields
$block_hero_eyebrow = get_field('block_hero_eyebrow') ?: 'INNOVACION Y TECNOLOGIA';
$block_hero_title = get_field('block_hero_title') ?: 'GRUPO GEELY';
$block_hero_description = get_field('block_hero_description') ?: '';

$block_hero_image = get_field('block_hero_image');
$block_hero_ceo_name = get_field('block_hero_ceo_name') ?: '';

$block_hero_content_title = get_field('block_hero_content_title') ?: '';
$block_hero_content_text = get_field('block_hero_content_text') ?: '';

// Obtener URL y alt de la imagen ACF
$image_url = '';
$image_alt = $block_hero_ceo_name ?: 'Geely';

if ($block_hero_image) {
  if (is_array($block_hero_image)) {
    // Si ACF retorna array (return format: array)
    $image_url = $block_hero_image['url'] ?? '';
    $image_alt = $block_hero_image['alt'] ?? $image_alt;
  } elseif (is_numeric($block_hero_image)) {
    // Si ACF retorna ID (return format: ID)
    $image_url = wp_get_attachment_image_url($block_hero_image, 'full') ?: '';
    $image_alt = get_post_meta($block_hero_image, '_wp_attachment_image_alt', true) ?: $image_alt;
  } else {
    // Si ACF retorna URL (return format: URL)
    $image_url = $block_hero_image;
  }
}
?>

<section class="about-hero" style="background-image: url('<?= IMG . "/nosotros-hero-bg.webp" ?>')">
  <div class="about-hero__container">
    <div class="about-hero__header">
      <?php if ($block_hero_eyebrow): ?>
        <p class="about-hero__eyebrow title-4"><?= esc_html($block_hero_eyebrow); ?></p>
      <?php endif; ?>

      <?php if ($block_hero_title): ?>
        <h1 class="about-hero__title title-1 title-sm-3"><?= esc_html($block_hero_title); ?></h1>
      <?php endif; ?>

      <?php if ($block_hero_description): ?>
        <div class="about-hero__description paragraph-3 paragraph-sm-4">
          <?= wp_kses_post($block_hero_description); ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="about-hero__content">
      <div class="about-hero__image-wrapper">
        <?php if ($image_url): ?>
          <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($image_alt); ?>" class="about-hero__image">
        <?php endif; ?>

        <?php if ($block_hero_ceo_name): ?>
          <div class="about-hero__ceo">
            <?php if ($block_hero_ceo_name): ?>
              <p class="about-hero__ceo-name paragraph-3 paragraph-sm-5"><?= esc_html($block_hero_ceo_name); ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="about-hero__text-content">
        <?php if ($block_hero_content_title): ?>
          <h2 class="about-hero__content-title title-5 title-mobile-sm-4"><?= esc_html($block_hero_content_title); ?></h2>
        <?php endif; ?>

        <?php if ($block_hero_content_text): ?>
          <div class="about-hero__content-text paragraph-3 paragraph-sm-4">
            <?= wp_kses_post($block_hero_content_text); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>