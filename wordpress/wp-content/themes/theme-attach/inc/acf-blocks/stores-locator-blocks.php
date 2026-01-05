<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Registrar bloques ACF (Page / Stores Locator)
 */
function theme_attach_register_page_stores_locator_blocks()
{
  if (!function_exists('acf_register_block_type')) {
    return;
  }

  // Bloque: Red de Atención (Localizador de Tiendas)  
  acf_register_block_type([
    'name' => 'page-stores-locator',
    'title' => __('Pagina - Red de Atención', 'theme-attach'),
    'description' => __('Localizador de tiendas con mapa interactivo y filtros', 'theme-attach'),
    'render_template' => 'template-parts/blocks-stores-locator/stores-locator.php',
    'category' => 'layout',
    'icon' => 'location',
    'keywords' => ['tienda', 'mapa', 'localizador', 'red', 'atencion'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Elige tu Geely (Carrusel de Productos)
  acf_register_block_type([
    'name' => 'choose-your-geely',
    'title' => __('Red Atención - Elige tu Geely', 'theme-attach'),
    'description' => __('Carrusel de productos con precios y enlaces de cotización', 'theme-attach'),
    'render_template' => 'template-parts/blocks-stores-locator/choose-your-geely.php',
    'category' => 'layout',
    'icon' => 'car',
    'keywords' => ['productos', 'carrusel', 'autos', 'geely', 'elige'],
    'supports' => [
      'align' => false,
    ],
  ]);
}
