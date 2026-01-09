<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Constante: Cantidad de promociones por página
 */
if (!defined('PROMOTIONS_PER_PAGE')) {
  define('PROMOTIONS_PER_PAGE', 4);
}

/**
 * Assets específicos de bloques Promociones
 */
function promotions_blocks_assets()
{

  /**
   * =========================
   * CSS de bloques Promociones
   * =========================
   * Convención:
   * template-parts/blocks-promotions/{block-name}.css
   */
  $css_blocks = [
    'promotions-hero',
    'promotions-grid',
    'promotions-single-hero',
    'promotions-single',
    'promotions-form',
  ];

  foreach ($css_blocks as $handle) {
    $rel = "/template-parts/blocks-promotions/{$handle}.css";
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
   * JS de bloques Promociones
   * =========================
   * Convención:
   * assets/js/{block-name}.js
   */
  $js_blocks = [
    'promotions-grid',
    'promotions-single',
    'promotions-form',
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

  // Localizar script para AJAX de grid
  wp_localize_script(
    'promotions-grid-js',
    'PROMOTIONS_GRID',
    [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('promotions_grid_nonce'),
    ]
  );

  // Localizar script para datos de departamentos y tiendas
  wp_localize_script(
    'promotions-single-js',
    'GEELY_STORES_DATA',
    [
      'departments' => promotions_get_departments_stores_data(),
    ]
  );
}
add_action('wp_enqueue_scripts', 'promotions_blocks_assets');

/**
 * Obtener datos de departamentos y tiendas para el formulario
 * 
 * @return array Estructura: ['Departamento' => [['id' => 1, 'title' => 'Tienda']]]
 */
function promotions_get_departments_stores_data(): array
{
  $departments_data = [];

  // Obtener todos los departamentos con tiendas
  $departments = get_terms([
    'taxonomy' => 'departamento',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC',
  ]);

  if (is_wp_error($departments) || empty($departments)) {
    return [];
  }

  foreach ($departments as $department) {
    // Obtener tiendas de este departamento
    $stores_query = new WP_Query([
      'post_type' => 'tienda',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'orderby' => 'title',
      'order' => 'ASC',
      'tax_query' => [
        [
          'taxonomy' => 'departamento',
          'field' => 'term_id',
          'terms' => $department->term_id,
        ]
      ],
    ]);

    if ($stores_query->have_posts()) {
      $stores = [];
      while ($stores_query->have_posts()) {
        $stores_query->the_post();
        $stores[] = [
          'id' => get_the_ID(),
          'title' => html_entity_decode(
            get_the_title(),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
          ),
        ];
      }
      wp_reset_postdata();

      $departments_data[$department->name] = $stores;
    }
  }

  return $departments_data;
}

/**
 * Handler AJAX para filtrar promociones por categoría
 */
add_action('wp_ajax_promotions_filter_by_category', 'promotions_filter_by_category');
add_action('wp_ajax_nopriv_promotions_filter_by_category', 'promotions_filter_by_category');

function promotions_filter_by_category()
{
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'promotions_grid_nonce')) {
    wp_send_json_error(['message' => 'Invalid nonce'], 403);
  }

  $category_slug = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
  $posts_per_page = PROMOTIONS_PER_PAGE;

  // Configurar query args
  $args = [
    'post_type' => 'promocion',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
  ];

  // Filtrar por categoría si se especifica
  if (!empty($category_slug)) {
    $args['tax_query'] = [
      [
        'taxonomy' => 'categoria_promocion',
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

    $description = function_exists('get_field') ? get_field('promocion_card_text', $post_id) : '';
    if (empty($description)) {
      $description = get_the_excerpt();
    }

    $link_url = function_exists('get_field') ? get_field('promocion_link_url', $post_id) : '';
    $link_text = function_exists('get_field') ? get_field('promocion_link_text', $post_id) : '';
    $link_text = !empty($link_text) ? $link_text : 'Ver condiciones';

    if (empty($link_url)) {
      $link_url = get_permalink($post_id);
    }
    ?>
    <div class="promotions-grid__item js-promo-item" data-page="<?php echo esc_attr($page_number); ?>"
      style="<?php echo $page_number > 1 ? 'display: none;' : ''; ?>">
      <div class="promotions-grid__card">

        <?php if ($image_url): ?>
          <div class="promotions-grid__image">
            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt ?: $title); ?>" loading="lazy">
          </div>
        <?php endif; ?>

        <div class="promotions-grid__content">
          <?php if ($title): ?>
            <h3 class="promotions-grid__title title-5"><?php echo esc_html($title); ?></h3>
          <?php endif; ?>

          <?php if ($description): ?>
            <div class="promotions-grid__description paragraph-4">
              <?php echo wp_kses_post(wpautop($description)); ?>
            </div>
          <?php endif; ?>

          <?php if ($link_url): ?>
            <a href="<?php echo esc_url($link_url); ?>" class="promotions-grid__link" rel="noopener">
              <?php echo esc_html($link_text); ?>
            </a>
          <?php endif; ?>
        </div>

      </div>
    </div>
    <?php
    $index++;
  endwhile;
  wp_reset_postdata();

  wp_send_json_success([
    'html' => ob_get_clean(),
    'total_pages' => $total_pages,
    'total_items' => $total_items,
  ]);
}



add_action('init', function () {

  // Si CF7 no está activo, no hacemos nada
  if (!function_exists('wpcf7')) {
    return;
  }

  add_filter('wpcf7_form_tag', function ($tag) {

    // Obtener nombre del tag para array u objeto
    $tag_name = is_object($tag)
      ? (property_exists($tag, 'name') ? (string) $tag->name : '')
      : (isset($tag['name']) ? (string) $tag['name'] : '');

    if ($tag_name !== 'department' && $tag_name !== 'store') {
      return $tag;
    }

    // Data: ['Departamento' => [['id'=>..,'title'=>..], ...]]
    $data = promotions_get_departments_stores_data();

    // =====================
    // department
    // =====================
    if ($tag_name === 'department') {
      $departments = array_keys($data);
      sort($departments, SORT_NATURAL | SORT_FLAG_CASE);

      if (is_object($tag)) {
        $tag->values = $departments;
        $tag->labels = $departments;
      } else {
        $tag['values'] = $departments;
        $tag['labels'] = $departments;
      }

      return $tag;
    }

    // =====================
    // store (value = ID, label = Title)
    // =====================
    if ($tag_name === 'store') {
      $values = [];
      $labels = [];

      foreach ($data as $stores) {
        foreach ($stores as $store) {
          if (empty($store['id']) || empty($store['title']))
            continue;

          $values[] = (string) $store['id'];
          $labels[] = (string) $store['title'];
        }
      }

      if (is_object($tag)) {
        $tag->values = $values;
        $tag->labels = $labels;
      } else {
        $tag['values'] = $values;
        $tag['labels'] = $labels;
      }

      return $tag;
    }

    return $tag;
  }, 10, 1);
});
