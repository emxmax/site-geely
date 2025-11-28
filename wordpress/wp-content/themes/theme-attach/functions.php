<?php

/**
 * Funciones del tema Theme Attach
 */

if (! defined('ABSPATH')) {
    exit; // Seguridad básica
}

/**
 * Encolar estilos y scripts generales del tema
 */
function theme_attach_assets()
{
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
        'https://unpkg.com/swiper/swiper-bundle.min.css',
        [],
        '11.0.0'
    );

    // Swiper JS (para sliders globales)
    wp_enqueue_script(
        'swiper',
        'https://unpkg.com/swiper/swiper-bundle.min.js',
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

/**
 * Registrar bloques de ACF
 */
function theme_attach_register_acf_blocks()
{
    if (! function_exists('acf_register_block_type')) {
        return;
    }

    // Bloque HERO Emgrand
    acf_register_block_type([
        'name'            => 'emgrand-hero',
        'title'           => __('Emgrand - Hero', 'theme-attach'),
        'description'     => __('Hero principal del Emgrand', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-hero.php',
        'category'        => 'layout',
        'icon'            => 'car',
        'keywords'        => ['emgrand', 'hero', 'geely'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Bloque EMGRAND – Configurador
    acf_register_block_type([
        'name'            => 'emgrand-config',
        'title'           => __('Emgrand - Configurador', 'theme-attach'),
        'description'     => __('Configurador de versiones, colores y precios del modelo Emgrand', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-config.php',
        'category'        => 'layout',
        'icon'            => 'admin-generic',
        'keywords'        => ['emgrand', 'config', 'versiones', 'colores'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Nuevo bloque: Momentos Emgrand
    acf_register_block_type([
        'name'            => 'emgrand-moments',
        'title'           => __('Emgrand - Momentos', 'theme-attach'),
        'description'     => __('Sección "Hecho para cada momento" con galería de imágenes del producto', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-moments.php',
        'category'        => 'layout',
        'icon'            => 'images-alt2',
        'keywords'        => ['emgrand', 'momentos', 'galeria', 'geely'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // NUEVO BLOQUE: Diseño Emgrand (Exterior / Interior)
    acf_register_block_type([
        'name'            => 'emgrand-design',
        'title'           => __('Emgrand - Diseño', 'theme-attach'),
        'description'     => __('Sección de diseño con pestañas Exterior / Interior y slider de imágenes', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-design.php',
        'category'        => 'layout',
        'icon'            => 'format-gallery',
        'keywords'        => ['emgrand', 'diseno', 'design', 'exterior', 'interior'],
        'supports'        => [
            'align' => false,
        ],
    ]);
}
add_action('acf/init', 'theme_attach_register_acf_blocks');

/**
 * Assets específicos de bloques Emgrand
 */
function emg_hero_assets()
{
    // Swiper (fallback si no viene del otro handle, pero lo mantenemos como lo tienes)
    wp_enqueue_style(
        'swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css'
    );

    wp_enqueue_script(
        'swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        [],
        null,
        true
    );

    // HERO
    wp_enqueue_style(
        'emg-hero-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-hero.css'
    );

    // CONFIGURADOR
    wp_enqueue_style(
        'emg-config-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-config.css',
        [],
        null
    );

    // MOMENTOS
    wp_enqueue_style(
        'emg-moments-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-moments.css',
        [],
        null
    );

    // NUEVO: DISEÑO
    wp_enqueue_style(
        'emg-design-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-design.css',
        [],
        null
    );

    // JS HERO
    wp_enqueue_script(
        'emg-hero-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-hero.js',
        ['swiper-js'],
        null,
        true
    );

    // JS CONFIGURADOR
    wp_enqueue_script(
        'emg-config-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-config.js',
        [],
        null,
        true
    );

    // JS MOMENTOS
    wp_enqueue_script(
        'emg-moments-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-moments.js',
        ['swiper-js'],
        null,
        true
    );

    // NUEVO: JS DISEÑO
    wp_enqueue_script(
        'emg-design-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-design.js',
        ['swiper-js'],
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'emg_hero_assets');
