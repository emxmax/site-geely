<?php
/**
 * Devuelve IDs de términos usados por un post_type dentro de una taxonomía.
 *
 * @param string $taxonomy
 * @param string $post_type
 * @param array $query_args Extra args para WP_Query (opc)
 * @return int[]
 */
function get_term_ids_used_by_post_type(
  string $taxonomy,
  string $post_type,
  array $query_args = []
): array {
  $defaults = [
    'post_type' => $post_type,
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
  ];

  $q = new WP_Query(array_merge($defaults, $query_args));

  $term_ids = [];

  if (!empty($q->posts)) {
    foreach ($q->posts as $post_id) {
      $ids = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
      if (!is_wp_error($ids) && !empty($ids)) {
        $term_ids = array_merge($term_ids, $ids);
      }
    }
  }

  $term_ids = array_values(array_unique(array_filter($term_ids)));
  return $term_ids;
}


/**
 * Devuelve términos de una taxonomía usados por un post_type, ordenados.
 * Orden principal: ACF term field (por defecto: "order") ascendente.
 * Fallback: name asc para estabilidad.
 *
 * @param string $taxonomy
 * @param string $post_type
 * @param string $acf_order_field
 * @param int|null $limit Límite de términos a devolver (null = todos)
 * @return WP_Term[]
 */
function get_terms_for_post_type_ordered(
  string $taxonomy,
  string $post_type,
  string $acf_order_field = 'order',
  ?int $limit = null
): array {
  $term_ids = get_term_ids_used_by_post_type(
    $taxonomy,
    $post_type
  );

  if (empty($term_ids)) {
    return [];
  }

  $args = [
    'taxonomy' => $taxonomy,
    'include' => $term_ids,
    'hide_empty' => false, // ya filtramos por CPT
  ];

  // Agregar límite si se especifica
  // if ($limit !== null && $limit > 0) {
  //   $args['number'] = $limit;
  // }

  $terms = get_terms($args);

  if (is_wp_error($terms) || empty($terms)) {
    return [];
  }

  // Si no está ACF disponible, ordenar por nombre y listo
  if (!function_exists('get_field')) {
    usort($terms, fn($a, $b) => strcasecmp($a->name, $b->name));
    return $terms;
  }

  usort($terms, function ($a, $b) use ($acf_order_field) {
    $oa = get_field($acf_order_field, 'term_' . $a->term_id);
    $ob = get_field($acf_order_field, 'term_' . $b->term_id);

    $oa = is_numeric($oa) ? (int) $oa : 999999;
    $ob = is_numeric($ob) ? (int) $ob : 999999;

    if ($oa === $ob) {
      return strcasecmp($a->name, $b->name);
    }

    return $oa <=> $ob;
  });

  // Aplicar límite si se especifica
  if ($limit !== null && $limit > 0) {
    $terms = array_slice($terms, 0, $limit);
  }

  return $terms;
}