<?php
if (! defined('ABSPATH')) exit;

/**
 * Registrar bloques ACF (Product)
 */
function theme_attach_register_product_blocks()
{

    if (! function_exists('acf_register_block_type')) {
        return;
    }

    // Bloque HERO Emgrand
    acf_register_block_type([
        'name'            => 'emgrand-hero',
        'title'           => __('Producto Bloque - Hero', 'theme-attach'),
        'description'     => __('Hero principal del producto', 'theme-attach'),
        'render_template' => 'template-parts/blocks-product/emgrand-hero.php',
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
        'title'           => __('Producto Bloque - Configurador', 'theme-attach'),
        'description'     => __('Configurador de versiones, colores y precios del modelo Producto', 'theme-attach'),
        'render_template' => 'template-parts/blocks-product/emgrand-config.php',
        'category'        => 'layout',
        'icon'            => 'admin-generic',
        'keywords'        => ['emgrand', 'config', 'versiones', 'colores'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // bloque: Momentos Emgrand
    acf_register_block_type([
        'name'            => 'emgrand-moments',
        'title'           => __('Producto Bloque - Momentos', 'theme-attach'),
        'description'     => __('Sección "Hecho para cada momento" con galería de imágenes del producto', 'theme-attach'),
        'render_template' => 'template-parts/blocks-product/emgrand-moments.php',
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
        'title'           => __('Producto Bloque - Diseño', 'theme-attach'),
        'description'     => __('Sección de diseño con pestañas Exterior / Interior y slider de imágenes', 'theme-attach'),
        'render_template' => 'template-parts/blocks-product/emgrand-design.php',
        'category'        => 'layout',
        'icon'            => 'format-gallery',
        'keywords'        => ['emgrand', 'diseno', 'design', 'exterior', 'interior'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Bloque EMGRAND – Tecnología Avanzada
    acf_register_block_type([
        'name'            => 'emgrand-tech',
        'title'           => __('Producto Bloque - Tecnología Avanzada', 'theme-attach'),
        'description'     => __('Bloque de tecnología con imagen y cards', 'theme-attach'),
        'render_template' => 'template-parts/blocks-product/emgrand-tech.php',
        'category'        => 'layout',
        'icon'            => 'admin-generic',
        'keywords'        => ['emgrand', 'tech', 'tecnologia'],
        'supports'        => ['align' => false],
    ]);

    // Bloque: Experiencia Única
    acf_register_block_type([
        'name'            => 'emgrand-experience',
        'title'           => __('Producto Bloque - Experiencia', 'theme-attach'),
        'description'     => __('Sección "Una experiencia única" con cards de imagen + título y fondo admin.', 'theme-attach'),
        'render_template' => 'template-parts/blocks-product/emgrand-experience.php',
        'category'        => 'layout',
        'icon'            => 'format-image',
        'keywords'        => ['emgrand', 'experience', 'experiencia', 'cards'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Bloque EMGRAND – Seguridad
    acf_register_block_type([
        'name'            => 'emgrand-safety',
        'title'           => __('Producto Bloque - Seguridad', 'theme-attach'),
        'description'     => __('Sección de seguridad con tabs y slider de tarjetas', 'theme-attach'),
        'render_template' => 'template-parts/blocks-product/emgrand-safety.php',
        'category'        => 'layout',
        'icon'            => 'shield',
        'keywords'        => ['emgrand', 'seguridad', 'safety'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Bloque EMGRAND – CTA final "El momento es ahora"
    acf_register_block_type([
        'name'            => 'emgrand-cta',
        'title'           => __('Producto Bloque - CTA Final', 'theme-attach'),
        'description'     => __('Bloque CTA con imagen de fondo, título, descripción y botón Cotizar', 'theme-attach'),
        'render_template' => 'template-parts/blocks-product/emgrand-cta.php',
        'category'        => 'layout',
        'icon'            => 'megaphone',
        'keywords'        => ['emgrand', 'cta', 'cotizar', 'geely'],
        'supports'        => [
            'align' => false,
        ],
    ]);
}
