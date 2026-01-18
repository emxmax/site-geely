<?php
if (!defined('ABSPATH')) exit;

/**
 * MODELOS GEELY – BLOCK
 * - CPT: producto
 * - Muestra SOLO productos que tengan algún color marcado:
 *   product_models.model_colors.color_image_in_card = true
 * - Selección de modelo:
 *   de los modelos que tienen color seleccionado, escoge el de menor precio (USD si existe, sino Local)
 * - Si un producto NO tiene ningún color seleccionado en ningún modelo -> NO se muestra
 */

// Block fields
$title = get_field('models_title') ?: 'MODELOS GEELY';
$desc  = get_field('models_desc') ?: '';
$terms = get_field('models_categories') ?: [];

// AJUSTA a tu proyecto:
$post_type = 'producto';
$taxonomy  = 'category';

if (empty($terms)) return;

$block_id = !empty($block['anchor']) ? $block['anchor'] : ('models-geely-' . ($block['id'] ?? uniqid()));
$root_selector = '#' . $block_id;
$bg_card_url = get_stylesheet_directory_uri() . '/assets/img/fondo-producto.png';

/** -----------------------------
 * Helpers (nombres ÚNICOS para evitar "Cannot redeclare")
 * ----------------------------- */

if (!function_exists('mg_models_geely_parse_price')) {
  function mg_models_geely_parse_price($v)
  {
    if ($v === null || $v === '') return null;
    $s = (string) $v;
    $s = str_replace([',', ' '], '', $s);
    $s = preg_replace('/[^0-9.]/', '', $s);
    return $s === '' ? null : (float) $s;
  }
}

if (!function_exists('mg_models_geely_get_image')) {
  function mg_models_geely_get_image($img)
  {
    if (empty($img)) return null;

    // Attachment ID
    if (is_numeric($img)) {
      $id  = (int) $img;
      $url = wp_get_attachment_image_url($id, 'full') ?: '';
      if (!$url) {
        $src = wp_get_attachment_image_src($id, 'full');
        $url = $src ? $src[0] : '';
      }
      return [
        'url' => $url,
        'alt' => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
      ];
    }

    // ACF array
    if (is_array($img)) {
      return [
        'url' => (string) ($img['url'] ?? ''),
        'alt' => (string) ($img['alt'] ?? ''),
      ];
    }

    // URL string
    return ['url' => (string) $img, 'alt' => ''];
  }
}

if (!function_exists('mg_models_geely_get_selected_color_images')) {
  function mg_models_geely_get_selected_color_images($model)
  {
    $colors = $model['model_colors'] ?? [];
    if (empty($colors) || !is_array($colors)) return [false, null, null];

    foreach ($colors as $c) {
      if (!empty($c['color_image_in_card'])) {
        $d = mg_models_geely_get_image($c['color_image_desktop'] ?? null);
        $m = mg_models_geely_get_image($c['color_image_mobile'] ?? null);
        return [true, $d, $m];
      }
    }

    return [false, null, null];
  }
}

if (!function_exists('mg_models_geely_pick_model_for_card')) {
  function mg_models_geely_pick_model_for_card($models)
  {
    if (empty($models) || !is_array($models)) return null;

    $candidates = [];
    foreach ($models as $m) {
      [$hasSelected] = mg_models_geely_get_selected_color_images($m);
      if ($hasSelected) $candidates[] = $m;
    }

    if (empty($candidates)) return null;

    $best = null;
    $bestPrice = null;

    foreach ($candidates as $m) {
      $usd = mg_models_geely_parse_price($m['model_price_usd'] ?? null);
      $loc = mg_models_geely_parse_price($m['model_price_local'] ?? null);
      $price = $usd !== null ? $usd : ($loc !== null ? $loc : PHP_FLOAT_MAX);

      if ($best === null || $price < $bestPrice) {
        $best = $m;
        $bestPrice = $price;
      }
    }

    return $best ?: $candidates[0];
  }
}

if (!function_exists('mg_models_geely_get_cards_for_term')) {
  function mg_models_geely_get_cards_for_term($term_id, $post_type, $taxonomy)
  {
    $q = new WP_Query([
      'post_type'      => $post_type,
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'orderby'        => 'menu_order',
      'order'          => 'ASC',
      'tax_query'      => [[
        'taxonomy' => $taxonomy,
        'field'    => 'term_id',
        'terms'    => (int) $term_id,
      ]],
    ]);

    $cards = [];

    if ($q->have_posts()) {
      while ($q->have_posts()) {
        $q->the_post();
        $post_id = get_the_ID();

        $models = get_field('product_models', $post_id);
        $model  = mg_models_geely_pick_model_for_card($models);

        if (!$model) continue;

        [$hasSelected, $imgD, $imgM] = mg_models_geely_get_selected_color_images($model);
        if (!$hasSelected || empty($imgD['url'])) continue;

        $year  = (string) ($model['model_year'] ?? '');
        $label = (string) ($model['model_price_label'] ?? 'Precio desde');
        $usd   = (string) ($model['model_price_usd'] ?? '');
        $local = (string) ($model['model_price_local'] ?? '');

        $imgDesktopUrl = $imgD['url'] ?? '';
        $imgMobileUrl  = $imgM['url'] ?? '';
        if ($imgMobileUrl === '') $imgMobileUrl = $imgDesktopUrl;

        $cards[] = [
          'title' => get_the_title(),
          'url'   => get_permalink(),
          'year'  => $year,
          'label' => $label,
          'usd'   => $usd,
          'local' => $local,
          'imgD'  => $imgDesktopUrl,
          'imgM'  => $imgMobileUrl,
        ];
      }
      wp_reset_postdata();
    }

    return $cards;
  }
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="mg-models"
style="--bg-card: url('<?php echo esc_url($bg_card_url); ?>');">
  <div class="mg-models__inner">

    <header class="mg-models__header">
      <h2 class="mg-models__title"><?php echo esc_html($title); ?></h2>
      <?php if (!empty($desc)) : ?>
        <p class="mg-models__desc"><?php echo esc_html($desc); ?></p>
      <?php endif; ?>
    </header>

    <div class="mg-models__tabs" role="tablist" aria-label="Categorías de modelos">
      <?php foreach ($terms as $i => $t) : ?>
        <?php
        $term_id  = is_object($t) ? $t->term_id : (int) $t;
        $term_obj = is_object($t) ? $t : get_term($term_id);
        $slug     = $term_obj ? $term_obj->slug : ('term-' . $term_id);
        $active   = $i === 0;
        ?>
        <button
          type="button"
          class="mg-models__tab <?php echo $active ? 'is-active' : ''; ?>"
          data-mg-tab="<?php echo esc_attr($slug); ?>"
          role="tab"
          aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
          <?php echo esc_html($term_obj ? $term_obj->name : 'Categoría'); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="mg-models__panels">
      <?php foreach ($terms as $i => $t) : ?>
        <?php
        $term_id  = is_object($t) ? $t->term_id : (int) $t;
        $term_obj = is_object($t) ? $t : get_term($term_id);
        $slug     = $term_obj ? $term_obj->slug : ('term-' . $term_id);
        $active   = $i === 0;

        $cards = mg_models_geely_get_cards_for_term($term_id, $post_type, $taxonomy);

        // Desktop: 4 por slide (2x2)
        $desktopSlides = array_chunk($cards, 4);

        // Mobile: 4 por slide (1 columna x 4 cards)
        $mobileSlides = array_chunk($cards, 4);
        ?>

        <div class="mg-models__panel <?php echo $active ? 'is-active' : ''; ?>" data-mg-panel="<?php echo esc_attr($slug); ?>" role="tabpanel">

          <?php if (empty($cards)) : ?>
            <p class="mg-models__empty">No hay modelos para esta categoría.</p>
          <?php else : ?>

            <!-- Desktop -->
            <div class="mg-models__swiper-wrap mg-models__swiper-wrap--desktop">
              <div class="swiper mg-models__swiper js-mg-swiper-desktop">
                <div class="swiper-wrapper">
                  <?php foreach ($desktopSlides as $group) : ?>
                    <div class="swiper-slide">
                      <div class="mg-models__grid">
                        <?php foreach ($group as $c) : ?>
                          <a class="mg-card" href="<?php echo esc_url($c['url']); ?>">
                            <div class="mg-card__media">

                              <div class="mg-card__meta">
                                <?php if (!empty($c['year'])) : ?>
                                  <div class="mg-card__year"><?php echo esc_html($c['year']); ?></div>
                                <?php endif; ?>
                                <div class="mg-card__name"><?php echo esc_html($c['title']); ?></div>
                              </div>

                              <img class="mg-card__arrow"
                                src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/arrow-card.png'); ?>"
                                alt="Arrow">

                              <picture class="mg-card__picture">
                                <source media="(max-width: 767px)" srcset="<?php echo esc_url($c['imgM']); ?>">
                                <img class="mg-card__img" src="<?php echo esc_url($c['imgD']); ?>" alt="<?php echo esc_attr($c['title']); ?>" loading="lazy">
                              </picture>

                            </div>

                            <div class="mg-card__body">
                              <div class="mg-card__priceLabel"><?php echo esc_html($c['label']); ?></div>
                              <div class="mg-card__prices">
                                <span class="mg-card__usd">USD <?php echo esc_html($c['usd']); ?></span>
                                <span class="mg-card__dot">o</span>
                                <span class="mg-card__local">PEN <?php echo esc_html($c['local']); ?></span>
                              </div>
                            </div>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <div class="mg-models__controls mg-models__controls--desktop">
                  <button class="mg-models__nav mg-models__nav--prev" type="button" aria-label="Anterior">
                    <img
                      src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
                      alt="Prev">
                  </button>
                  <div class="mg-models__pagination mg-models__pagination--desktop"></div>
                  <button class="mg-models__nav mg-models__nav--next" type="button" aria-label="Siguiente">
                    <img
                      src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
                      alt="Next">
                  </button>
                </div>
              </div>
            </div>

            <!-- Mobile (4 cards en columna por slide) -->
            <div class="mg-models__swiper-wrap mg-models__swiper-wrap--mobile">
              <div class="swiper mg-models__swiper js-mg-swiper-mobile">
                <div class="swiper-wrapper">
                  <?php foreach ($mobileSlides as $group) : ?>
                    <div class="swiper-slide">
                      <div class="mg-models__mcol">
                        <?php foreach ($group as $c) : ?>
                          <a class="mg-card mg-card--mobile" href="<?php echo esc_url($c['url']); ?>">
                            <div class="mg-card__media">

                              <div class="mg-card__meta">
                                <?php if (!empty($c['year'])) : ?>
                                  <div class="mg-card__year"><?php echo esc_html($c['year']); ?></div>
                                <?php endif; ?>
                                <div class="mg-card__name"><?php echo esc_html($c['title']); ?></div>
                              </div>

                              <img class="mg-card__arrow"
                                src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/arrow-card.png'); ?>"
                                alt="Arrow">

                              <picture class="mg-card__picture">
                                <source media="(max-width: 767px)" srcset="<?php echo esc_url($c['imgM']); ?>">
                                <img class="mg-card__img" src="<?php echo esc_url($c['imgD']); ?>" alt="<?php echo esc_attr($c['title']); ?>" loading="lazy">
                              </picture>

                            </div>

                            <div class="mg-card__body">
                              <div class="mg-card__priceLabel"><?php echo esc_html($c['label']); ?></div>
                              <div class="mg-card__prices">
                                <span class="mg-card__usd">USD <?php echo esc_html($c['usd']); ?></span>
                                <span class="mg-card__dot">o</span>
                                <span class="mg-card__local">PEN <?php echo esc_html($c['local']); ?></span>
                              </div>
                            </div>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <div class="mg-models__controls mg-models__controls--mobile">
                  <!-- <button class="mg-models__nav mg-models__nav--prev-m" type="button" aria-label="Anterior">
                    <img
                      src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
                      alt="Prev">
                  </button> -->

                  <div class="mg-models__pagination mg-models__pagination--mobile"></div>

                  <!-- <button class="mg-models__nav mg-models__nav--next-m" type="button" aria-label="Siguiente">
                    <img
                      src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
                      alt="Next">
                  </button> -->
                </div>
              </div>
            </div>

          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    window.__MG_MODELS_BLOCKS__ = window.__MG_MODELS_BLOCKS__ || [];
    window.__MG_MODELS_BLOCKS__.push('<?php echo esc_js($root_selector); ?>');
  </script>
</section>
