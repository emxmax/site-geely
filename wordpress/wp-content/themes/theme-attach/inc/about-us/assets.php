<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Enqueue About Us Blocks Assets
 * 
 * @since 1.0.0
 */
function about_us_blocks_assets()
{
  $css_blocks = [
    'about-us-hero',
    'about-us-evolution',
    'about-us-journey',
    'about-us-values',
    'about-us-social',
    'about-us-social-impact',
    'about-us-tech',
  ];

  foreach ($css_blocks as $handle) {
    $rel = "/template-parts/blocks-about-us/{$handle}.css";
    $abs = get_stylesheet_directory() . $rel;

    if (file_exists($abs)) {
      wp_enqueue_style(
        "{$handle}-css",
        get_stylesheet_directory_uri() . $rel,
        [],
        filemtime($abs)
      );
    }
  }

  $js_blocks = [
    'about-us-evolution',
    'about-us-tech',
  ];

  foreach ($js_blocks as $handle) {
    $rel = "/assets/js/{$handle}.js";
    $abs = get_stylesheet_directory() . $rel;

    if (file_exists($abs)) {
      wp_enqueue_script(
        "{$handle}-js",
        get_stylesheet_directory_uri() . $rel,
        ['swiper'], // Dependencia de Swiper
        filemtime($abs),
        true
      );
    }
  }
}

add_action('wp_enqueue_scripts', 'about_us_blocks_assets');