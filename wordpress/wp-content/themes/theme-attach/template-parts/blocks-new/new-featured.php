<?php
/**
 * Bloque: Destacados (Noticias)
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field')) {
  return;
}

// Título de la sección
$section_title = get_field('featured_section_title');
if (empty($section_title)) {
  $section_title = 'DESTACADOS';
}

// Repeater de posts destacados
$featured_posts = get_field('featured_posts');

// Si no hay posts en ACF, obtener los últimos 3 posts de taxonomy "destacados"
if (empty($featured_posts)) {
  $query = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 3,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'tax_query' => [
      [
        'taxonomy' => 'post_status_label',
        'field' => 'slug',
        'terms' => 'destacados',
      ],
    ],
  ]);

  $featured_posts = [];
  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      $featured_posts[] = [
        'post' => get_the_ID(),
      ];
    }
    wp_reset_postdata();
  }
}

if (empty($featured_posts)) {
  return;
}

$uid = 'nf-' . wp_unique_id();
?>

<section class="new-featured">
  <div class="new-featured__header">
    <div class="new-featured__inner">
      <h2 class="new-featured__title title-3 title-sm-5"><?php echo esc_html($section_title); ?></h2>
    </div>
  </div>
  <div class="new-featured__slider-wrapper">
    <div class="new-featured__swiper swiper" id="<?php echo esc_attr($uid); ?>">
      <div class="swiper-wrapper">

        <?php foreach ($featured_posts as $item): ?>
          <?php
          $post_obj = null;

          // Si viene del ACF (relación de post)
          if (isset($item['post'])) {
            if (is_object($item['post'])) {
              $post_obj = $item['post'];
            } elseif (is_numeric($item['post'])) {
              $post_obj = get_post((int) $item['post']);
            }
          }

          if (!$post_obj)
            continue;

          $post_id = $post_obj->ID;
          $post_title = get_the_title($post_id);
          $post_date = get_the_date('j \O\c\t\u\b\r\e Y', $post_id);
          $post_excerpt = get_the_excerpt($post_id);
          $post_link = get_permalink($post_id);
          $post_image = get_the_post_thumbnail_url($post_id, 'large');

          if (empty($post_excerpt)) {
            $post_excerpt = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent vehicula, sed nonummy euismod, tempor et magna, efficitur placerat justo ac orci facilisis varius sit amet, consectetur adipiscing elit tempor et magna, efficitur';
          }
          if (!$post_image) {
            $post_image = 'https://placehold.co/670x380/png';
          }
          ?>

          <div class="new-featured__slide swiper-slide">
            <div class="new-featured__content">
              <div class="new-featured__text-content">
                <p class="new-featured__date paragraph-4 paragraph-sm-5"><?php echo esc_html($post_date); ?></p>
                <h3 class="new-featured__post-title paragraph-1 paragraph-sm-2">
                  <?php echo esc_html($post_title); ?>
                </h3>
                <?php if ($post_excerpt): ?>
                  <div class="new-featured__excerpt paragraph-4">
                    <?php echo esc_html($post_excerpt); ?>
                  </div>
                <?php endif; ?>
              </div>
              <a href="<?php echo esc_url($post_link); ?>" class="new-featured__cta paragraph-4">
                Ver más
              </a>
            </div>
            <div class="new-featured__media">
              <img src="<?php echo esc_url($post_image); ?>" alt="<?php echo esc_attr($post_title); ?>"
                class="new-featured__image">
            </div>
          </div>

        <?php endforeach; ?>

      </div>
    </div>
    <?php if (count($featured_posts) > 1): ?>
      <div class="new-featured__controls">
        <!-- Navegación -->
        <button class="new-featured__nav new-featured__nav--prev" aria-label="Anterior">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow-right.png'); ?>"
            alt="Anterior">
        </button>
        <!-- Paginación -->
        <div class="new-featured__pagination"></div>
        <button class="new-featured__nav new-featured__nav--next" aria-label="Siguiente">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow-right.png'); ?>"
            alt="Siguiente">
        </button>
      </div>
    <?php endif; ?>

  </div>
</section>

<script>
  (function () {
    document.addEventListener('DOMContentLoaded', function () {
      const swiperEl = document.querySelector('#<?php echo esc_js($uid); ?>');
      if (!swiperEl) return;
      new Swiper(swiperEl, {
        slidesPerView: 1,
        spaceBetween: 0,
        loop: false,

        navigation: {
          nextEl: '.new-featured__nav--next',
          prevEl: '.new-featured__nav--prev',
        },
        pagination: {
          el: '.new-featured__pagination',
          clickable: true,
        },
      });
    });
  })();
</script>