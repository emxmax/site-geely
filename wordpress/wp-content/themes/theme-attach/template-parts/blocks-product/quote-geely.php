<?php
if (!defined('ABSPATH')) exit;

$title = get_field('quote_title') ?: 'COTIZA TU GEELY';
$desc  = get_field('quote_desc') ?: 'Encuentra el Geely ideal para ti y obtén una cotización al instante. Elige tu versión, completa tus datos y da el primer paso hacia tu próxima aventura.';
$cf7_shortcode = get_field('quote_cf7_shortcode') ?: '[contact-form-7 id="36e9f7a" title="Cotiza tienda"]';

$block_id = !empty($block['anchor']) ? $block['anchor'] : ('mg-quote-' . ($block['id'] ?? uniqid()));
$root_selector = '#' . $block_id;

$product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
if (!$product_id || get_post_status($product_id) !== 'publish') {
  echo '<section id="' . esc_attr($block_id) . '" class="mg-quote"><div class="mg-quote__inner"><p class="mg-quote__error">No se encontró el producto a cotizar.</p></div></section>';
  return;
}

$product_title = get_the_title($product_id);
$image_url = get_stylesheet_directory_uri() . '/assets/img/fondo-cotiza.png';
$nid_marca = (int) (get_field('api_nid_marca', $product_id) ?: 0); // ACF opcional: api_nid_marca

$models = get_field('product_models', $product_id);
if (empty($models) || !is_array($models)) {
  echo '<section id="' . esc_attr($block_id) . '" class="mg-quote"><div class="mg-quote__inner"><p class="mg-quote__error">Este producto no tiene versiones configuradas.</p></div></section>';
  return;
}

/** Helpers: ACF image -> URL */
if (!function_exists('mg_quote_get_image')) {
  function mg_quote_get_image($img)
  {
    if (empty($img)) return null;

    if (is_numeric($img)) {
      $id  = (int)$img;
      $url = wp_get_attachment_image_url($id, 'full') ?: '';
      if (!$url) {
        $src = wp_get_attachment_image_src($id, 'full');
        $url = $src ? $src[0] : '';
      }
      return ['url' => $url, 'alt' => (string)get_post_meta($id, '_wp_attachment_image_alt', true)];
    }

    if (is_array($img)) {
      return ['url' => (string)($img['url'] ?? ''), 'alt' => (string)($img['alt'] ?? '')];
    }

    return ['url' => (string)$img, 'alt' => ''];
  }
}

if (!function_exists('mg_quote_pick_color')) {
  function mg_quote_pick_color($model)
  {
    $colors = $model['model_colors'] ?? [];
    if (empty($colors) || !is_array($colors)) return [null, null, null];

    foreach ($colors as $c) {
      if (!empty($c['color_image_in_card'])) {
        $d = mg_quote_get_image($c['color_image_desktop'] ?? null);
        $m = mg_quote_get_image($c['color_image_mobile'] ?? null);
        return [$c, $d, $m];
      }
    }

    foreach ($colors as $c) {
      $d = mg_quote_get_image($c['color_image_desktop'] ?? null);
      if (!empty($d['url'])) {
        $m = mg_quote_get_image($c['color_image_mobile'] ?? null);
        return [$c, $d, $m];
      }
    }

    return [null, null, null];
  }
}

/** Years helper */
if (!function_exists('mg_quote_get_years_list')) {
  function mg_quote_get_years_list($model)
  {
    $years = [];

    if (!empty($model['model_years']) && is_array($model['model_years'])) {
      foreach ($model['model_years'] as $y) {
        $y = is_array($y) ? ($y['year'] ?? '') : $y;
        $y = trim((string)$y);
        if ($y !== '') $years[] = $y;
      }
    }

    if (empty($years)) {
      $raw = (string)($model['model_year'] ?? '');
      if ($raw !== '') {
        if (preg_match_all('/\b(19|20)\d{2}\b/', $raw, $m)) {
          $years = $m[0];
        } else {
          $years = [trim($raw)];
        }
      }
    }

    $years = array_values(array_unique(array_filter($years)));
    return $years;
  }
}

// filtrar modelos activos
$active_models = [];
foreach ($models as $m) {
  if (isset($m['model_active']) && !$m['model_active']) continue;
  if (empty($m['model_name']) || empty($m['model_slug'])) continue;
  $active_models[] = $m;
}
if (empty($active_models)) $active_models = $models;

// default selection
$first_model = $active_models[0];
[$c0, $imgD0] = mg_quote_pick_color($first_model);

$first_model_img = mg_quote_get_image($first_model['model_image'] ?? ($first_model['model_image_desktop'] ?? null));
$product_thumb   = get_the_post_thumbnail_url($product_id, 'large') ?: '';

$default_hero_img = (string)(
  ($imgD0['url'] ?? '') ?: ($first_model_img['url'] ?? '') ?:
  $product_thumb
);

?>
<section id="<?php echo esc_attr($block_id); ?>"
  class="mg-quote"
  style="--quote-bg: url('<?php echo esc_url($image_url); ?>');"
  data-product-id="<?php echo (int)$product_id; ?>"
  data-nid-marca="<?php echo (int)$nid_marca; ?>"
  data-step="1">
  <div class="mg-quote__inner">

    <header class="mg-quote__header">
      <h1 class="mg-quote__title"><?php echo esc_html($title); ?></h1>
      <?php if (!empty($desc)) : ?>
        <p class="mg-quote__desc"><?php echo esc_html($desc); ?></p>
      <?php endif; ?>
    </header>

    <div class="mg-quote__tabs" role="tablist" aria-label="Pasos de cotización">
      <button class="mg-quote__tab is-active" type="button" role="tab" aria-selected="true" tabindex="0" data-step-tab="1">
        <span class="mg-quote__tabLabel">PASO 1</span>
        <span class="mg-quote__tabText">Elige tu versión</span>
      </button>

      <button class="mg-quote__tab is-disabled" type="button" role="tab" aria-selected="false" tabindex="-1" data-step-tab="2">
        <span class="mg-quote__tabLabel">PASO 2</span>
        <span class="mg-quote__tabText">Completa tus datos</span>
      </button>
    </div>

    <div class="mg-quote__content">

      <aside class="mg-quote__left">
        <div class="mg-quote__productName"><?php echo esc_html($product_title); ?></div>

        <div class="mg-quote__carWrap">
          <?php if ($default_hero_img): ?>
            <img class="mg-quote__carImg" src="<?php echo esc_url($default_hero_img); ?>" alt="<?php echo esc_attr($product_title); ?>">
          <?php endif; ?>
        </div>

        <div class="mg-quote__leftMeta">

          <div class="mg-quote__modelName" data-selected-model-name></div>

          <div class="mg-quote__colorsAll" data-colors-all>
            <div class="mg-quote__colorsLabel">Colores</div>
            <div class="mg-quote__colorsDots" data-colors-dots></div>
            <div class="mg-quote__colorsName" data-colors-name></div>
          </div>

          <div class="mg-quote__selectedRow" data-selected-row>
            <span class="mg-quote__yearText" data-selected-model-year></span>
            <div class="mg-quote__colorDot" data-selected-color-dot></div>
            <span class="mg-quote__colorText" data-selected-color-name></span>
          </div>

        </div>
      </aside>

      <div class="mg-quote__right">

        <div class="mg-quote__panel is-active" data-step="1">
          <div class="mg-quote__cards" role="list">
            <?php foreach ($active_models as $idx => $m): ?>
              <?php
              $slug  = (string)($m['model_slug'] ?? ('model-' . $idx));
              $name  = (string)($m['model_name'] ?? '');
              $label = (string)($m['model_price_label'] ?? 'Precio desde');
              $usd   = (string)($m['model_price_usd'] ?? '');
              $loc   = (string)($m['model_price_local'] ?? '');

              $nid_modelo = (int)($m['api_nid_modelo'] ?? ($m['nid_modelo'] ?? 0)); // ACF opcional por modelo: api_nid_modelo

              $years_list   = mg_quote_get_years_list($m);
              $default_year = $years_list[0] ?? (string)($m['model_year'] ?? '');

              $modelImg    = mg_quote_get_image($m['model_image'] ?? ($m['model_image_desktop'] ?? null));
              $modelImgUrl = (string)($modelImg['url'] ?? '');

              $colors = $m['model_colors'] ?? [];
              $colors_payload = [];

              if (is_array($colors)) {
                foreach ($colors as $c) {
                  $cname = (string)($c['color_name'] ?? '');
                  $chex  = trim((string)($c['color_hex'] ?? '#cccccc'));

                  if ($chex !== '' && $chex[0] !== '#' && preg_match('/^[0-9a-fA-F]{3,8}$/', $chex)) {
                    $chex = '#' . $chex;
                  }

                  $imgD  = mg_quote_get_image($c['color_image_desktop'] ?? null);

                  $colors_payload[] = [
                    'name' => $cname,
                    'hex'  => $chex,
                    'imgD' => (string)($imgD['url'] ?? ''),
                  ];
                }
              }

              [$cc, $imgD] = mg_quote_pick_color($m);
              $img = (string)(
                ($imgD['url'] ?? '') ?:
                $modelImgUrl ?:
                $product_thumb
              );

              $color_name = (string)($cc['color_name'] ?? '');
              $color_hex  = trim((string)($cc['color_hex'] ?? '#cccccc'));

              if ($color_hex !== '' && $color_hex[0] !== '#' && preg_match('/^[0-9a-fA-F]{3,8}$/', $color_hex)) {
                $color_hex = '#' . $color_hex;
              }
              ?>

              <button
                type="button"
                class="mg-quoteCard <?php echo $idx === 0 ? 'is-selected' : ''; ?>"
                data-model-card
                data-model-slug="<?php echo esc_attr($slug); ?>"
                data-model-name="<?php echo esc_attr($name); ?>"
                data-model-year="<?php echo esc_attr($default_year); ?>"
                data-model-years="<?php echo esc_attr(wp_json_encode($years_list)); ?>"
                data-model-price-usd="<?php echo esc_attr($usd); ?>"
                data-model-price-local="<?php echo esc_attr($loc); ?>"
                data-nid-modelo="<?php echo esc_attr($nid_modelo); ?>"
                data-model-image="<?php echo esc_attr($img); ?>"
                data-color-name="<?php echo esc_attr($color_name); ?>"
                data-color-hex="<?php echo esc_attr($color_hex); ?>"
                data-model-colors="<?php echo esc_attr(wp_json_encode($colors_payload)); ?>">
                <div class="mg-quoteCard__media">
                  <?php if ($img): ?>
                    <img class="mg-quoteCard__img" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
                  <?php endif; ?>

                  <?php if (!empty($years_list)): ?>
                    <div class="mg-quoteCard__years" data-years-wrap role="group" aria-label="Año">
                      <?php foreach ($years_list as $yIdx => $yVal): ?>
                        <?php
                        $yVal = (string)$yVal;
                        $input_id = 'mg-quote-year-' . sanitize_title($slug) . '-' . $yIdx . '-' . $block_id;
                        ?>
                        <label class="mg-quoteYear" for="<?php echo esc_attr($input_id); ?>">
                          <input
                            id="<?php echo esc_attr($input_id); ?>"
                            type="radio"
                            name="mg-quote-year-<?php echo esc_attr($block_id . '-' . sanitize_title($slug)); ?>"
                            value="<?php echo esc_attr($yVal); ?>"
                            data-year-radio
                            <?php echo ($yIdx === 0) ? 'checked' : ''; ?>>
                          <span><?php echo esc_html($yVal); ?></span>
                        </label>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="mg-quoteCard__info">
                  <div class="mg-quoteCard__title"><?php echo esc_html($name); ?></div>
                  <div class="mg-quoteCard__label"><?php echo esc_html($label); ?></div>
                  <div class="mg-quoteCard__price">
                    <span>USD <?php echo esc_html($usd); ?></span>
                    <span class="mg-quoteCard__dot">o</span>
                    <span>PEN <?php echo esc_html($loc); ?></span>
                  </div>
                </div>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="mg-quote__actions">
            <button type="button" class="mg-quote__btn" data-next-step>Continuar</button>
          </div>
        </div>

        <div class="mg-quote__panel" data-step="2">
          <?php if ($cf7_shortcode): ?>
            <div class="mg-quote__cf7">
              <?php echo do_shortcode($cf7_shortcode); ?>
            </div>
          <?php else: ?>
            <p class="mg-quote__error">Falta configurar el shortcode de Contact Form 7 en el bloque (quote_cf7_shortcode).</p>
          <?php endif; ?>
        </div>

        <div class="mg-quote__panel" data-step="3">
          <div class="mg-quoteConfirm" aria-live="polite">
            <div class="mg-quoteConfirm__hero">
              <img class="mg-quoteConfirm__heroImg" data-confirm-hero src="<?php echo esc_url($default_hero_img); ?>" alt="<?php echo esc_attr($product_title); ?>" loading="lazy">
            </div>

            <div class="mg-quoteConfirm__body">
              <div class="mg-quoteConfirm__inner">
                <h2 class="mg-quoteConfirm__title">RECIBIMOS TU COTIZACIÓN</h2>

                <p class="mg-quoteConfirm__subtitle">
                  <strong>¡Tu <span data-confirm-product><?php echo esc_html($product_title); ?></span> te está esperando!</strong>
                </p>

                <p class="mg-quoteConfirm__text">
                  Gracias por tu interés en el <span data-confirm-product-2><?php echo esc_html($product_title); ?></span>.
                  En breve, uno de nuestros asesores se comunicará contigo para brindarte información detallada sobre precios,
                  disponibilidad y opciones de compra.
                </p>

                <div class="mg-quoteConfirm__btns">
                  <a class="mg-quote__btn mg-quote__btn--ghost" href="<?php echo esc_url(home_url('/')); ?>">Ver modelos</a>
                  <a class="mg-quote__btn mg-quote__btn--tec" href="<?php echo esc_url(get_permalink($product_id)); ?>">Ficha técnica</a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /right -->
    </div><!-- /content -->
  </div><!-- /inner -->

  <style>
    /* Background del bloque */
    <?php echo esc_html($root_selector); ?>{
      background-image: var(--quote-bg);
      background-size: cover;
      background-position: center top;
      background-repeat: no-repeat;
    }
    /* En Step 3 ocultamos cabecera y tabs (y el resumen izquierdo) */
    <?php echo esc_html($root_selector); ?>[data-step="3"] .mg-quote__header,
    <?php echo esc_html($root_selector); ?>[data-step="3"] .mg-quote__tabs,
    <?php echo esc_html($root_selector); ?>[data-step="3"] .mg-quote__left{
      display:none !important;
    }
    <?php echo esc_html($root_selector); ?>[data-step="3"] .mg-quote__content{
      grid-template-columns: 1fr !important;
    }
  </style>

  <script>
    window.__MG_QUOTE_BLOCKS__ = window.__MG_QUOTE_BLOCKS__ || [];
    window.__MG_QUOTE_BLOCKS__.push('<?php echo esc_js($root_selector); ?>');
  </script>
</section>
