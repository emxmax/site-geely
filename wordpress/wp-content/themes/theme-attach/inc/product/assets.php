<?php
if (! defined('ABSPATH')) exit;

/**
 * Assets específicos de bloques Product
 */
function product_blocks_assets()
{

    // --- CSS de bloques ---
    $css_blocks = [
        'emgrand-hero',
        'emgrand-config',
        'emgrand-moments',
        'emgrand-design',
        'emgrand-tech',
        'emgrand-experience',
        'emgrand-safety',
        'emgrand-cta',
        'models-geely',
        'models-finder',

        'geely-quote-form',
        'quote-geely',
    ];

    foreach ($css_blocks as $handle) {

        if ($handle === 'geely-quote-form') {
            $rel = "/template-parts/blocks-product/partials/{$handle}.css";
        } else {
            $rel = "/template-parts/blocks-product/{$handle}.css";
        }

        $abs = get_stylesheet_directory() . $rel;

        if (!file_exists($abs)) {
            continue;
        }

        $deps = [];
        if ($handle === 'quote-geely') {
            $deps = ['geely-quote-form-css'];
        }

        wp_enqueue_style(
            "{$handle}-css",
            get_stylesheet_directory_uri() . $rel,
            $deps,
            filemtime($abs)
        );
    }

    // --- Librería 360 Cloudimage ---
    wp_enqueue_script(
        'ci-360',
        'https://scaleflex.cloudimg.io/v7/plugins/js-cloudimage-360-view/latest/js-cloudimage-360-view.min.js',
        [],
        null,
        true
    );

    // --- JS de bloques (asumiendo nombres product-*.js) ---
    // HERO
    $hero_js_rel = '/assets/js/emg-hero.js';
    $hero_js_abs = get_stylesheet_directory() . $hero_js_rel;

    wp_enqueue_script(
        'emg-hero-js',
        get_stylesheet_directory_uri() . $hero_js_rel,
        ['swiper'],
        file_exists($hero_js_abs) ? filemtime($hero_js_abs) : null,
        true
    );

    // CONFIG
    $config_js_rel = '/assets/js/emg-config.js';
    $config_js_abs = get_stylesheet_directory() . $config_js_rel;

    wp_enqueue_script(
        'emg-config-js',
        get_stylesheet_directory_uri() . $config_js_rel,
        ['ci-360'],
        file_exists($config_js_abs) ? filemtime($config_js_abs) : null,
        true
    );

    // MOMENTS
    $moments_js_rel = '/assets/js/emg-moments.js';
    $moments_js_abs = get_stylesheet_directory() . $moments_js_rel;

    wp_enqueue_script(
        'emg-moments-js',
        get_stylesheet_directory_uri() . $moments_js_rel,
        ['swiper'],
        file_exists($moments_js_abs) ? filemtime($moments_js_abs) : null,
        true
    );

    // DESIGN
    $design_js_rel = '/assets/js/emg-design.js';
    $design_js_abs = get_stylesheet_directory() . $design_js_rel;

    wp_enqueue_script(
        'emg-design-js',
        get_stylesheet_directory_uri() . $design_js_rel,
        ['swiper'],
        file_exists($design_js_abs) ? filemtime($design_js_abs) : null,
        true
    );

    // SAFETY
    $safety_js_rel = '/assets/js/emg-safety.js';
    $safety_js_abs = get_stylesheet_directory() . $safety_js_rel;

    wp_enqueue_script(
        'emg-safety-js',
        get_stylesheet_directory_uri() . $safety_js_rel,
        ['swiper'],
        file_exists($safety_js_abs) ? filemtime($safety_js_abs) : null,
        true
    );

    // MODELS GEELY
    $models_geely_js_rel = '/assets/js/models-geely.js';
    $models_geely_js_abs = get_stylesheet_directory() . $models_geely_js_rel;

    wp_enqueue_script(
        'models-geely-js',
        get_stylesheet_directory_uri() . $models_geely_js_rel,
        ['swiper'],
        file_exists($models_geely_js_abs) ? filemtime($models_geely_js_abs) : null,
        true
    );
    // MODELS GEELY
    // FORM (base)
    $quote_form_js_rel = '/assets/js/quote-form.js';
    $quote_form_js_abs = get_stylesheet_directory() . $quote_form_js_rel;

    wp_enqueue_script(
        'quote-form-js',
        get_stylesheet_directory_uri() . $quote_form_js_rel,
        [],
        file_exists($quote_form_js_abs) ? filemtime($quote_form_js_abs) : null,
        true
    );

    // PAGE (geely) -> depende del base
    $quote_geely_js_rel = '/assets/js/quote-geely.js';
    $quote_geely_js_abs = get_stylesheet_directory() . $quote_geely_js_rel;

    wp_enqueue_script(
        'quote-geely-js',
        get_stylesheet_directory_uri() . $quote_geely_js_rel,
        ['quote-form-js'], // dependencia
        file_exists($quote_geely_js_abs) ? filemtime($quote_geely_js_abs) : null,
        true
    );

    // MODELS GEELY
    $models_finder_js_rel = '/assets/js/models-finder.js';
    $models_finder_js_abs = get_stylesheet_directory() . $models_finder_js_rel;

    wp_enqueue_script(
        'models-finder',
        get_stylesheet_directory_uri() . $models_finder_js_rel,
        [],
        file_exists($models_finder_js_abs) ? filemtime($models_finder_js_abs) : null,
        true
    );

    wp_localize_script('models-finder', 'mfFinder', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('mf_finder_nonce'),
    ]);

    wp_enqueue_script(
        'google-maps',
        'https://maps.googleapis.com/maps/api/js?key=AIzaSyBoj8gwBBd4OV9f06jlR_klaUYYWAKNEDY&language=es&region=PE',
        [],
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'product_blocks_assets');
