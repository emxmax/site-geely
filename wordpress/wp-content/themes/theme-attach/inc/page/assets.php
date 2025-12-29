<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Assets específicos de bloques PAGE
 */
function page_blocks_assets() {

    /**
     * =========================
     * CSS de bloques PAGE
     * =========================
     * Convención:
     * template-parts/blocks-page/{block-name}.css
     */
    $css_blocks = [
        'experience-carousel',
        'image-commitments',
        'find-geely',
        'faq',
        'hero-carousel',
        'geely-future',
        'stores-locator',
    ];

    foreach ($css_blocks as $handle) {
        $rel = "/template-parts/blocks-page/{$handle}.css";
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
        'experience-carousel',
        'image-commitments',
        'faq',
        'hero-carousel',
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
        wp_localize_script('page-stores-locator-js', 'STORES_LOCATOR', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'google_maps_api_key' => 'TU_API_KEY_AQUI', // CAMBIAR por tu API key real
        ]);
    }
}
add_action('wp_enqueue_scripts', 'page_blocks_assets');
