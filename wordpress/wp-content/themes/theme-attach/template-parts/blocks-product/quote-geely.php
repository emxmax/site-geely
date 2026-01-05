<?php
if (!defined('ABSPATH')) exit;

/**
 * BLOQUE: COTIZA TU GEELY (Paso 1 / Paso 2 (CF7) / Paso 3 confirmación)
 * URL esperada: /cotiza/?product_id=123
 *
 * Requiere:
 * - CPT producto
 * - ACF en producto: product_models (repeater)
 * - CF7 shortcode en un campo del bloque (opcional)
 */

$title = get_field('quote_title') ?: 'COTIZA TU GEELY';
$desc  = get_field('quote_desc') ?: 'Encuentra el Geely ideal para ti y obtén una cotización al instante. Elige tu versión, completa tus datos y da el primer paso hacia tu próxima aventura.';
$cf7_shortcode = get_field('quote_cf7_shortcode') ?: '[contact-form-7 id="36e9f7a" title="Cotiza tienda"]';

// Bloque id
$block_id = !empty($block['anchor']) ? $block['anchor'] : ('mg-quote-' . ($block['id'] ?? uniqid()));
$root_selector = '#' . $block_id;

// product_id desde URL
$product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
if (!$product_id || get_post_status($product_id) !== 'publish') {
  echo '<section id="'.esc_attr($block_id).'" class="mg-quote"><div class="mg-quote__inner"><p class="mg-quote__error">No se encontró el producto a cotizar.</p></div></section>';
  return;
}

$product_title = get_the_title($product_id);

// modelos
$models = get_field('product_models', $product_id);
if (empty($models) || !is_array($models)) {
  echo '<section id="'.esc_attr($block_id).'" class="mg-quote"><div class="mg-quote__inner"><p class="mg-quote__error">Este producto no tiene versiones configuradas.</p></div></section>';
  return;
}

/** Helpers */
if (!function_exists('mg_quote_get_image')) {
  function mg_quote_get_image($img) {
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
  function mg_quote_pick_color($model) {
    $colors = $model['model_colors'] ?? [];
    if (empty($colors) || !is_array($colors)) return [null, null, null];

    // Primero el marcado como "color_image_in_card"
    foreach ($colors as $c) {
      if (!empty($c['color_image_in_card'])) {
        $d = mg_quote_get_image($c['color_image_desktop'] ?? null);
        $m = mg_quote_get_image($c['color_image_mobile'] ?? null);
        return [$c, $d, $m];
      }
    }

    // Si no, primer color con imagen
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
[$c0, $imgD0, $imgM0] = mg_quote_pick_color($first_model);
$default_hero_img = $imgD0['url'] ?? '';

?>
<section id="<?php echo esc_attr($block_id); ?>"
  class="mg-quote"
  data-product-id="<?php echo (int)$product_id; ?>"
  data-step="1"
>
  <div class="mg-quote__inner">

    <header class="mg-quote__header">
      <h1 class="mg-quote__title"><?php echo esc_html($title); ?></h1>
      <?php if (!empty($desc)) : ?>
        <p class="mg-quote__desc"><?php echo esc_html($desc); ?></p>
      <?php endif; ?>
    </header>

    <!-- Steps header -->
    <div class="mg-quote__steps">
      <div class="mg-quote__step is-active" data-step-indicator="1">
        <div class="mg-quote__stepTop">
          <span class="mg-quote__stepLabel">PASO 1</span>
          <span class="mg-quote__stepLine"></span>
        </div>
        <div class="mg-quote__stepText">Elige tu versión</div>
      </div>

      <div class="mg-quote__step" data-step-indicator="2">
        <div class="mg-quote__stepTop">
          <span class="mg-quote__stepLabel">PASO 2</span>
          <span class="mg-quote__stepLine"></span>
        </div>
        <div class="mg-quote__stepText">Completa tus datos</div>
      </div>
    </div>

    <div class="mg-quote__content">

      <!-- LEFT summary -->
      <aside class="mg-quote__left">
        <div class="mg-quote__productName"><?php echo esc_html($product_title); ?></div>

        <div class="mg-quote__carWrap">
          <img class="mg-quote__carImg" src="<?php echo esc_url($default_hero_img); ?>" alt="<?php echo esc_attr($product_title); ?>">
        </div>

        <div class="mg-quote__leftMeta">
          <div class="mg-quote__modelName" data-selected-model-name></div>
          <div class="mg-quote__modelYear" data-selected-model-year></div>

          <!-- Paso 1: todos los colores -->
          <div class="mg-quote__colorsAll" data-colors-all>
            <div class="mg-quote__colorsLabel">Colores</div>
            <div class="mg-quote__colorsDots" data-colors-dots></div>
            <div class="mg-quote__colorsName" data-colors-name></div>
          </div>

          <!-- Paso 2: solo seleccionado -->
          <div class="mg-quote__colorRow" data-selected-color-row>
            <span class="mg-quote__colorDot" data-selected-color-dot></span>
            <span class="mg-quote__colorText" data-selected-color-name></span>
          </div>
        </div>
      </aside>

      <!-- RIGHT: Step 1 / Step 2 / Step 3 -->
      <div class="mg-quote__right">

        <!-- STEP 1 -->
        <div class="mg-quote__panel is-active" data-step="1">
          <div class="mg-quote__cards" role="list">
            <?php foreach ($active_models as $idx => $m): ?>
              <?php
                $slug  = (string)($m['model_slug'] ?? ('model-'.$idx));
                $name  = (string)($m['model_name'] ?? '');
                $year  = (string)($m['model_year'] ?? '');
                $label = (string)($m['model_price_label'] ?? 'Precio desde');
                $usd   = (string)($m['model_price_usd'] ?? '');
                $loc   = (string)($m['model_price_local'] ?? '');

                // Colores para paso 1 (todos)
                $colors = $m['model_colors'] ?? [];
                $colors_payload = [];
                if (is_array($colors)) {
                  foreach ($colors as $c) {
                    $cname = (string)($c['color_name'] ?? '');
                    $chex  = (string)($c['color_hex'] ?? '#cccccc');
                    $imgD  = mg_quote_get_image($c['color_image_desktop'] ?? null);
                    $colors_payload[] = [
                      'name' => $cname,
                      'hex'  => $chex,
                      'imgD' => (string)($imgD['url'] ?? ''),
                    ];
                  }
                }

                // Imagen por defecto del card (pick)
                [$cc, $imgD, $imgM] = mg_quote_pick_color($m);
                $img = $imgD['url'] ?? '';
                $color_name = (string)($cc['color_name'] ?? '');
                $color_hex  = (string)($cc['color_hex'] ?? '#cccccc');
              ?>
              <button
                type="button"
                class="mg-quoteCard <?php echo $idx === 0 ? 'is-selected' : ''; ?>"
                data-model-card
                data-model-slug="<?php echo esc_attr($slug); ?>"
                data-model-name="<?php echo esc_attr($name); ?>"
                data-model-year="<?php echo esc_attr($year); ?>"
                data-model-price-usd="<?php echo esc_attr($usd); ?>"
                data-model-price-local="<?php echo esc_attr($loc); ?>"
                data-model-image="<?php echo esc_attr($img); ?>"
                data-color-name="<?php echo esc_attr($color_name); ?>"
                data-color-hex="<?php echo esc_attr($color_hex); ?>"
                data-model-colors="<?php echo esc_attr(wp_json_encode($colors_payload)); ?>"
              >
                <div class="mg-quoteCard__media">
                  <?php if ($img): ?>
                    <img class="mg-quoteCard__img" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
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
                  <?php if ($year): ?>
                    <div class="mg-quoteCard__yearRow">
                      <span class="mg-quoteCard__yearTag"><?php echo esc_html($year); ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <span class="mg-quoteCard__radio" aria-hidden="true"></span>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="mg-quote__actions">
            <button type="button" class="mg-quote__btn" data-next-step>Continuar</button>
          </div>
        </div>

        <!-- STEP 2 -->
        <div class="mg-quote__panel" data-step="2">
          <div class="mg-quote__formCard">
            <div class="mg-quote__formTitle">COTIZA TU GEELY</div>

            <?php if ($cf7_shortcode): ?>
              <div class="mg-quote__cf7">
                <?php echo do_shortcode($cf7_shortcode); ?>
              </div>
            <?php else: ?>
              <p class="mg-quote__error">Falta configurar el shortcode de Contact Form 7 en el bloque (quote_cf7_shortcode).</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- STEP 3 (FULL-WIDTH / FULL-BLEED) -->
        <div class="mg-quote__panel" data-step="3">
          <div class="mg-quoteConfirm" aria-live="polite">
            <div class="mg-quoteConfirm__hero">
              <img
                class="mg-quoteConfirm__heroImg"
                data-confirm-hero
                src="<?php echo esc_url($default_hero_img); ?>"
                alt="<?php echo esc_attr($product_title); ?>"
                loading="lazy"
              >
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
                  <a class="mg-quote__btn" href="<?php echo esc_url(get_permalink($product_id)); ?>">Ficha técnica</a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /right -->
    </div><!-- /content -->
  </div><!-- /inner -->

  <script>
    window.__MG_QUOTE_BLOCKS__ = window.__MG_QUOTE_BLOCKS__ || [];
    window.__MG_QUOTE_BLOCKS__.push('<?php echo esc_js($root_selector); ?>');
  </script>
</section>
