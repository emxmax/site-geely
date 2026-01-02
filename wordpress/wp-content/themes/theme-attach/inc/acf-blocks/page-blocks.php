<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Registrar bloques ACF (Page / Reutilizables)
 */
function theme_attach_register_page_blocks()
{

  if (!function_exists('acf_register_block_type')) {
    return;
  }

  // Bloque: Carrusel de Experiencias (reutilizable)
  acf_register_block_type([
    'name' => 'page-experience-carousel',
    'title' => __('Pagina - Carrusel de Experiencias', 'theme-attach'),
    'description' => __('Carrusel reutilizable con imagen, título, descripción y botón.', 'theme-attach'),
    'render_template' => 'template-parts/blocks-page/experience-carousel.php',
    'category' => 'layout',
    'icon' => 'slides',
    'keywords' => ['carrusel', 'slider', 'experiencias', 'cards', 'pagina'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Sección con Imagen y Compromisos (reutilizable)
  acf_register_block_type([
    'name' => 'page-image-commitments',
    'title' => __('Pagina - Sección con Imagen y Compromisos', 'theme-attach'),
    'description' => __('Sección reusable con imagen grande, fondo y carrusel de compromisos.', 'theme-attach'),
    'render_template' => 'template-parts/blocks-page/image-commitments.php',
    'category' => 'layout',
    'icon' => 'align-pull-left',
    'keywords' => ['compromiso', 'imagen', 'seccion', 'slider', 'cards'],
    'supports' => ['align' => false],
  ]);

  // Bloque: Encuentra tu Geely hoy
  acf_register_block_type([
    'name' => 'page-find-geely',
    'title' => __('Pagina - Encuentra tu Geely', 'theme-attach'),
    'description' => __('Bloque con texto, botón e imagen decorativa', 'theme-attach'),
    'render_template' => 'template-parts/blocks-page/find-geely.php',
    'category' => 'layout',
    'icon' => 'location-alt',
    'keywords' => ['geely', 'mapa', 'ubicacion', 'cta'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Preguntas Frecuentes (FAQ)
  acf_register_block_type([
    'name' => 'page-faq',
    'title' => __('Pagina - Preguntas Frecuentes', 'theme-attach'),
    'description' => __('Bloque de preguntas frecuentes tipo acordeón', 'theme-attach'),
    'render_template' => 'template-parts/blocks-page/faq.php',
    'category' => 'layout',
    'icon' => 'editor-help',
    'keywords' => ['faq', 'preguntas', 'accordion'],
    'supports' => [
      'align' => false,
    ],
  ]);

  //  Bloque: Carrusel Hero
  acf_register_block_type([
    'name' => 'hero-carousel',
    'title' => __('Pagina - Carrusel Hero', 'theme-attach'),
    'description' => __('Carrusel principal con imágenes desktop y mobile', 'theme-attach'),
    'render_template' => 'template-parts/blocks-page/hero-carousel.php',
    'category' => 'layout',
    'icon' => 'images-alt2',
    'keywords' => ['hero', 'carousel', 'slider'],
    'supports' => [
      'align' => false,
    ],
  ]);

  acf_register_block_type([
    'name' => 'future-hero',
    'title' => 'Pagina - Banner Simple Hero',
    'description' => 'Hero con fondo desktop/mobile, título y descripción.',
    'category' => 'formatting',
    'icon' => 'cover-image',
    'keywords' => ['geely', 'hero', 'banner'],
    'mode' => 'preview',
    'supports' => ['anchor' => true],
    'render_template' => 'template-parts/blocks-page/geely-future.php',
  ]);

  // Bloque: Red de Atención (Localizador de Tiendas)
  // acf_register_block_type([
  //   'name' => 'page-stores-locator',
  //   'title' => __('Pagina - Red de Atención', 'theme-attach'),
  //   'description' => __('Localizador de tiendas con mapa interactivo y filtros', 'theme-attach'),
  //   'render_template' => 'template-parts/blocks-page/stores-locator.php',
  //   'category' => 'layout',
  //   'icon' => 'location',
  //   'keywords' => ['tienda', 'mapa', 'localizador', 'red', 'atencion'],
  //   'supports' => [
  //     'align' => false,
  //   ],
  // ]);
}
