<?php if (!defined('ABSPATH'))
  exit;

/**
 * Section: Carousel
 *
 * Args:
 * - id: (string) ID del section
 * - class: (string) Clases extra
 * - modifier: (string|string[]) Modificador/es BEM: 'service', 'testimonial', 'tech'
 * - uid: (string) UID único (si no, se genera)
 * - slides: (array) Lista de slides (data)
 * - slide_template: (string) template-part para render de cada slide (ej: 'template-parts/partials/components/c-slide-service')
 * - controls: (bool) mostrar controles
 */

$defaults = [
  'id' => '',
  'class' => '',
  'wrapper_class' => '',
  'slides' => [],
  'slide_template' => '',
  'controls' => true,
];

$args = wp_parse_args($args ?? [], $defaults);

$id = (string) $args['id'];
$wrapper_class = trim('s-carousel__wrapper' . ' ' . (string) $args['wrapper_class']);
$classes = trim('s-carousel' . ' ' . (string) $args['class']);
$slides = is_array($args['slides']) ? $args['slides'] : [];
$slide_template = (string) $args['slide_template'];
$uid = $id ?: 'carousel-' . wp_unique_id();

if (!$slide_template || empty($slides))
  return;
?>
<div class="<?= esc_attr($wrapper_class); ?>">
  <div class="<?= esc_attr($classes); ?>" <?= $id ? 'id="' . esc_attr($id) . '"' : '' ?>
    data-carousel="<?= esc_attr($uid); ?>">
    <div class="s-carousel__inner">

      <div class="s-carousel__swiper swiper" id="<?= esc_attr($uid); ?>">
        <div class="s-carousel__wrapper swiper-wrapper">
          <?php foreach ($slides as $slide_args): ?>
            <div class="s-carousel__slide swiper-slide">
              <?php
              get_template_part(
                $slide_template,
                null,
                is_array($slide_args) ? $slide_args : []
              ); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (!empty($args['controls'])): ?>
        <?php get_template_part(
          'template-parts/partials/components/c-swiper-controls',
          null,
          [
            'id' => 'controls-' . $uid,
            'class' => 's-carousel__controls',
          ]
        ); ?>
      <?php endif; ?>
    </div>
  </div>
</div>