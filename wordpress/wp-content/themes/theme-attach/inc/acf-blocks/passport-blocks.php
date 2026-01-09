<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Registrar bloques ACF (Pasaportes de Servicio)
 */
function theme_attach_register_passport_blocks()
{
  if (!function_exists('acf_register_block_type')) {
    return;
  }

  // Bloque: Hero Pasaportes
  acf_register_block_type([
    'name' => 'passport-hero',
    'title' => __('Pasaportes - Hero', 'theme-attach'),
    'description' => __('Hero principal de página de pasaportes de servicio', 'theme-attach'),
    'render_template' => 'template-parts/blocks-passport/passport-hero.php',
    'category' => 'layout',
    'icon' => 'media-document',
    'keywords' => ['pasaporte', 'hero', 'geely'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Catálogo de Pasaportes
  acf_register_block_type([
    'name' => 'passport-catalog',
    'title' => __('Pasaportes - Catálogo', 'theme-attach'),
    'description' => __('Catálogo de pasaportes de servicio con filtros', 'theme-attach'),
    'render_template' => 'template-parts/blocks-passport/passport-catalog.php',
    'category' => 'layout',
    'icon' => 'grid-view',
    'keywords' => ['pasaporte', 'catalogo', 'geely'],
    'supports' => [
      'align' => false,
    ],
  ]);
}
add_action('acf/init', 'theme_attach_register_passport_blocks');
