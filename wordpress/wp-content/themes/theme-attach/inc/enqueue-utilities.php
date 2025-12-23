<?php
if (!defined('ABSPATH')) exit;

function attach_enqueue_utilities() {
  $css_rel_path = '/assets/css/utilities.css';
  $css_abs_path = get_template_directory() . $css_rel_path;

  wp_enqueue_style(
    'attach-utilities',
    get_template_directory_uri() . $css_rel_path,
    [],
    file_exists($css_abs_path) ? filemtime($css_abs_path) : wp_get_theme()->get('Version')
  );
}
add_action('wp_enqueue_scripts', 'attach_enqueue_utilities');

function mf_product_modal_ajax() {
  check_ajax_referer('mf_finder_nonce', 'nonce');

  $id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
  if (!$id) {
    wp_send_json(['ok' => false, 'message' => 'Missing product_id']);
  }

  // Imagen (thumbnail del producto)
  $img = get_the_post_thumbnail_url($id, 'full');
  if (!$img) $img = '';

  // Specs a nivel producto
  $specs = [
    'spec_maximum_power' => (string) get_field('spec_maximum_power', $id),
    'spec_transmission'  => (string) get_field('spec_transmission', $id),
    'spec_security'      => (string) get_field('spec_security', $id),
    'spec_seating'       => (string) get_field('spec_seating', $id),
    'spec_sush_button'   => (string) get_field('spec_sush_button', $id),
    'spec_type'          => (string) get_field('spec_type', $id),
  ];

  // Versions desde product_models
  $versions = [];
  $models = get_field('product_models', $id);

  if (is_array($models) && !empty($models)) {
    foreach ($models as $m) {
      $name =
        (string)($m['model_version_name'] ?? '') ?:
        (string)($m['model_name'] ?? '') ?:
        (string)($m['model_trim'] ?? '') ?:
        (string)($m['model_version'] ?? '') ?:
        '';

      if ($name === '') continue;

      $versions[] = [
        'name' => $name,
        'usd'  => (string)($m['model_price_usd'] ?? ''),
        'pen'  => (string)($m['model_price_local'] ?? ''),
      ];
    }
  }

  if (empty($versions)) {
    $versions[] = ['name' => 'COMFORT 1.5 MT', 'usd' => '', 'pen' => ''];
  }

  wp_send_json([
    'ok'       => true,
    'title'    => get_the_title($id),
    'img'      => $img,     //  ahora sí manda imagen
    'usd'      => '',       // si quieres: puedes mandar precio base aquí
    'local'    => '',       // idem
    'specs'    => $specs,
    'versions' => $versions,
  ]);
}

add_action('wp_ajax_mf_product_modal_ajax', 'mf_product_modal_ajax');
add_action('wp_ajax_nopriv_mf_product_modal_ajax', 'mf_product_modal_ajax');
