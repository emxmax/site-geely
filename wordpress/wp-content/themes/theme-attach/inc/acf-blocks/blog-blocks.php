<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Registrar bloques ACF (Page / Reutilizables)
 */
function theme_attach_register_blog_blocks()
{

    if (!function_exists('acf_register_block_type')) {
        return;
    }

    // Bloque: Noticias Geely
    acf_register_block_type([
        'name' => 'blog-content',
        'title' => __('Pagina - Noticias Contenido', 'theme-attach'),
        'description' => __('Bloque de Contenido Principal', 'theme-attach'),
        'render_template' => 'template-parts/blocks-blog/blog-content.php',
        'category' => 'layout',
        'icon' => 'admin-post',
        'keywords' => ['blog', 'content', 'posts', 'noticias'],
        'supports' => [
            'align' => false,
        ],
    ]);

    // Bloque: Noticias Geely
    acf_register_block_type([
        'name' => 'blog-news',
        'title' => __('Pagina - Noticias', 'theme-attach'),
        'description' => __('Bloque de noticias con 3 posts', 'theme-attach'),
        'render_template' => 'template-parts/blocks-blog/blog-news.php',
        'category' => 'layout',
        'icon' => 'admin-post',
        'keywords' => ['blog', 'news', 'posts', 'noticias'],
        'supports' => [
            'align' => false,
        ],
    ]);
}
