<?php
if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field')) {
  return;
}

if (!function_exists('mf_parse_price')) {
  function mf_parse_price($v)
  {
    if ($v === null || $v === '')
      return null;
    $s = str_replace([',', ' '], '', (string) $v);
    $s = preg_replace('/[^0-9.]/', '', $s);
    return $s === '' ? null : (float) $s;
  }
}
if (!function_exists('mf_get_image_url')) {
  function mf_get_image_url($img)
  {
    if (empty($img))
      return '';
    if (is_numeric($img)) {
      return (string) (wp_get_attachment_image_url((int) $img, 'full') ?: '');
    }
    if (is_array($img))
      return (string) ($img['url'] ?? '');
    return (string) $img;
  }
}
if (!function_exists('mf_get_selected_model_image')) {
  function mf_get_selected_model_image($model)
  {
    $colors = $model['model_colors'] ?? [];
    if (!is_array($colors))
      return '';
    foreach ($colors as $c) {
      if (!empty($c['color_image_in_card'])) {
        // para finder usaremos desktop (se ve bien en cards)
        return mf_get_image_url($c['color_image_desktop'] ?? null) ?: mf_get_image_url($c['color_image_mobile'] ?? null);
      }
    }
    return '';
  }
}
if (!function_exists('mf_pick_model_for_card')) {
  function mf_pick_model_for_card($models)
  {
    if (empty($models) || !is_array($models))
      return null;

    $candidates = [];
    foreach ($models as $m) {
      $img = mf_get_selected_model_image($m);
      if ($img)
        $candidates[] = $m;
    }
    if (empty($candidates))
      return null;

    // menor precio (USD si existe, si no Local)
    $best = null;
    $bestPrice = null;
    foreach ($candidates as $m) {
      $usd = mf_parse_price($m['model_price_usd'] ?? null);
      $loc = mf_parse_price($m['model_price_local'] ?? null);
      $price = $usd !== null ? $usd : ($loc !== null ? $loc : PHP_FLOAT_MAX);

      if ($best === null || $price < $bestPrice) {
        $best = $m;
        $bestPrice = $price;
      }
    }
    return $best ?: $candidates[0];
  }
}

$title = get_field('sl_choose_your_geely_title') ?: '';
$description = get_field('sl_choose_your_geely_description') ?: '';
$selected_category = get_field('sl_choose_your_geely_taxonomy') ?: '';
$limit = get_field('sl_choose_your_geely_limit') ?: '';


$products_carousel = [];
if (post_type_exists('producto')) {
  $posts_per_page = !empty($limit) && is_numeric($limit) ? (int) $limit : -1;

  $query_args = [
    'post_type' => 'producto',
    'posts_per_page' => $posts_per_page,
    'post_status' => 'publish',
  ];
  if (!empty($selected_category)) {
    $query_args['tax_query'] = [
      [
        'taxonomy' => 'category',
        'field' => 'term_id',
        'terms' => is_array($selected_category) ? $selected_category : [$selected_category],
      ],
    ];
  }
  // $products_query = new WP_Query([
  //   'post_type' => 'producto',
  //   'posts_per_page' => $posts_per_page,
  //   'post_status' => 'publish',
  // ]);
  $products_query = new WP_Query($query_args);

  if ($products_query->have_posts()) {
    while ($products_query->have_posts()) {
      $products_query->the_post();

      $post_id = get_the_ID();
      $models = get_field('product_models', $post_id);
      $model = mf_pick_model_for_card($models);
      if (!$model)
        continue;
      $img = mf_get_selected_model_image($model);
      if (!$img)
        continue;

      $label = (string) ($model['model_price_label'] ?? 'Precio desde');
      $usd = (string) ($model['model_price_usd'] ?? '');
      $local = (string) ($model['model_price_local'] ?? '');

      // Tipo (Gasolina / Híbrido)
      $type = (string) get_field('spec_type', $post_id);

      $link_model = get_permalink();

      $products_carousel[] = [
        'title' => get_the_title(),
        'image' => $img,
        'price_label' => $label,
        'price_usd' => $usd,
        'price_pen' => $local,
        'link_model' => $link_model,
        'link_quote' => $link_model . '#cotizar',
        'class' => 'stores-locator',
        'id' => 'producto-' . $post_id,
      ];
    }
    wp_reset_postdata();
  }
}

// Generar un UID único para el carrusel 
$uid = 'nf-producto-' . wp_unique_id();
?>
<section class="stores-locator__products">
  <div class="stores-locator__products-container">
    <h2 class="stores-locator__products-title">
      <?= esc_html($title); ?>
    </h2>
    <div class="stores-locator__products-subtitle">
      <?= wp_kses_post($description); ?>
    </div>

    <div class="stores-locator__products-carousel swiper" id="<?= esc_attr($uid); ?>">
      <div class="swiper-wrapper">
        <?php foreach ($products_carousel as $product): ?>
          <div class="stores-locator__products-slide swiper-slide">
            <?php get_template_part(
              'template-parts/partials/components/c-card-product',
              null,
              $product
            ); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php get_template_part(
      'template-parts/partials/components/c-swiper-controls',
      null,
      [
        'id' => 'controls-stores-locator',
        'class' => 'controls-stores-locator',
      ]
    ) ?>
  </div>
</section>

<script>
  (function () {
    document.addEventListener('DOMContentLoaded', function () {
      const swiperEl = document.querySelector('#<?= esc_js($uid); ?>');
      if (!swiperEl) return;
      const swiper = new Swiper(swiperEl, {
        loop: false,
        spaceBetween: 0,
        slidesPerView: 1,
        slidesPerGroup: 1,
        grid: {
          rows: 3,
          fill: 'row',
        },
        navigation: {
          nextEl: '#controls-stores-locator .c-swiper-controls__nav--next',
          prevEl: '#controls-stores-locator .c-swiper-controls__nav--prev',
        },
        pagination: {
          el: '#controls-stores-locator .c-swiper-controls__pagination',
          clickable: true,
        },
        breakpoints: {
          768: {
            spaceBetween: 32,
            slidesPerView: 2,
            slidesPerGroup: 2,
            grid: {
              rows: 1,
            },
          },
          1280: {
            spaceBetween: 32,
            slidesPerView: 3,
            slidesPerGroup: 3,
            grid: {
              rows: 1,
            },
          }
        },
      });
    });
  })();
</script>