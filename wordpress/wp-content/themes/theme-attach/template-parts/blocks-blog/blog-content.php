<?php
if (!defined('ABSPATH'))
  exit;

$is_single_post = is_singular('post');
if (!$is_single_post)
  return;

$block_content = get_field(
  'blog_content'
) ?: '';
$title = get_the_title();
?>
<section class="blog-content">
  <div class="blog-content__inner">

    <div class="blog-content__header">
      <p class="blog-content__date paragraph-4">
        <?= esc_html(get_the_date('j F Y')); ?>
      </p>
      <h1 class="blog-content__title title-3 title-mobile-sm-2"><?= esc_html($title); ?></h1>
    </div>

    <?php if (has_post_thumbnail()): ?>
      <div class="blog-content__featured-image">
        <?php the_post_thumbnail('large', [
          'alt' => esc_attr($title),
          'class' => 'blog-content__image',
        ]); ?>
      </div>
    <?php endif; ?>
    <div class="blog-content__content">
      <?= wp_kses_post($block_content); ?>
    </div>
  </div>
</section>