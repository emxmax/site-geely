<?php
if (!defined('ABSPATH'))
  exit;

// Block fields
$block_social_impact_eyebrow = get_field('block_social_impact_eyebrow') ?: 'BIENESTAR SOCIAL';
$block_social_impact_title = get_field('block_social_impact_title') ?: 'GEELY HOPE';
$block_social_impact_description = get_field('block_social_impact_description') ?: '';
$block_social_impact_image = get_field('block_social_impact_image');

// Obtener URL y alt de la imagen ACF
$image_url = '';
$image_alt = $block_social_impact_title ?: 'Geely Hope';

if ($block_social_impact_image) {
  if (is_array($block_social_impact_image)) {
    // Si ACF retorna array (return format: array)
    $image_url = $block_social_impact_image['url'] ?? '';
    $image_alt = $block_social_impact_image['alt'] ?? $image_alt;
  } elseif (is_numeric($block_social_impact_image)) {
    // Si ACF retorna ID (return format: ID)
    $image_url = wp_get_attachment_image_url($block_social_impact_image, 'large') ?: '';
    $image_alt = get_post_meta($block_social_impact_image, '_wp_attachment_image_alt', true) ?: $image_alt;
  } else {
    // Si ACF retorna URL (return format: URL)
    $image_url = $block_social_impact_image;
  }
} ?>

<section class="about-social-impact" style="background-image: url('<?= IMG . "/nosotros-bienestar-social.png" ?>');">
  <div class="about-social-impact__container">
    <div class="about-social-impact__content">
      <?php if ($block_social_impact_eyebrow): ?>
        <p class="about-social-impact__eyebrow title-4"><?= esc_html($block_social_impact_eyebrow); ?></p>
      <?php endif; ?>

      <?php if ($block_social_impact_title): ?>
        <h2 class="about-social-impact__title title-2"><?= esc_html($block_social_impact_title); ?></h2>
      <?php endif; ?>

      <?php if ($block_social_impact_description): ?>
        <div class="about-social-impact__description paragraph-3 paragraph-sm-4">
          <?= wp_kses_post($block_social_impact_description); ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($image_url): ?>
      <div class="about-social-impact__image-wrapper">
        <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($image_alt); ?>" class="about-social-impact__image">
      </div>
    <?php endif; ?>
  </div>
</section>