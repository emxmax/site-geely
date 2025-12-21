<?php
/**
 * Bloque: Hero de Noticias
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF del bloque
$title = get_field('news_hero_title');
$description = get_field('news_hero_description');

// Valores por defecto
if (empty($title)) {
  $title = 'GEELY NOTICIAS';
}

if (empty($description)) {
  $description = 'Conoce las últimas noticias sobre lanzamientos, tecnología, la compañía, etc';
}
?>

<section class="new-hero">
  <div class="new-hero__inner">
    <div class="new-hero__content">
      <h1 class="new-hero__title title-1"><?php echo esc_html($title); ?></h1>

      <?php if ($description): ?>
        <div class="new-hero__description paragraph-2 paragraph-sm-4">
          <?php echo wp_kses_post($description); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>