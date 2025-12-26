<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Constante para posts por página en new-about
 */
if (!defined('NEW_ABOUT_POSTS_PER_PAGE')) {
  define('NEW_ABOUT_POSTS_PER_PAGE', 3);
}

/**
 * Assets específicos de bloques PAGE
 */
function page_new_blocks_assets()
{

  /**
   * =========================
   * CSS de bloques PAGE New
   * =========================
   * Convención:
   * template-parts/blocks-new/{block-name}.css
   */
  $css_blocks = [
    'new-hero',
    'new-featured',
    'new-about',
    'new-innovation'
  ];

  foreach ($css_blocks as $handle) {
    $rel = "/template-parts/blocks-new/{$handle}.css";
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
    'new-featured',
    'new-about',
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

  // Localize script for AJAX (new-about)
  wp_localize_script(
    'page-new-about-js',
    'NEW_ABOUT',
    [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('new_about_nonce'),
    ]
  );
}
add_action('wp_enqueue_scripts', 'page_new_blocks_assets');

// AJAX handler for new-about pagination
add_action('wp_ajax_new_about_load_page', 'new_about_load_page');
add_action('wp_ajax_nopriv_new_about_load_page', 'new_about_load_page');

function new_about_load_page()
{
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'new_about_nonce')) {
    wp_send_json_error(['message' => 'Invalid nonce'], 403);
  }

  $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;

  $q = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => NEW_ABOUT_POSTS_PER_PAGE,
    'paged' => $page,
    'orderby' => 'date',
    'order' => 'DESC',
  ]);

  ob_start();
  while ($q->have_posts()):
    $q->the_post();
    get_template_part('template-parts/blocks-blog/partials/blog-news-card');
  endwhile;
  wp_reset_postdata();

  wp_send_json_success(['html' => ob_get_clean()]);
}
