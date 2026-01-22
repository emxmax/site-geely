<?php
if (!defined('ABSPATH'))
  exit;

$block_title = get_field('blog_news_title') ?: 'NOTICIAS GEELY';
$block_description = get_field('blog_news_description') ?: '';
$blog_btn_text = get_field('blog_news_btn_text') ?: 'Más noticias';
$posts_per_page = 3;

// Verificar si estamos en una entrada individual
$is_single_post = is_singular('post');

// Excluir el post actual si estamos en una entrada individual
$exclude_posts = [];
if ($is_single_post) {
  $exclude_posts = [get_the_ID()];
}

$q = new WP_Query([
  'post_type' => 'post',
  'post_status' => 'publish',
  'posts_per_page' => $posts_per_page,
  'paged' => 1,
  'orderby' => 'date',
  'order' => 'DESC',
  'post__not_in' => $exclude_posts,
]);

$total_pages = (int) $q->max_num_pages;
?>

<section class="blog-news" data-total-pages="<?php echo esc_attr($total_pages); ?>">
  <div class="blog-news__inner">
    <h2 class="blog-news__title"><?php echo esc_html($block_title); ?></h2>
    <div class="blog-news__description"><?php echo esc_html($block_description); ?></div>
    <div class="blog-news__grid js-blog-news-grid">
      <?php while ($q->have_posts()):
        $q->the_post(); ?>
        <?php get_template_part('template-parts/blocks-blog/partials/blog-news-card'); ?>
      <?php endwhile;
      wp_reset_postdata(); ?>
    </div>
    <?php if ($total_pages > 1): ?>
      <div class="blog-news__cta">        
        <a class="blog-news__btn js-blog-news-loadmore" data-page="1" href="<?= home_url('/noticias'); ?>">
          <?= esc_html($blog_btn_text); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>