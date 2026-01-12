<?php
if (!defined('ABSPATH'))
  exit;

// Block fields
$block_journey_title = get_field('block_journey_title') ?: 'UN VIAJE POR DELANTE';

$block_journey_mission_title = get_field('block_journey_mission_title') ?: 'MISIÓN';
$block_journey_mission_image = get_field('block_journey_mission_image');
$block_journey_mission_text = get_field('block_journey_mission_text') ?: '';

$block_journey_vision_title = get_field('block_journey_vision_title') ?: 'VISIÓN';
$block_journey_vision_image = get_field('block_journey_vision_image');
$block_journey_vision_text = get_field('block_journey_vision_text') ?: '';

// Obtener URL y alt de la imagen mission ACF
$mission_image_url = '';
$mission_image_alt = $block_journey_mission_title;

if ($block_journey_mission_image) {
  if (is_array($block_journey_mission_image)) {
    $mission_image_url = $block_journey_mission_image['url'] ?? '';
    $mission_image_alt = $block_journey_mission_image['alt'] ?? $mission_image_alt;
  } elseif (is_numeric($block_journey_mission_image)) {
    $mission_image_url = wp_get_attachment_image_url($block_journey_mission_image, 'large') ?: '';
    $mission_image_alt = get_post_meta($block_journey_mission_image, '_wp_attachment_image_alt', true) ?: $mission_image_alt;
  } else {
    $mission_image_url = $block_journey_mission_image;
  }
}

// Obtener URL y alt de la imagen vision ACF
$vision_image_url = '';
$vision_image_alt = $block_journey_vision_title;

if ($block_journey_vision_image) {
  if (is_array($block_journey_vision_image)) {
    $vision_image_url = $block_journey_vision_image['url'] ?? '';
    $vision_image_alt = $block_journey_vision_image['alt'] ?? $vision_image_alt;
  } elseif (is_numeric($block_journey_vision_image)) {
    $vision_image_url = wp_get_attachment_image_url($block_journey_vision_image, 'large') ?: '';
    $vision_image_alt = get_post_meta($block_journey_vision_image, '_wp_attachment_image_alt', true) ?: $vision_image_alt;
  } else {
    $vision_image_url = $block_journey_vision_image;
  }
}
?>

<section class="about-journey" style="background: url(<?= IMG . "/nosotros-mision-vision.png" ?>);">
  <div class="about-journey__container">
    <?php if ($block_journey_title): ?>
      <h2 class="about-journey__title title-2 title-mobile-sm-2"><?= esc_html($block_journey_title); ?></h2>
    <?php endif; ?>

    <div class="about-journey__grid">
      <!-- Mission -->
      <div class="about-journey__card about-journey__card--mision">
        <?php if ($mission_image_url): ?>
          <div class="about-journey__card-image-wrapper">
            <img src="<?= esc_url($mission_image_url); ?>" alt="<?= esc_attr($mission_image_alt); ?>"
              class="about-journey__card-image">
            <div class="about-journey__card-overlay">
              <h3 class="about-journey__card-title title-1 title-mobile-sm-2">
                <?= esc_html($block_journey_mission_title); ?>
              </h3>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($block_journey_mission_text): ?>
          <div class="about-journey__card-content paragraph-3 paragraph-sm-4">
            <?= wp_kses_post($block_journey_mission_text); ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Vision -->
      <div class="about-journey__card about-journey__card--vision">

        <?php if ($block_journey_vision_text): ?>
          <div class="about-journey__card-content paragraph-3 paragraph-sm-4">
            <?= wp_kses_post($block_journey_vision_text); ?>
          </div>
        <?php endif; ?>

        <?php if ($vision_image_url): ?>
          <div class="about-journey__card-image-wrapper">
            <img src="<?= esc_url($vision_image_url); ?>" alt="<?= esc_attr($vision_image_alt); ?>"
              class="about-journey__card-image">
            <div class="about-journey__card-overlay">
              <h3 class="about-journey__card-title title-1 title-mobile-sm-2">
                <?= esc_html($block_journey_vision_title); ?>
              </h3>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>