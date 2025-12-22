<?php
if (!defined('ABSPATH'))
    exit;

function attach_enqueue_utilities()
{
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

add_action('wp_ajax_mf_product_modal', 'mf_product_modal_ajax');
add_action('wp_ajax_nopriv_mf_product_modal', 'mf_product_modal_ajax');

function mf_product_modal_ajax()
{
    $id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
    if (!$id) wp_send_json(['ok' => false]);

    $title = get_the_title($id);
    $img   = get_the_post_thumbnail_url($id, 'large');

    $usd = get_field('model_price_usd', $id);
    $pen = get_field('model_price_local', $id);

    $price = 'USD ' . ($usd ?: '') . ' • PEN ' . ($pen ?: '');

    $specs = [
        'spec_maximum_power' => (string) get_field('spec_maximum_power', $id),
        'spec_transmission'  => (string) get_field('spec_transmission', $id),
        'spec_security'      => (string) get_field('spec_security', $id),
        'spec_seating'       => (string) get_field('spec_seating', $id),
        'spec_sush_button'   => (string) get_field('spec_sush_button', $id),
    ];

    wp_send_json([
        'ok' => true,
        'title' => $title,
        'img' => $img,
        'price' => $price,
        'specs' => $specs,
    ]);
}
