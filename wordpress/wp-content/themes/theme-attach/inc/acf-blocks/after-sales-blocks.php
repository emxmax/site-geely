<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Registrar bloques ACF (Post-Venta / After Sales)
 */
function theme_attach_register_after_sales_blocks()
{
  if (!function_exists('acf_register_block_type')) {
    return;
  }

  // Bloque: Hero Post-Venta
  acf_register_block_type([
    'name' => 'after-sales-hero',
    'title' => __('Post-Venta - Hero', 'theme-attach'),
    'description' => __('Hero principal de página de post-venta', 'theme-attach'),
    'render_template' => 'template-parts/blocks-after-sales/after-sales-hero.php',
    'category' => 'layout',
    'icon' => 'admin-tools',
    'keywords' => ['postventa', 'hero', 'servicio'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Beneficios Post-Venta
  acf_register_block_type([
    'name' => 'after-sales-benefits',
    'title' => __('Post-Venta - Beneficios', 'theme-attach'),
    'description' => __('Sección de beneficios del servicio post-venta', 'theme-attach'),
    'render_template' => 'template-parts/blocks-after-sales/after-sales-benefits.php',
    'category' => 'layout',
    'icon' => 'awards',
    'keywords' => ['postventa', 'beneficios', 'servicio'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Servicios Post-Venta
  acf_register_block_type([
    'name' => 'after-sales-services',
    'title' => __('Post-Venta - Servicios', 'theme-attach'),
    'description' => __('Carousel de servicios disponibles', 'theme-attach'),
    'render_template' => 'template-parts/blocks-after-sales/after-sales-services.php',
    'category' => 'layout',
    'icon' => 'admin-settings',
    'keywords' => ['postventa', 'servicios', 'carousel'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Testimonios Post-Venta
  acf_register_block_type([
    'name' => 'after-sales-testimonials',
    'title' => __('Post-Venta - Testimonios', 'theme-attach'),
    'description' => __('Testimonios de clientes sobre el servicio', 'theme-attach'),
    'render_template' => 'template-parts/blocks-after-sales/after-sales-testimonials.php',
    'category' => 'layout',
    'icon' => 'testimonial',
    'keywords' => ['postventa', 'testimonios', 'clientes'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Agendar Cita Post-Venta
  acf_register_block_type([
    'name' => 'after-sales-appointment',
    'title' => __('Post-Venta - Agendar Cita', 'theme-attach'),
    'description' => __('Sección con mapa para agendar citas', 'theme-attach'),
    'render_template' => 'template-parts/blocks-after-sales/after-sales-appointment.php',
    'category' => 'layout',
    'icon' => 'calendar-alt',
    'keywords' => ['postventa', 'cita', 'agendar', 'mapa'],
    'supports' => [
      'align' => false,
    ],
  ]);

  // Bloque: Garantía Post-Venta
  acf_register_block_type([
    'name' => 'after-sales-warranty',
    'title' => __('Post-Venta - Garantía', 'theme-attach'),
    'description' => __('Información sobre garantía y seguridad', 'theme-attach'),
    'render_template' => 'template-parts/blocks-after-sales/after-sales-warranty.php',
    'category' => 'layout',
    'icon' => 'shield',
    'keywords' => ['postventa', 'garantia', 'seguridad'],
    'supports' => [
      'align' => false,
    ],
  ]);
}
