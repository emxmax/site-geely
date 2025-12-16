<?php
if (!defined('ABSPATH')) exit;

$block_title = get_field('blog_news_title') ?: 'NOTICIAS GEELY';
$posts_per_page = 3;

$q = new WP_Query([
  'post_type'      => 'post',
  'post_status'    => 'publish',
  'posts_per_page' => $posts_per_page,
  'paged'          => 1,
  'orderby'        => 'date',
  'order'          => 'DESC',
]);

$total_pages = (int) $q->max_num_pages;
?>

<section class="blog-news" data-total-pages="<?php echo esc_attr($total_pages); ?>">
  <div class="blog-news__inner">

    <h2 class="blog-news__title"><?php echo esc_html($block_title); ?></h2>

    <div class="blog-news__grid js-blog-news-grid">
      <?php while ($q->have_posts()) : $q->the_post(); ?>
        <?php get_template_part('template-parts/blocks-blog/partials/blog-news-card'); ?>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="blog-news__cta">
        <button type="button" class="blog-news__btn js-blog-news-loadmore" data-page="1">
          Ver más
        </button>
      </div>
    <?php endif; ?>

  </div>
</section>
