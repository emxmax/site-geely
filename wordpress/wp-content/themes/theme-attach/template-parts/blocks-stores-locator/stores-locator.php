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
$products_carousel = [];
if ($show_products_carousel && post_type_exists('producto')) {
  $products_query = new WP_Query([
    'post_type' => 'producto',
    'posts_per_page' => 6,
    'post_status' => 'publish',
  ]);

  if ($products_query->have_posts()) {
    while ($products_query->have_posts()) {
      $products_query->the_post();

      // Obtener precio de forma segura
      $price_from = '';
      $product_models = get_field('product_models');
      if (is_array($product_models) && !empty($product_models) && isset($product_models[0]['model_price'])) {
        $price_from = $product_models[0]['model_price'];
      }

      $products_carousel[] = [
        'id' => get_the_ID(),
        'title' => get_the_title(),
        'image' => function_exists('theme_attach_get_post_image_url')
          ? theme_attach_get_post_image_url(get_the_ID(), 'large')
          : get_the_post_thumbnail_url(get_the_ID(), 'large'),
        'alt' => function_exists('theme_attach_get_post_image_alt')
          ? theme_attach_get_post_image_alt(get_the_ID())
          : get_the_title(),
        'permalink' => get_permalink(),
        'price_from' => $price_from,
      ];
    }
    wp_reset_postdata();
  }
}
?>

<section class="stores-locator" id="stores-locator">

  <!-- Hero Section -->
  <div class="stores-locator__hero">
    <div class="stores-locator__hero-inner">
      <h1 class="stores-locator__hero-title"><?php echo esc_html($main_title); ?></h1>
      <p class="stores-locator__hero-description"><?php echo esc_html($main_description); ?></p>
    </div>
  </div>

  <!-- Filters and Map Container -->
  <div class="stores-locator__main">
    <div class="stores-locator__container">

      <!-- Filters -->
      <div class="stores-locator__filters">

        <!-- Servicios Filter -->
        <div class="stores-locator__filter-group">
          <div class="stores-locator__select-wrapper">
            <svg class="stores-locator__select-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path
                d="M10 0C6.14 0 3 3.14 3 7C3 12.25 10 20 10 20C10 20 17 12.25 17 7C17 3.14 13.86 0 10 0ZM10 9.5C8.62 9.5 7.5 8.38 7.5 7C7.5 5.62 8.62 4.5 10 4.5C11.38 4.5 12.5 5.62 12.5 7C12.5 8.38 11.38 9.5 10 9.5Z"
                fill="#6C757D" />
            </svg>
            <select id="stores-service-filter" class="stores-locator__select"
              aria-label="<?php esc_attr_e('Filtrar por servicio', 'theme-attach'); ?>">
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
            <svg class="stores-locator__select-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path
                d="M10 0C6.14 0 3 3.14 3 7C3 12.25 10 20 10 20C10 20 17 12.25 17 7C17 3.14 13.86 0 10 0ZM10 9.5C8.62 9.5 7.5 8.38 7.5 7C7.5 5.62 8.62 4.5 10 4.5C11.38 4.5 12.5 5.62 12.5 7C12.5 8.38 11.38 9.5 10 9.5Z"
                fill="#6C757D" />
            </svg>
            <select id="stores-department-filter" class="stores-locator__select"
              aria-label="<?php esc_attr_e('Filtrar por departamento', 'theme-attach'); ?>">
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

                <!-- Category Badge -->
                <?php
                $primary_service = wp_get_post_terms(
                  $store_id,
                  'categoria_promocion',
                  ['number' => 1]
                );
                if (!empty($primary_service) && !is_wp_error($primary_service)):
                  ?>
                  <div class="stores-locator__card-badge">
                    <?php echo esc_html($primary_service[0]->name); ?>
                  </div>
                <?php endif; ?>

                <!-- Store Name -->
                <h3 class="stores-locator__card-title">
                  <?php echo esc_html($store_name); ?>
                </h3>

                <!-- Store Details -->
                <div class="stores-locator__card-details">
                  <?php if ($store_address): ?>
                    <div class="stores-locator__card-item">
                      <svg width="16" height="20" viewBox="0 0 16 20" fill="none">
                        <path
                          d="M8 0C3.58 0 0 3.58 0 8C0 14 8 20 8 20C8 20 16 14 16 8C16 3.58 12.42 0 8 0ZM8 10.5C6.62 10.5 5.5 9.38 5.5 8C5.5 6.62 6.62 5.5 8 5.5C9.38 5.5 10.5 6.62 10.5 8C10.5 9.38 9.38 10.5 8 10.5Z"
                          fill="#027BFF" />
                      </svg>
                      <span><?php echo esc_html($store_address); ?></span>
                    </div>
                  <?php endif; ?>
                  <?php if ($store_phone): ?>
                    <div class="stores-locator__card-item">
                      <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path
                          d="M3.62 7.79C5.06 10.62 7.38 12.93 10.21 14.38L12.41 12.18C12.68 11.91 13.08 11.82 13.43 11.94C14.55 12.31 15.76 12.51 17 12.51C17.55 12.51 18 12.96 18 13.51V17C18 17.55 17.55 18 17 18C7.61 18 0 10.39 0 1C0 0.45 0.45 0 1 0H4.5C5.05 0 5.5 0.45 5.5 1C5.5 2.25 5.7 3.45 6.07 4.57C6.18 4.92 6.1 5.31 5.82 5.59L3.62 7.79Z"
                          fill="#027BFF" />
                      </svg>
                      <a
                        href="tel:+51<?php echo esc_attr(function_exists('theme_attach_sanitize_phone') ? theme_attach_sanitize_phone($store_phone) : preg_replace('/[^0-9+]/', '', $store_phone)); ?>">
                        <?= esc_html($store_phone); ?>
                      </a>
                    </div>
                  <?php endif; ?>

                  <?php if ($store_whatsapp_number): ?>
                    <?php
                    // Construir URL de WhatsApp
                    // $whatsapp_number_clean = function_exists('theme_attach_sanitize_phone') 
                    //   ? theme_attach_sanitize_phone($store_whatsapp_number)
                    //   : preg_replace('/[^0-9+]/', '', $store_whatsapp_number);              
                    $whatsapp_number_clean = '+51' . trim($store_whatsapp_number);
                    $whatsapp_url = 'https://wa.me/' . $whatsapp_number_clean;
                    if (!empty($store_whatsapp_message)) {
                      $whatsapp_url .= '?text=' . rawurlencode($store_whatsapp_message);
                    }
                    ?>
                    <div class="stores-locator__card-item">
                      <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path
                          d="M15.3 2.7C13.62 0.99 11.38 0 9 0C4.05 0 0 4.05 0 9C0 10.62 0.45 12.21 1.26 13.59L0 18L4.5 16.77C5.82 17.5 7.32 17.88 9 17.88C13.95 17.88 18 13.83 18 8.88C18 6.48 17.01 4.23 15.3 2.7ZM9 16.38C7.5 16.38 6.03 15.96 4.77 15.18L4.47 15L1.95 15.66L2.64 13.23L2.43 12.9C1.59 11.61 1.14 10.08 1.14 8.55C1.14 4.68 4.35 1.5 9 1.5C11.19 1.5 13.23 2.34 14.7 3.81C16.17 5.28 17.01 7.32 17.01 9.51C17.01 13.38 13.83 16.38 9 16.38Z"
                          fill="#25D366" />
                      </svg>
                      <a href="<?= esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">
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
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M8 0L6.59 1.41L12.17 7H0V9H12.17L6.59 14.59L8 16L16 8L8 0Z" fill="#027BFF" />
                  </svg>
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
  </div>

  <!-- Products Carousel Section -->
  <div style="display: none !important;">
    <?php if ($show_products_carousel && !empty($products_carousel)): ?>
      <div class="stores-locator__products">
        <div class="stores-locator__products-container">

          <h2 class="stores-locator__products-title">
            <?php _e('ELIGE TU GEELY', 'theme-attach'); ?>
          </h2>

          <p class="stores-locator__products-subtitle">
            <?php _e('Obtén los mejores precios en autos nuevos con Geely. Encuentra SUVs y Sedanes de alta calidad y tecnología a un precio accesible.', 'theme-attach'); ?>
          </p>

          <div class="stores-locator__products-carousel swiper">
            <div class="swiper-wrapper">
              <?php foreach ($products_carousel as $product): ?>
                <div class="swiper-slide">
                  <div class="stores-locator__product-card">

                    <div class="stores-locator__product-image">
                      <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['alt']); ?>">
                    </div>

                    <h3 class="stores-
                          echo esc_html(function_exists('theme_attach_format_price') 
                            ? theme_attach_format_price($product['price_from']) 
                            : 'S/ ' . number_format($product['price_from'], 2)
                          ); 
                      
                      <?php echo esc_html($product['title']); ?>
                    </h3>
                    
                    <?php if (!empty($product['price_from'])): ?>
                      <p class=" stores-locator__product-price">
                        <?php _e('Precio desde', 'theme-attach'); ?><br>
                        <strong><?php echo esc_html(theme_attach_format_price($product['price_from'])); ?></strong>
                        </p>
                      <?php endif; ?>

                      <div class="stores-locator__product-actions">
                        <a href="<?php echo esc_url($product['permalink']); ?>"
                          class="stores-locator__product-btn stores-locator__product-btn--outline">
                          <?php _e('Ver modelo', 'theme-attach'); ?>
                        </a>
                        <a href="<?php echo esc_url($product['permalink'] . '#cotizar'); ?>"
                          class="stores-locator__product-btn stores-locator__product-btn--primary">
                          <?php _e('Cotizar', 'theme-attach'); ?>
                        </a>
                      </div>

                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Navigation -->
            <div class="stores-locator__carousel-nav">
              <button class="stores-locator__carousel-prev" aria-label="<?php esc_attr_e('Anterior', 'theme-attach'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M15.41 7.41L14 6L8 12L14 18L15.41 16.59L10.83 12L15.41 7.41Z" fill="currentColor" />
                </svg>
              </button>
              <button class="stores-locator__carousel-next"
                aria-label="<?php esc_attr_e('Siguiente', 'theme-attach'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M8.59 16.59L10 18L16 12L10 6L8.59 7.41L13.17 12L8.59 16.59Z" fill="currentColor" />
                </svg>
              </button>
            </div>

            <!-- Pagination -->
            <div class="stores-locator__carousel-pagination"></div>
          </div>

        </div>
      </div>
    <?php endif; ?>
  </div>
</section>