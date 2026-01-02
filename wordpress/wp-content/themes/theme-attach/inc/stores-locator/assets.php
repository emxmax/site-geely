<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Assets específicos de bloques RED DE ATENCION | STORES LOCATOR
 */
function page_stores_locator_assets()
{
  /**
   * =========================
   * CSS de bloques Red de atencion
   * =========================
   * Convención:
   * template-parts/blocks-stores-locator/{block-name}.css
   */
  $css_blocks = [
    'stores-locator',
  ];

  foreach ($css_blocks as $handle) {
    $rel = "/template-parts/blocks-stores-locator/{$handle}.css";
    $abs = get_stylesheet_directory() . $rel;

    wp_enqueue_style(
      "page-{$handle}-css",
      get_stylesheet_directory_uri() . $rel,
      [],
      file_exists($abs) ? filemtime($abs) : null
    );
  }

  /**
   * =========================
   * JS de bloques PAGE
   * =========================
   * Convención:
   * assets/js/{block-name}.js
   */
  $js_blocks = [
  ];

  foreach ($js_blocks as $handle) {
    $rel = "/assets/js/{$handle}.js";
    $abs = get_stylesheet_directory() . $rel;

    wp_enqueue_script(
      "page-{$handle}-js",
      get_stylesheet_directory_uri() . $rel,
      ['swiper'],
      file_exists($abs) ? filemtime($abs) : null,
      true
    );
  }

  /**
   * Stores Locator (Google Maps + Swiper)
   * https://console.cloud.google.com/welcome
   * Maps JavaScript API
   * AIzaSyBoj8gwBBd4OV9f06jlR_klaUYYWAKNEDY
   */
  $stores_locator_js = "/assets/js/stores-locator.js";
  $stores_locator_js_abs = get_stylesheet_directory() . $stores_locator_js;

  if (file_exists($stores_locator_js_abs)) {
    wp_enqueue_script(
      'page-stores-locator-js',
      get_stylesheet_directory_uri() . $stores_locator_js,
      ['swiper'],
      filemtime($stores_locator_js_abs),
      true
    );

    // Datos para JavaScript
    wp_localize_script(
      'page-stores-locator-js',
      'STORES_LOCATOR',
      [
        'ajax_url' => admin_url('admin-ajax.php'),
        'google_maps_api_key' => 'AIzaSyBoj8gwBBd4OV9f06jlR_klaUYYWAKNEDY', // CAMBIAR por tu API key real
      ]
    );
  }
}
add_action(
  'wp_enqueue_scripts',
  'page_stores_locator_assets'
);

/**
 * =========================
 * ADMIN: Columnas personalizadas en CPT Tienda
 * =========================
 */

/**
 * Agregar columnas personalizadas a la tabla de Tiendas
 * 
 * @param array $columns Columnas existentes
 * @return array Columnas modificadas
 */
function stores_locator_add_custom_columns($columns)
{
  // Crear nuevo array con el orden deseado
  $new_columns = [];

  foreach ($columns as $key => $value) {
    // Insertar después de 'title'
    $new_columns[$key] = $value;

    if ($key === 'title') {
      $new_columns['departamento'] = __('Departamento', 'theme-attach');
      $new_columns['categoria_promocion'] = __('Tipo de Campaña', 'theme-attach');
    }
  }

  return $new_columns;
}
add_filter(
  'manage_tienda_posts_columns',
  'stores_locator_add_custom_columns'
);

/**
 * Llenar el contenido de las columnas personalizadas
 * 
 * @param string $column  Nombre de la columna
 * @param int    $post_id ID del post
 */
function stores_locator_fill_custom_columns($column, $post_id)
{
  switch ($column) {
    case 'departamento':
      $terms = get_the_terms($post_id, 'departamento');

      if (!empty($terms) && !is_wp_error($terms)) {
        $department_names = array_map(function ($term) {
          return sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('edit.php?post_type=tienda&departamento=' . $term->slug)),
            esc_html($term->name)
          );
        }, $terms);

        echo implode(', ', $department_names);
      } else {
        echo '<span style="color: #999;">—</span>';
      }
      break;

    case 'categoria_promocion':
      $terms = get_the_terms($post_id, 'categoria_promocion');

      if (!empty($terms) && !is_wp_error($terms)) {
        $category_names = array_map(function ($term) {
          return sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('edit.php?post_type=tienda&categoria_promocion=' . $term->slug)),
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
  'manage_tienda_posts_custom_column',
  'stores_locator_fill_custom_columns',
  10,
  2
);

/**
 * Hacer las columnas ordenables (sortable)
 * 
 * @param array $columns Columnas ordenables
 * @return array Columnas modificadas
 */
function stores_locator_sortable_columns($columns)
{
  $columns['departamento'] = 'departamento';
  $columns['categoria_promocion'] = 'categoria_promocion';

  return $columns;
}
add_filter(
  'manage_edit-tienda_sortable_columns',
  'stores_locator_sortable_columns'
);

/**
 * Modificar la query para ordenar por taxonomías
 * 
 * @param WP_Query $query Query actual
 */
function stores_locator_columns_orderby($query)
{
  if (!is_admin() || !$query->is_main_query()) {
    return;
  }

  $orderby = $query->get('orderby');

  switch ($orderby) {
    case 'departamento':
      $query->set('meta_key', 'departamento');
      $query->set('orderby', 'meta_value');
      break;

    case 'categoria_promocion':
      $query->set('meta_key', 'categoria_promocion');
      $query->set('orderby', 'meta_value');
      break;
  }
}
add_action(
  'pre_get_posts',
  'stores_locator_columns_orderby'
);