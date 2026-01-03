<?php
$defaults = [
  'id' => '',
  'class' => '',
];

$args = wp_parse_args($args ?? [], $defaults);
$classes = trim((string) $args['class']);
?>
<div class="c-swiper-controls new-featured__controls <?= esc_attr($classes) ?>" <?= $args['id'] ? 'id="' . esc_attr($args['id']) . '"' : '' ?>>
  <!-- Navegación -->
  <button class="c-swiper-controls__nav c-swiper-controls__nav--prev new-featured__nav new-featured__nav--prev"
    aria-label="Anterior">
    <img src="<?= esc_url(IMG . '/icon-arrow-right.png'); ?>" alt="Anterior" class="c-swiper-controls__icon">
  </button>
  <!-- Paginación -->
  <div class="c-swiper-controls__pagination new-featured__pagination"></div>
  <!-- Navegación -->
  <button class="c-swiper-controls__nav c-swiper-controls__nav--next new-featured__nav new-featured__nav--next"
    aria-label="Siguiente">
    <img src="<?= esc_url(IMG . '/icon-arrow-right.png'); ?>" alt="Siguiente" class="c-swiper-controls__icon">
  </button>
</div>

<style>
</style>