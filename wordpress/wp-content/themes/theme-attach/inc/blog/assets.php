<?php
if (!defined('ABSPATH'))
  exit;

function blog_blocks_assets()
{

  // CSS
  $css_blocks = [
    'blog-news',
    'blog-content'
  ];
  foreach ($css_blocks as $handle) {
    $rel = "/template-parts/blocks-blog/{$handle}.css";
    $abs = get_stylesheet_directory() . $rel;

    wp_enqueue_style(
      "{$handle}-css",
      get_stylesheet_directory_uri() . $rel,
      [],
      file_exists($abs) ? filemtime($abs) : null
    );
  }

  // JS
  $js_rel = '/assets/js/blog-news.js';
  $js_abs = get_stylesheet_directory() . $js_rel;

  wp_enqueue_script(
    'blog-news-js',
    get_stylesheet_directory_uri() . $js_rel,
    [],
    file_exists($js_abs) ? filemtime($js_abs) : null,
    true
  );

  wp_localize_script('blog-news-js', 'BLOG_NEWS', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('blog_news_nonce'),
  ]);
}
add_action('wp_enqueue_scripts', 'blog_blocks_assets');

add_action('wp_ajax_blog_news_load_more', 'blog_news_load_more');
add_action('wp_ajax_nopriv_blog_news_load_more', 'blog_news_load_more');

function blog_news_load_more()
{
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'blog_news_nonce')) {
    wp_send_json_error(['message' => 'Invalid nonce'], 403);
  }

  $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;

  $q = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 3,
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
