<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Encola estilos y scripts generales del tema
 */
function theme_attach_assets() {

    // Estilos del tema (usa style.css)
    wp_enqueue_style(
        'theme-attach-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );

    // Swiper CSS (para sliders globales)
    wp_enqueue_style(
        'swiper',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        [],
        '11.0.0'
    );

    // Swiper JS (para sliders globales)
    wp_enqueue_script(
        'swiper',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        [],
        '11.0.0',
        true
    );

    // JS propio del tema
    wp_enqueue_script(
        'theme-attach-main',
        get_stylesheet_directory_uri() . '/assets/js/main.js',
        ['swiper'],
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'theme_attach_assets');
