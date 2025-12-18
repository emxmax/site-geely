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
}
add_action('wp_enqueue_scripts', 'page_blocks_assets');
