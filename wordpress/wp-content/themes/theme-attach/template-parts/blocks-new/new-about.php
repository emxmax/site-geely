<?php
/**
 * Bloque: Conoce más sobre Geely
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field')) {
  return;
}

$block_title = get_field('about_title') ?: 'CONOCE MÁS SOBRE GEELY';
$block_description = get_field('about_description') ?: 'Conoce las últimas noticias sobre lanzamientos, tecnología, la compañía, etc';
$posts_per_page = defined('NEW_ABOUT_POSTS_PER_PAGE') ? NEW_ABOUT_POSTS_PER_PAGE : 6;

$q = new WP_Query([
  'post_type' => 'post',
  'post_status' => 'publish',
  'posts_per_page' => $posts_per_page,
  'paged' => 1,
  'orderby' => 'date',
  'order' => 'DESC',
]);

$total_pages = (int) $q->max_num_pages;
?>

<section class="new-about" data-total-pages="<?php echo esc_attr($total_pages); ?>">
  <div class="new-about__inner">

    <div class="new-about__header">
      <div class="new-about__header-content">
        <h2 class="new-about__title title-3"><?php echo esc_html($block_title); ?></h2>
        <p class="new-about__description paragraph-2"><?php echo esc_html($block_description); ?></p>
      </div>
    </div>

    <div class="new-about__grid js-new-about-grid">
      <?php while ($q->have_posts()):
        $q->the_post(); ?>
        <?php get_template_part(
          'template-parts/blocks-blog/partials/blog-news-card'
        ); ?>
      <?php endwhile;
      wp_reset_postdata(); ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="new-about__pagination">
        <!-- Flecha izquierda -->
        <button type="button" class="new-about__nav new-about__nav--prev js-new-about-prev" data-page="1" disabled>
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow-right.png'); ?>"
            alt="Anterior">
        </button>

        <!-- Números de página -->
        <div class="new-about__pages js-new-about-pages">
          <?php for ($i = 1; $i <= min(3, $total_pages); $i++): ?>
            <button type="button" class="new-about__page js-new-about-page <?php echo $i === 1 ? 'is-active' : ''; ?>"
              data-page="<?php echo $i; ?>">
              <?php echo $i; ?>
            </button>
          <?php endfor; ?>

          <?php if ($total_pages > 5): ?>
            <span class="new-about__dots">...</span>
          <?php endif; ?>

          <?php if ($total_pages > 3): ?>
            <?php for ($i = max(4, $total_pages - 1); $i <= $total_pages; $i++): ?>
              <button type="button" class="new-about__page js-new-about-page" data-page="<?php echo $i; ?>">
                <?php echo $i; ?>
              </button>
            <?php endfor; ?>
          <?php endif; ?>
        </div>

        <!-- Flecha derecha -->
        <button type="button" class="new-about__nav new-about__nav--next js-new-about-next" data-page="1" <?php echo $total_pages <= 1 ? 'disabled' : ''; ?>>
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow-right.png'); ?>"
            alt="Siguiente">
        </button>
      </div>
    <?php endif; ?>

  </div>
</section>