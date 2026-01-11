<?php
if (!defined('ABSPATH'))
  exit;

// Block fields
$block_social_title = get_field('block_social_title') ?: 'RESPONSABILIDAD SOCIAL EMPRESARIAL';
$block_social_image_desktop = get_field('block_social_image_desktop');
$block_social_image_mobile = get_field('block_social_image_mobile');

// Obtener URLs de las imágenes desktop y mobile
$image_desktop_url = '';
$image_mobile_url = '';
$image_alt = $block_social_title ?: 'Responsabilidad Social Empresarial Geely';

// Imagen Desktop
if ($block_social_image_desktop) {
  if (is_array($block_social_image_desktop)) {
    $image_desktop_url = $block_social_image_desktop['url'] ?? '';
    $image_alt = $block_social_image_desktop['alt'] ?? $image_alt;
  } elseif (is_numeric($block_social_image_desktop)) {
    $image_desktop_url = wp_get_attachment_image_url($block_social_image_desktop, 'full') ?: '';
    $alt_text = get_post_meta($block_social_image_desktop, '_wp_attachment_image_alt', true);
    if ($alt_text) {
      $image_alt = $alt_text;
    }
  } else {
    $image_desktop_url = $block_social_image_desktop;
  }
}

// Imagen Mobile
if ($block_social_image_mobile) {
  if (is_array($block_social_image_mobile)) {
    $image_mobile_url = $block_social_image_mobile['url'] ?? '';
  } elseif (is_numeric($block_social_image_mobile)) {
    $image_mobile_url = wp_get_attachment_image_url($block_social_image_mobile, 'full') ?: '';
  } else {
    $image_mobile_url = $block_social_image_mobile;
  }
}

// Fallback: si no hay mobile, usar desktop
if (empty($image_mobile_url) && !empty($image_desktop_url)) {
  $image_mobile_url = $image_desktop_url;
}
?>

<section class="about-social">
  <?php if ($image_desktop_url || $image_mobile_url): ?>
    <picture class="about-social__background">
      <?php if ($image_mobile_url): ?>
        <source media="(max-width: 767px)" srcset="<?php echo esc_url($image_mobile_url); ?>">
      <?php endif; ?>
      <?php if ($image_desktop_url): ?>
        <img src="<?php echo esc_url($image_desktop_url); ?>" alt="<?php echo esc_attr($image_alt); ?>"
          class="about-social__background-image">
      <?php endif; ?>
    </picture>
  <?php endif; ?>

  <div class="about-social__overlay">
    <div class="about-social__container">
      <?php if ($block_social_title): ?>
        <h2 class="about-social__title"><?php echo esc_html($block_social_title); ?></h2>
      <?php endif; ?>
    </div>
  </div>
</section>