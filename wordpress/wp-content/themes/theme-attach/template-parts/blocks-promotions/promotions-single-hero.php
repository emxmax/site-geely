<?php
/**
 * Bloque: Hero Single Promoción
 * 
 * Muestra el hero para el detalle de una promoción individual
 */

if (!defined('ABSPATH'))
  exit;

// Obtener datos del post actual
$post_id = get_the_ID();
$title = get_the_title($post_id);
$excerpt = get_the_excerpt($post_id);

// Campos ACF del bloque (opcional)
$custom_title = function_exists('get_field') ? get_field('hero_title') : '';
$custom_subtitle = function_exists('get_field') ? get_field('hero_subtitle') : '';

// Usar campos personalizados si existen, sino usar datos del post
if (empty($custom_title)) {
  $custom_title = $title;
}

if (empty($custom_subtitle)) {
  $custom_subtitle = $excerpt;
}

// Imagen de fondo (puede ser de ACF o imagen destacada)
$bg_image_id = function_exists('get_field') ? get_field('hero_background_image') : '';
if (empty($bg_image_id)) {
  $bg_image_id = get_post_thumbnail_id($post_id);
}
$bg_image_url = $bg_image_id ? wp_get_attachment_image_url($bg_image_id, 'full') : '';
?>

<section class="promotions-single-hero" style="<?php echo $bg_image_url ? 'background-image: url(' . esc_url($bg_image_url) . ');' : ''; ?>">
  <div class="promotions-single-hero__overlay"></div>
  <div class="promotions-single-hero__inner">
    
    <?php if ($custom_title): ?>
      <h1 class="promotions-single-hero__title title-2">
        <?php echo esc_html($custom_title); ?>
      </h1>
    <?php endif; ?>

    <?php if ($custom_subtitle): ?>
      <div class="promotions-single-hero__subtitle paragraph-3">
        <?php echo wp_kses_post(wpautop($custom_subtitle)); ?>
      </div>
    <?php endif; ?>

  </div>
</section>
