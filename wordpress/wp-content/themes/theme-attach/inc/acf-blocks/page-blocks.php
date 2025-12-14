<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Registrar bloques ACF (Page / Reutilizables)
 */
function theme_attach_register_page_blocks() {

  if ( ! function_exists('acf_register_block_type') ) {
    return;
  }

  // Bloque: Carrusel de Experiencias (reutilizable)
  acf_register_block_type([
    'name'            => 'page-experience-carousel',
    'title'           => __('Pagina - Carrusel de Experiencias', 'theme-attach'),
    'description'     => __('Carrusel reutilizable con imagen, título, descripción y botón.', 'theme-attach'),
    'render_template' => 'template-parts/blocks-page/experience-carousel.php',
    'category'        => 'layout',
    'icon'            => 'slides',
    'keywords'        => ['carrusel', 'slider', 'experiencias', 'cards', 'pagina'],
    'supports'        => [
      'align' => false,
    ],
  ]);

  // Bloque: Sección con Imagen y Compromisos (reutilizable)
  acf_register_block_type([
  'name'            => 'page-image-commitments',
  'title'           => __('Pagina - Sección con Imagen y Compromisos', 'theme-attach'),
  'description'     => __('Sección reusable con imagen grande, fondo y carrusel de compromisos.', 'theme-attach'),
  'render_template' => 'template-parts/blocks-page/image-commitments.php',
  'category'        => 'layout',
  'icon'            => 'align-pull-left',
  'keywords'        => ['compromiso', 'imagen', 'seccion', 'slider', 'cards'],
  'supports'        => ['align' => false],
]);

}
