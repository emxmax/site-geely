<?php
/**
 * Bloque: Hero de Promociones
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Campos ACF del bloque
$title = get_field('promotions_hero_title');
$description = get_field('promotions_hero_description');

// Valores por defecto
if (empty($title)) {
  $title = 'GEELY PROMOCIONES';
}

if (empty($description)) {
  $description = 'Disfruta de nuestras Promociones de Autos Nuevos en Geely. Encuentra el carro de tus sueños hoy.';
}
?>

<section class="promotions-hero">
  <div class="promotions-hero__inner">
    <div class="promotions-hero__content">
      <h1 class="promotions-hero__title title-1"><?php echo esc_html($title); ?></h1>

      <?php if ($description): ?>
        <div class="promotions-hero__description paragraph-2 paragraph-sm-4">
          <?php echo esc_html($description); ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div class="promotions-hero__tabs">
      <button type="button" class="promotions-hero__tab promotions-hero__tab--active js-promo-tab"
        data-tab="ventas">
        <span>Ventas</span>
      </button>
      <button type="button" class="promotions-hero__tab js-promo-tab" data-tab="postventa">
        <span>Postventa</span>
      </button>
    </div>    
  </div>
</section>
