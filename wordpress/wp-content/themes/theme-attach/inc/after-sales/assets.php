<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Assets específicos de bloques POSTVENTA
 */
function page_after_sales_assets()
{
  /**
   * =========================
   * CSS de bloques 
   * =========================
   * Convención:
   * template-parts/blocks-after-sales/{block-name}.css
   */
  $css_blocks = [
    "after-sales-hero",
    "after-sales-benefits",
    "after-sales-services",
    "after-sales-testimonials",
    "after-sales-appointment",
    "after-sales-warranty",
  ];

  foreach ($css_blocks as $handle) {
    $rel = "/template-parts/blocks-after-sales/{$handle}.css";
    $abs = get_stylesheet_directory() . $rel;

    wp_enqueue_style(
      "page-{$handle}-css",
      get_stylesheet_directory_uri() . $rel,
      [],
      file_exists($abs) ? filemtime($abs) : null
    );
  }

  /**
   * =========================
   * JS de bloques PAGE
   * =========================
   * Convención:
   * assets/js/{block-name}.js
   */
  $js_blocks = [
  ];

  foreach ($js_blocks as $handle) {
    $rel = "/assets/js/{$handle}.js";
    $abs = get_stylesheet_directory() . $rel;

    wp_enqueue_script(
      "page-{$handle}-js",
      get_stylesheet_directory_uri() . $rel,
      ['swiper'],
      file_exists($abs) ? filemtime($abs) : null,
      true
    );
  }
}
add_action(
  'wp_enqueue_scripts',
  'page_after_sales_assets'
);

