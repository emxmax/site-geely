<?php
/**
 * Bloque: Innovación que mueve
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field')) {
  return;
}

// Campos ACF del bloque
$title = get_field('innovation_title');
$description = get_field('innovation_description');
$image = get_field('innovation_image');

// Valores por defecto
if (empty($title)) {
  $title = 'INNOVACIÓN QUE MUEVE';
}

if (empty($description)) {
  $description = 'Inspírate con historias, lanzamientos y tendencias que marcan el camino.';
}

// Imagen por defecto
$image_url = get_stylesheet_directory_uri() . '/assets/img/new-innovation-background-compress.png';
$image_alt = $title;

if ($image) {
  if (is_array($image)) {
    $image_url = $image['url'] ?? $image_url;
    $image_alt = $image['alt'] ?? $title;
  } elseif (is_numeric($image)) {
    $image_url = wp_get_attachment_image_url($image, 'full') ?: $image_url;
    $image_alt = get_post_meta($image, '_wp_attachment_image_alt', true) ?: $title;
  }
}
?>

<section class="new-innovation" style="background-image: url('<?php echo esc_url($image_url); ?>');">
  <div class="new-innovation__inner">
    <div class="new-innovation__content">
      <h2 class="new-innovation__title title-3 title-mobile-sm-2"><?php echo esc_html($title); ?></h2>
      <?php if ($description): ?>
        <div class="new-innovation__description paragraph-2 paragraph-sm-4">
          <?php echo wp_kses_post($description); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>