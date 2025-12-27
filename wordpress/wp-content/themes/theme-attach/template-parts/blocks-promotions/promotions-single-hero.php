<?php
/**
 * Bloque: Hero Single Promoción
 * 
 * Muestra el hero para el detalle de una promoción individual
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

if (!is_singular('promocion'))
  return;


// Obtener datos del post actual
$post_id = get_the_ID();
$title = get_the_title($post_id);
$excerpt = get_the_excerpt($post_id);


// Campos ACF del bloque (opcional)
$custom_title = get_field(
  'promocion_hero_title',
  $post_id
) ?: '';

$custom_subtitle = get_field(
  'promocion_hero_description',
  $post_id
) ?: '';

// Usar campos personalizados si existen, sino usar datos del post
if (empty($custom_title)) {
  $custom_title = $title;
}

if (empty($custom_subtitle)) {
  // $custom_subtitle = $excerpt;
} ?>

<section class="promotions-single-hero">
  <div class="promotions-single-hero__inner">
    <div class="promotions-single-hero__content">
      <?php if (!empty($custom_title)): ?>
        <h1 class="promotions-single-hero__title title-1 title-mobile-sm-2">
          <?= esc_html($custom_title); ?>
        </h1>
      <?php endif; ?>
      <?php if ($custom_subtitle): ?>
        <div class="promotions-single-hero__description paragraph-2 paragraph-sm-4">
          <?= esc_html($custom_subtitle); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>