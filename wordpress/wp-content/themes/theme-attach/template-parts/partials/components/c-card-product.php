<?php if (!defined('ABSPATH'))
  exit;
/**
 * Component: Product Card
 *
 * Args:
 * - title: (string) Nombre del producto
 * - image: (string) URL de la imagen del producto
 * - price_usd: (string) Precio en dólares (formato: "12,990")
 * - price_pen: (string) Precio en soles (formato: "46,504")
 * - link_model: (string) URL para "Ver modelo"
 * - link_quote: (string) URL para "Cotizar"
 * - class: (string) Clases CSS adicionales
 * - id: (string) ID del elemento
 */

$defaults = [
  'title' => '',
  'image' => '',
  'price_label' => 'Precio desde',
  'price_usd' => '',
  'price_pen' => '',
  'link_model' => '#',
  'link_quote' => '#',
  'class' => '',
  'id' => '',
];

$args = wp_parse_args($args ?? [], $defaults);

$title = (string) $args['title'];
$image = (string) $args['image'];
$price_label = (string) $args['price_label'];
$price_usd = (string) $args['price_usd'];
$price_pen = (string) $args['price_pen'];
$link_model = (string) $args['link_model'];
$link_quote = (string) $args['link_quote'];

$classes = trim('mf-card ' . (string) $args['class']);

$price_label = !empty($price_label) ? $price_label : 'Precio desde';
?>

<article class="<?= esc_attr($classes); ?>" <?php if ($args['id']): ?>id="<?= esc_attr($args['id']); ?>" <?php endif; ?>>
  <header class="mf-card__top">
    <h3 class="mf-card__title"><?= esc_html($title); ?></h3>
  </header>
  <?php if ($image): ?>
    <div class="mf-card__media">
      <img class="mf-card__img" src="<?= esc_url($image); ?>" alt="<?= esc_attr($title); ?>" loading="lazy"
        decoding="async" />
    </div>
  <?php endif; ?>

  <div class="mf-card__body">
    <div class="mf-card__label">
      <?= esc_html($price_label); ?>
    </div>
    <div class="mf-card__prices">
      <span class="mf-card__usd">USD <?= esc_html($price_usd); ?></span>
      <span class="mf-card__dot">o</span>
      <span class="mf-card__local">PEN <?= esc_html($price_pen); ?></span>
    </div>
  </div>

  <footer class="mf-card__actions">
    <a class="mf-btn mf-btn--ghost" href="<?= esc_url($link_model); ?>">Ver modelo</a>
    <a class="mf-btn mf-btn--solid" href="<?= esc_url($link_quote); ?>">Cotizar</a>
  </footer>
</article>