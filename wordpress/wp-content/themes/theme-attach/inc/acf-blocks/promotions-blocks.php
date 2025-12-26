<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Registrar bloques ACF (Promociones)
 */
function theme_attach_register_promotions_blocks()
{
  if (!function_exists('acf_register_block_type')) {
    return;
  }

  // Bloque: Hero Promociones
  acf_register_block_type([
    'name' => 'promotions-hero',
    'title' => __('Promociones - Hero', 'theme-attach'),
    'description' => __('Hero principal de página de promociones con tabs', 'theme-attach'),
    'render_template' => 'template-parts/blocks-promotions/promotions-hero.php',
    'category' => 'layout',
    'icon' => 'megaphone',
    'keywords' => ['promociones', 'hero', 'geely'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Grid de Promociones
  acf_register_block_type([
    'name' => 'promotions-grid',
    'title' => __('Promociones - Grid', 'theme-attach'),
    'description' => __('Grid de promociones con paginación', 'theme-attach'),
    'render_template' => 'template-parts/blocks-promotions/promotions-grid.php',
    'category' => 'layout',
    'icon' => 'grid-view',
    'keywords' => ['promociones', 'grid', 'geely'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Hero Single Promoción
  acf_register_block_type([
    'name' => 'promotions-single-hero',
    'title' => __('Promoción Single - Hero', 'theme-attach'),
    'description' => __('Hero para el detalle de una promoción individual', 'theme-attach'),
    'render_template' => 'template-parts/blocks-promotions/promotions-single-hero.php',
    'category' => 'layout',
    'icon' => 'megaphone',
    'keywords' => ['promocion', 'hero', 'single'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Contenido Single Promoción
  acf_register_block_type([
    'name' => 'promotions-single',
    'title' => __('Promoción Single - Contenido', 'theme-attach'),
    'description' => __('Muestra imagen principal y contenido de la promoción', 'theme-attach'),
    'render_template' => 'template-parts/blocks-promotions/promotions-single.php',
    'category' => 'layout',
    'icon' => 'media-document',
    'keywords' => ['promocion', 'contenido', 'single'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Formulario Promoción
  acf_register_block_type([
    'name' => 'promotions-form',
    'title' => __('Promoción Single - Formulario', 'theme-attach'),
    'description' => __('Formulario de Contact Form 7 para promociones', 'theme-attach'),
    'render_template' => 'template-parts/blocks-promotions/promotions-form.php',
    'category' => 'layout',
    'icon' => 'feedback',
    'keywords' => ['promocion', 'formulario', 'contact form 7'],
    'supports' => [
      'align' => false,
    ],
  ]);
}

add_action('acf/init', 'theme_attach_register_promotions_blocks');
