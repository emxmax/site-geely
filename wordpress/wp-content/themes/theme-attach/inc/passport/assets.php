<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Constante: Cantidad de pasaportes por página
 */
if (!defined('PASSPORTS_PER_PAGE')) {
  define('PASSPORTS_PER_PAGE', 90);
}

/**
 * Assets específicos de bloques Pasaportes
 */
function passport_blocks_assets()
{

  /**
   * =========================
   * CSS de bloques Pasaportes
   * =========================
   * Convención:
   * template-parts/blocks-passport/{block-name}.css
   */
  $css_blocks = [
    'passport-hero',
    'passport-catalog',
  ];

  foreach ($css_blocks as $handle) {
    $rel = "/template-parts/blocks-passport/{$handle}.css";
    $abs = get_stylesheet_directory() . $rel;

    wp_enqueue_style(
      "{$handle}-css",
      get_stylesheet_directory_uri() . $rel,
      [],
      file_exists($abs) ? filemtime($abs) : null
    );
  }

  /**
   * =========================
   * JS de bloques Pasaportes
   * =========================
   * Convención:
   * assets/js/{block-name}.js
   */
  $js_blocks = [
    'passport-catalog',
  ];

  foreach ($js_blocks as $handle) {
    $rel = "/assets/js/{$handle}.js";
    $abs = get_stylesheet_directory() . $rel;

    wp_enqueue_script(
      "{$handle}-js",
      get_stylesheet_directory_uri() . $rel,
      [],
      file_exists($abs) ? filemtime($abs) : null,
      true
    );
  }

  // Localizar script para AJAX de catalog
  wp_localize_script(
    'passport-catalog-js',
    'PASSPORT_CATALOG',
    [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('passport_catalog_nonce'),
    ]
  );
}
add_action('wp_enqueue_scripts', 'passport_blocks_assets');

/**
 * Handler AJAX para filtrar pasaportes por categoría
 */
add_action('wp_ajax_passport_filter_by_category', 'passport_filter_by_category');
add_action('wp_ajax_nopriv_passport_filter_by_category', 'passport_filter_by_category');

function passport_filter_by_category()
{
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'passport_catalog_nonce')) {
    wp_send_json_error(['message' => 'Invalid nonce'], 403);
  }

  $category_slug = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
  $posts_per_page = PASSPORTS_PER_PAGE;

  // Configurar query args
  $args = [
    'post_type' => 'pasaporte',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
  ];

  // Filtrar por categoría si se especifica
  if (!empty($category_slug) && $category_slug !== 'todos') {
    $args['tax_query'] = [
      [
        'taxonomy' => 'categoria_pasaporte',
        'field' => 'slug',
        'terms' => $category_slug,
      ],
    ];
  }

  $q = new WP_Query($args);

  if (!$q->have_posts()) {
    wp_reset_postdata();
    wp_send_json_success([
      'html' => '',
      'total_pages' => 0,
      'total_items' => 0,
    ]);
    return;
  }

  $total_items = $q->post_count;
  $total_pages = ceil($total_items / $posts_per_page);

  ob_start();
  $index = 0;
  while ($q->have_posts()):
    $q->the_post();

    $page_number = floor($index / $posts_per_page) + 1;
    $post_id = get_the_ID();
    $title = get_the_title();
    $image_id = get_post_thumbnail_id($post_id);
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
    $image_alt = $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '';

    // Obtener el PDF del pasaporte
    $service_passport = function_exists('get_field') ? get_field('service_passport', $post_id) : '';
    $pdf_url = '';

    if ($service_passport) {
      if (is_array($service_passport)) {
        $pdf_url = $service_passport['url'] ?? '';
      } elseif (is_numeric($service_passport)) {
        $pdf_url = wp_get_attachment_url($service_passport) ?: '';
      } else {
        $pdf_url = $service_passport;
      }
    }
    ?>
    <div class="passport-catalog__item js-passport-item" data-page="<?php echo esc_attr($page_number); ?>"
      style="<?php echo $page_number > 1 ? 'display: none;' : ''; ?>">
      <div class="passport-catalog__card">

        <?php if ($title): ?>
          <h3 class="passport-catalog__title title-6 title-mobile-sm-4">
            <?php echo esc_html($title); ?>
          </h3>
        <?php endif; ?>

        <?php if ($image_url): ?>
          <div class="passport-catalog__image">
            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt ?: $title); ?>" loading="lazy">
          </div>
        <?php endif; ?>

        <div class="passport-catalog__content">

          <?php if ($pdf_url): ?>
            <a href="<?php echo esc_url($pdf_url); ?>" class="passport-catalog__button title-7 title-mobile-sm-5" target="_blank"
              rel="noopener">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
              Pasaporte de servicios
            </a>
          <?php endif; ?>
        </div>

      </div>
    </div>
    <?php
    $index++;
  endwhile;
  wp_reset_postdata();

  $html = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'total_pages' => $total_pages,
    'total_items' => $total_items,
  ]);
}

/**
 * =================================================================
 * Columnas personalizadas en el admin del CPT 'pasaporte'
 * =================================================================
 */

/**
 * Agregar columnas personalizadas
 * 
 * @param array $columns Columnas existentes
 * @return array Columnas modificadas
 */
function passport_add_custom_columns($columns)
{
  // Crear nuevo array con el orden deseado
  $new_columns = [];

  foreach ($columns as $key => $value) {
    $new_columns[$key] = $value;

    // Insertar después de 'title'
    if ($key === 'title') {
      $new_columns['passport_category'] = __('Categoría', 'theme-attach');
      $new_columns['passport_thumbnail'] = __('Imagen', 'theme-attach');
    }
  }

  return $new_columns;
}
add_filter(
  'manage_pasaporte_posts_columns',
  'passport_add_custom_columns'
);

/**
 * Llenar el contenido de las columnas personalizadas
 * 
 * @param string $column  Nombre de la columna
 * @param int    $post_id ID del post
 */
function passport_fill_custom_columns($column, $post_id)
{
  switch ($column) {
    case 'passport_thumbnail':
      $thumbnail_id = get_post_thumbnail_id($post_id);

      if ($thumbnail_id) {
        $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');

        if ($thumbnail_url) {
          echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr(get_the_title($post_id)) . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">';
        } else {
          echo '<span style="color: #999;">—</span>';
        }
      } else {
        echo '<span style="color: #999;">—</span>';
      }
      break;

    case 'passport_category':
      $terms = get_the_terms($post_id, 'categoria_pasaporte');

      if (!empty($terms) && !is_wp_error($terms)) {
        $category_names = array_map(function ($term) {
          return sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('edit.php?post_type=pasaporte&categoria_pasaporte=' . $term->slug)),
            esc_html($term->name)
          );
        }, $terms);

        echo implode(', ', $category_names);
      } else {
        echo '<span style="color: #999;">—</span>';
      }
      break;
  }
}
add_action(
  'manage_pasaporte_posts_custom_column',
  'passport_fill_custom_columns',
  10,
  2
);

/**
 * Hacer columnas ordenables
 * 
 * @param array $columns Columnas ordenables
 * @return array Columnas modificadas
 */
function passport_sortable_columns($columns)
{
  $columns['passport_category'] = 'passport_category';
  return $columns;
}
add_filter(
  'manage_edit-pasaporte_sortable_columns',
  'passport_sortable_columns'
);

/**
 * Ordenamiento por taxonomía
 * 
 * @param WP_Query $query Query de WordPress
 */
function passport_columns_orderby($query)
{
  if (!is_admin() || !$query->is_main_query()) {
    return;
  }

  $orderby = $query->get('orderby');

  if ($orderby === 'passport_category') {
    $query->set('orderby', 'meta_value');
    $query->set('meta_key', 'passport_category');
  }
}
add_action(
  'pre_get_posts',
  'passport_columns_orderby'
);


/*==================================================================*/





