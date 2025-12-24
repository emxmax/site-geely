<?php
/**
 * Bloque: Contenido Single Promoción
 * 
 * Muestra imagen principal y contenido de la promoción
 */

if (!defined('ABSPATH'))
  exit;

// Obtener datos del post actual
$post_id = get_the_ID();
$content = get_the_content($post_id);
$image_id = get_post_thumbnail_id($post_id);
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
$image_alt = $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : get_the_title($post_id);

// Campos ACF opcionales del bloque
$show_featured_image = function_exists('get_field') ? get_field('show_featured_image') : true;
if ($show_featured_image === null || $show_featured_image === '') {
  $show_featured_image = true; // Por defecto mostrar imagen
}
?>

<section class="promotions-single">
  <div class="promotions-single__inner">

    <?php if ($show_featured_image && $image_url): ?>
      <div class="promotions-single__image">
        <img src="<?php echo esc_url($image_url); ?>" 
             alt="<?php echo esc_attr($image_alt); ?>"
             loading="lazy">
      </div>
    <?php endif; ?>

    <?php if ($content): ?>
      <div class="promotions-single__content paragraph-4">
        <?php echo apply_filters('the_content', $content); ?>
      </div>
    <?php endif; ?>

  </div>
</section>
