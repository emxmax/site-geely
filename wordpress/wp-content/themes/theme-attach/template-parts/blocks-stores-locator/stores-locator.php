<?php
if (!defined('ABSPATH'))
  exit;

// Verificar que ACF esté activo
if (!function_exists('get_field')) {
  return;
}

// Campos ACF del bloque (con fallbacks seguros)
$main_title = get_field('sl_main_title') ?: 'RED DE ATENCIÓN';
$main_description = get_field('sl_main_description') ?: 'Encuentra un concesionario Geely cerca de ti para recibir asistencia experta en ventas, servicio y repuestos.';
// Por si queremos ocultarlo creamos esto en ACF
$show_products_carousel = get_field('sl_show_products_carousel') ?? true;

// Verificar si el CPT 'tienda' existe
$tienda_exists = post_type_exists('tienda');

// Obtener categorías de servicio (solo si existe el CPT tienda)
$service_categories = [];
if ($tienda_exists && function_exists('theme_attach_get_terms_with_posts')) {
  $tienda_ids = get_posts([
    'post_type' => 'tienda',
    'numberposts' => -1,
    'fields' => 'ids',
    'post_status' => 'publish',
  ]);

  if (!empty($tienda_ids)) {
    $service_categories = theme_attach_get_terms_with_posts(
      'categoria_promocion',
      [
        'object_ids' => $tienda_ids,
      ]
    );
  }
}

// Obtener departamentos (solo si existe el CPT tienda)
$departments = [];
if ($tienda_exists && function_exists('theme_attach_get_terms_with_posts')) {
  $tienda_ids = get_posts([
    'post_type' => 'tienda',
    'numberposts' => -1,
    'fields' => 'ids',
    'post_status' => 'publish',
  ]);

  if (!empty($tienda_ids)) {
    $departments = theme_attach_get_terms_with_posts('departamento', [
      'object_ids' => $tienda_ids,
      'orderby' => 'name',
    ]);
  }
}

// Query inicial de tiendas (solo si existe el CPT)
$stores_query = null;
if ($tienda_exists) {
  $stores_query = new WP_Query([
    'post_type' => 'tienda',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
  ]);
}

// Obtener productos para el carrusel (CPT producto)
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
$products_carousel = [];
if ($show_products_carousel && post_type_exists('producto')) {
  $products_query = new WP_Query([
    'post_type' => 'producto',
    'posts_per_page' => -1,
    'post_status' => 'publish',
  ]);

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
<div class="stores-locator" id="stores-locator">
  <div class="stores-locator__background">
    <img src="<?= esc_url(IMG . '/bg-red-de-atencion.webp') ?>" alt="Background image of Red de Atención"
      class="stores-locator__img" width="2880" height="2072" />
    <!-- Hero Section -->
    <section class=" stores-locator__hero">
      <div class="stores-locator__hero-inner">
        <h1 class="stores-locator__hero-title title-1 title-sm-3">
          <?= esc_html($main_title); ?>
        </h1>
        <div class="stores-locator__hero-description paragraph-2 paragraph-sm-4">
          <?= wp_kses_post($main_description); ?>
        </div>
      </div>
    </section>

    <!-- Filters and Map Container -->
    <section class="stores-locator__main">
      <div class="stores-locator__container">
        <!-- Filters -->
        <div class="stores-locator__filters">
          <!-- Servicios Filter -->
          <div class="stores-locator__filter-group">
            <div class="stores-locator__select-wrapper">
              <img src="<?= esc_url(IMG . '/icon-tiendas-servicios.svg') ?>" class="stores-locator__select-icon"
                alt="Icono de servicios" width="24" height="24" />
              <select id="stores-service-filter" class="stores-locator__select"
                aria-label="<?php esc_attr_e('Filtrar por servicio', 'theme-attach'); ?>"
                style="background-image: url(<?= IMG . '/icon-tiendas-arrow-down.svg' ?>)">
                <option value=""><?php _e('Servicios', 'theme-attach'); ?></option>
                <?php if (!empty($service_categories)): ?>
                  <?php foreach ($service_categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->slug); ?>">
                      <?php echo esc_html($cat->name); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <!-- Departamento Filter -->
          <div class="stores-locator__filter-group">
            <div class="stores-locator__select-wrapper">
              <img src="<?= esc_url(IMG . '/icon-tiendas-departamentos.svg') ?>" class="stores-locator__select-icon"
                alt="Icono de departamento" width="24" height="24" />
              <select id="stores-department-filter" class="stores-locator__select"
                aria-label="<?php esc_attr_e('Filtrar por departamento', 'theme-attach'); ?>"
                style="background-image: url(<?= IMG . '/icon-tiendas-arrow-down.svg' ?>)">
                <option value=""><?php _e('Departamento', 'theme-attach'); ?></option>
                <?php if (!empty($departments)): ?>
                  <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo esc_attr($dept->slug); ?>">
                      <?php echo esc_html($dept->name); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <!-- Geolocation Checkbox -->
            <div class="stores-locator__filter-group stores-locator__filter-group--geo">
              <label class="stores-locator__geo-label">
                <input type="checkbox" id="stores-use-location" class="stores-locator__geo-checkbox">
                <span class="stores-locator__geo-text">
                  <?php _e('Usar ubicación actual', 'theme-attach'); ?>
                </span>
              </label>
            </div>
          </div>
        </div>

        <!-- Content Grid: Stores List + Map -->
        <div class="stores-locator__grid">

          <!-- Stores List (Left Column) -->
          <div class="stores-locator__list">

            <?php if ($stores_query && $stores_query->have_posts()): ?>
              <?php while ($stores_query->have_posts()):
                $stores_query->the_post(); ?>
                <?php
                // Obtener campos ACF de la tienda
                $store_id = get_the_ID();
                $store_name = get_the_title();
                $store_address = get_field('store_address', $store_id) ?: '';

                // Phone (manejar string o array)
                $store_phone_raw = get_field('store_phone', $store_id);
                $store_phone = is_array($store_phone_raw) ? ($store_phone_raw[0] ?? '') : ($store_phone_raw ?: '');

                // WhatsApp
                $store_whatsapp_group = get_field('store_whatsapp', $store_id);
                $store_whatsapp_number = is_array($store_whatsapp_group)
                  ? ($store_whatsapp_group['store_whatsapp_number'] ?? '') : '';
                $store_whatsapp_message = is_array($store_whatsapp_group)
                  ? ($store_whatsapp_group['store_whatsapp_message'] ?? '') : '';


                $store_coordinates_raw = get_field(
                  'store_coordinates',
                  $store_id
                ) ?: '';
                $coordinates = theme_attach_parse_coordinates($store_coordinates_raw);
                $store_lat = $coordinates['lat'] ?? '';
                $store_lng = $coordinates['lng'] ?? '';
                // $store_lat = get_field('store_latitude', $store_id) ?: '';
                // $store_lng = get_field('store_longitude', $store_id) ?: '';
            
                // Obtener términos de la tienda
                $store_departments = wp_get_post_terms(
                  $store_id,
                  'departamento',
                  ['fields' => 'slugs']
                );
                $store_services = wp_get_post_terms(
                  $store_id,
                  'categoria_promocion',
                  ['fields' => 'slugs']
                );

                $dept_slugs = !is_wp_error($store_departments) && is_array($store_departments) ? implode(',', $store_departments) : '';
                $service_slugs = !is_wp_error($store_services) && is_array($store_services) ? implode(',', $store_services) : '';
                ?>

                <div class="stores-locator__card" data-store-id="<?= esc_attr($store_id); ?>"
                  data-lat="<?= esc_attr($store_lat); ?>" data-lng="<?= esc_attr($store_lng); ?>"
                  data-departments="<?= esc_attr($dept_slugs); ?>" data-services="<?= esc_attr($service_slugs); ?>">

                  <a href="#" class="stores-locator__card-link stores-locator__card-link--all"
                    data-action="view-on-map"></a>
                  <!-- Category Badge -->
                  <?php
                  $primary_service = wp_get_post_terms(
                    $store_id,
                    'categoria_promocion',
                    ['number' => 1]
                  );
                  if (!empty($primary_service) && !is_wp_error($primary_service)):
                    $service_slug = $primary_service[0]->slug;
                    $bg_pills = $service_slug === "postventa" ? 'bg-red-de-atencion-tags-postventa.webp' : 'bg-red-de-atencion-tags.webp';
                    ?>
                    <div class="stores-locator__card-badge title-mob"
                      style="background-image: url(<?= esc_url(IMG . '/' . $bg_pills) ?>);">
                      <?= esc_html($primary_service[0]->name); ?>
                    </div>
                  <?php endif; ?>

                  <!-- Store Name -->
                  <h3 class="stores-locator__card-title title-7 title-mobile-sm-5">
                    <?php echo esc_html($store_name); ?>
                  </h3>

                  <!-- Store Details -->
                  <div class="stores-locator__card-details">
                    <?php if ($store_address): ?>
                      <div class="stores-locator__card-item stores-locator__card-item--address">
                        <img src="<?= esc_url(IMG . '/icon-tiendas-direccion.svg') ?>" alt="Icono Dirección" width="24"
                          height="24" class="stores-locator__card-icon" />
                        <span class="paragraph-4 paragraph-sm-5"><?php echo esc_html($store_address); ?></span>
                      </div>
                    <?php endif; ?>
                    <?php if ($store_phone): ?>
                      <div class="stores-locator__card-item stores-locator__card-item--phone">
                        <img src="<?= esc_url(IMG . '/icon-tiendas-telefono.svg') ?>" alt="Icono Teléfono" width="24"
                          height="24" class="stores-locator__card-icon" />
                        <a href="tel:+51<?php echo esc_attr(function_exists('theme_attach_sanitize_phone') ? theme_attach_sanitize_phone($store_phone) : preg_replace('/[^0-9+]/', '', $store_phone)); ?>"
                          class="paragraph-4 paragraph-sm-5">
                          <?= esc_html($store_phone); ?>
                        </a>
                      </div>
                    <?php endif; ?>

                    <?php if ($store_whatsapp_number): ?>
                      <?php
                      $whatsapp_number_clean = '+51' . trim($store_whatsapp_number);
                      $whatsapp_url = 'https://wa.me/' . $whatsapp_number_clean;
                      if (!empty($store_whatsapp_message)) {
                        $whatsapp_url .= '?text=' . rawurlencode($store_whatsapp_message);
                      }
                      ?>
                      <div class="stores-locator__card-item stores-locator__card-item--whatsapp">
                        <img src="<?= esc_url(IMG . '/icon-tiendas-wsp.svg') ?>" alt="Icono WhatsApp" width="24" height="24"
                          class="stores-locator__card-icon" />
                        <a href="<?= esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer"
                          class="paragraph-4 paragraph-sm-5">
                          <?= esc_html($store_whatsapp_number); ?>
                        </a>
                      </div>
                    <?php endif; ?>

                  </div>

                  <!-- View on Map Link -->
                  <a href="#" class="stores-locator__card-link" data-action="view-on-map">
                    <?php _e(
                      'Ver ubicación en el mapa',
                      'theme-attach'
                    ); ?>
                  </a>

                </div>
              <?php endwhile; ?>
              <?php wp_reset_postdata(); ?>
            <?php else: ?>
              <div class="stores-locator__empty">
                <?php if (!$tienda_exists): ?>
                  <p style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; margin: 0;">
                    ⚠️ El Custom Post Type <strong>"tienda"</strong> no está registrado.<br>
                    <small>Asegúrate de tener instalado el plugin que registra este CPT.</small>
                  </p>
                <?php else: ?>
                  <p><?php _e('No se encontraron tiendas publicadas.', 'theme-attach'); ?></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>

          </div>

          <!-- Google Map (Right Column) -->
          <div class="stores-locator__map-container">
            <div id="stores-map" class="stores-locator__map"></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>