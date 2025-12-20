<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Registrar bloques ACF (Noticias)
 */
function theme_attach_register_page_new_blocks()
{
  if (!function_exists('acf_register_block_type')) {
    return;
  }

  // Bloque: Hero Noticias
  acf_register_block_type([
    'name' => 'new-hero',
    'title' => __('Noticias - Hero', 'theme-attach'),
    'description' => __('Hero principal de página de noticias', 'theme-attach'),
    'render_template' => 'template-parts/blocks-new/new-hero.php',
    'category' => 'layout',
    'icon' => 'megaphone',
    'keywords' => ['noticias', 'hero', 'geely'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Destacados
  acf_register_block_type([
    'name' => 'new-featured',
    'title' => __('Noticias - Destacados', 'theme-attach'),
    'description' => __('Sección de noticias destacadas con carrusel', 'theme-attach'),
    'render_template' => 'template-parts/blocks-new/new-featured.php',
    'category' => 'layout',
    'icon' => 'star-filled',
    'keywords' => ['noticias', 'destacados', 'carousel'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Conoce más sobre Geely
  acf_register_block_type([
    'name' => 'new-about',
    'title' => __('Noticias - Conoce más sobre Geely', 'theme-attach'),
    'description' => __('Grid de noticias con paginación', 'theme-attach'),
    'render_template' => 'template-parts/blocks-new/new-about.php',
    'category' => 'layout',
    'icon' => 'grid-view',
    'keywords' => ['noticias', 'grid', 'geely'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Innovación que mueve
  acf_register_block_type([
    'name' => 'new-innovation',
    'title' => __('Noticias - Innovación que mueve', 'theme-attach'),
    'description' => __('Sección de innovación con imagen de auto', 'theme-attach'),
    'render_template' => 'template-parts/blocks-new/new-innovation.php',
    'category' => 'layout',
    'icon' => 'lightbulb',
    'keywords' => ['noticias', 'innovacion', 'geely'],
    'supports' => [
      'align' => false,
    ],
  ]);
}