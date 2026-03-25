<?php
if (!defined('ABSPATH')) exit;

/**
 * Helpers/redirects para el cotizador:
 * - Canonicaliza `?product_id=123` -> `?product=slug`
 * - Helper para construir URL de cotiza por slug (SEO)
 */

add_action('init', function () {
  // /cotiza/<producto-slug>/
  add_rewrite_rule(
    '^cotiza/([^/]+)/?$',
    'index.php?pagename=cotiza&product=$matches[1]',
    'top'
  );
});

add_filter('query_vars', function ($vars) {
  $vars[] = 'product';
  return $vars;
});

// Flush automático (1 sola vez) cuando se despliega este cambio y un admin entra al WP.
add_action('admin_init', function () {
  $ver = 1;
  $key = 'mg_quote_rewrite_ver';
  if ((int)get_option($key, 0) >= $ver) return;
  flush_rewrite_rules(false);
  update_option($key, $ver);
});

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
      unset($query['product'], $query['product_id']);

      // Respeta la configuración de trailing slash del sitio.
      $path = user_trailingslashit('cotiza/' . $slug, 'page');
      $base = home_url('/' . ltrim($path, '/'));
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

  // Si ya está en la ruta canónica (sin querystring legacy) no hacemos nada.
  $has_legacy_product = isset($_GET['product']) && $_GET['product'] !== '';
  $has_legacy_id = isset($_GET['product_id']) && $_GET['product_id'] !== '';
  if (!$has_legacy_product && !$has_legacy_id) return;

  // Resolver slug (preferir `product`, fallback a `product_id`)
  $slug = $has_legacy_product ? sanitize_title((string)wp_unslash($_GET['product'])) : '';
  if ($slug === '') {
    $product_id = $has_legacy_id ? (int)$_GET['product_id'] : 0;
    if ($product_id > 0) {
      $slug = sanitize_title((string)get_post_field('post_name', $product_id));
    }
  }
  if ($slug === '') return;

  $query = [];
  foreach ($_GET as $k => $v) {
    if ($k === 'product_id' || $k === 'product') continue;
    $query[$k] = $v;
  }

  $path = user_trailingslashit('cotiza/' . $slug, 'page');
  $target = add_query_arg($query, home_url('/' . ltrim($path, '/')));
  wp_safe_redirect($target, 301);
  exit;
});
