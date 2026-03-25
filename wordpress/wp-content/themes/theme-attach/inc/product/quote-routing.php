<?php
if (!defined('ABSPATH')) exit;

/**
 * Helpers/redirects para el cotizador:
 * - Canonicaliza `?product_id=123` -> `?product=slug`
 * - Helper para construir URL de cotiza por slug (SEO)
 */

if (!function_exists('mg_quote_build_url')) {
  function mg_quote_build_url($product_id, array $extra_query = [])
  {
    $product_id = (int)$product_id;
    $base = home_url('/cotiza/');

    $query = is_array($extra_query) ? $extra_query : [];
    $query = array_filter($query, function ($v) {
      return $v !== null && $v !== '';
    });

    $slug = '';
    if ($product_id > 0) {
      $slug = (string)get_post_field('post_name', $product_id);
      $slug = sanitize_title($slug);
    }

    if ($slug !== '') {
      $query['product'] = $slug;
      unset($query['product_id']);
    } elseif ($product_id > 0) {
      $query['product_id'] = $product_id;
    }

    return add_query_arg($query, $base);
  }
}

add_action('template_redirect', function () {
  if (!is_page()) return;

  global $post;
  if (!$post || empty($post->post_name)) return;

  // Página /cotiza/ (slug)
  if ($post->post_name !== 'cotiza') return;

  // Ya viene con slug
  if (!empty($_GET['product'])) return;

  // Back-compat: product_id -> product (slug)
  $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
  if ($product_id <= 0) return;

  $slug = (string)get_post_field('post_name', $product_id);
  $slug = sanitize_title($slug);
  if ($slug === '') return;

  $query = [];
  foreach ($_GET as $k => $v) {
    if ($k === 'product_id') continue;
    $query[$k] = $v;
  }
  $query['product'] = $slug;

  $target = add_query_arg($query, home_url('/cotiza/'));
  wp_safe_redirect($target, 301);
  exit;
});

