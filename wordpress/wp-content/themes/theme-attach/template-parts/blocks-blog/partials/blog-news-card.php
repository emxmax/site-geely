<?php if (!defined('ABSPATH')) exit; ?>

<article class="blog-news__card">

    <?php if (has_post_thumbnail()) : ?>
        <a class="blog-news__media" href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail('large'); ?>
        </a>
    <?php endif; ?>

    <?php
    $cats = get_the_category();
    $cat  = (!empty($cats) && !is_wp_error($cats)) ? $cats[0] : null;
    ?>

    <div class="blog-news__meta">
        <span class="blog-news__date">
            <?php echo esc_html(get_the_date('d F Y')); ?>
        </span>

        <?php if ($cat): ?>
            <a class="blog-news__cat" href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                <?php echo esc_html($cat->name); ?>
            </a>
        <?php endif; ?>
    </div>

    <h3 class="blog-news__card-title">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h3>

    <?php $excerpt = get_the_excerpt(); ?>
    <?php if ($excerpt): ?>
        <p class="blog-news__excerpt"><?php echo esc_html($excerpt); ?></p>
    <?php endif; ?>

    <a class="blog-news__link" href="<?php the_permalink(); ?>">
        Ver más
        <!-- <img
            src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
            alt="Next"> -->
    </a>
</article>