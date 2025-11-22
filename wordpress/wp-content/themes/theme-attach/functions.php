<?php

/**
 * Funciones del tema Theme Attach
 */

if (! defined('ABSPATH')) {
    exit; // Seguridad básica
}

/**
 * Encolar estilos y scripts del tema
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

    // Swiper CSS
    wp_enqueue_style(
        'swiper',
        'https://unpkg.com/swiper/swiper-bundle.min.css',
        [],
        '11.0.0'
    );

    // Swiper JS
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
}
add_action('acf/init', 'theme_attach_register_acf_blocks');

function emg_hero_assets()
{

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

    wp_enqueue_style(
        'emg-hero-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-hero.css'
    );

    wp_enqueue_script(
        'emg-hero-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-hero.js',
        ['swiper-js'],
        null,
        true
    );
}

add_action('wp_enqueue_scripts', 'emg_hero_assets');
