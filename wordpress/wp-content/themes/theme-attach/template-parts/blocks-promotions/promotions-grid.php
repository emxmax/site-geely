<?php
/**
 * Bloque: Grid de Promociones
 * 
 * Muestra promociones del CPT 'promocion' con paginación del lado del cliente
 */

if (!defined('ABSPATH'))
  exit;

// Campos ACF del bloque (opcionales para filtrar)
// $categoria_filter = get_field('promotions_category'); // Campo ACF tipo Taxonomy
$posts_per_page = 2; // 4 promociones por página (2x2 grid)

// Configurar query args
$args = [
  'post_type' => 'promocion',
  'post_status' => 'publish',
  'posts_per_page' => -1, // Obtener todas para paginar en cliente
  'orderby' => 'date',
  'order' => 'DESC',
  'tax_query' => [
    [
      'taxonomy' => 'categoria_promocion',
      'field' => 'slug',
      'terms' => 'ventas',
    ]
  ]
];

$q = new WP_Query($args);
if (!$q->have_posts()) {
  wp_reset_postdata();
  return;
}

$total_items = $q->post_count;
$total_pages = ceil($total_items / $posts_per_page);
?>

<section class="promotions-grid" data-total-pages="<?php echo esc_attr($total_pages); ?>">
  <div class="promotions-grid__inner">

    <div class="promotions-grid__wrapper js-promotions-grid">
      <?php
      $index = 0;
      while ($q->have_posts()):
        $q->the_post();

        // Calcular número de página para este item
        $page_number = floor($index / $posts_per_page) + 1;

        // Obtener datos del post
        $post_id = get_the_ID();
        $title = get_the_title();
        $image_id = get_post_thumbnail_id($post_id);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
        $image_alt = $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '';

        // Campos ACF del post promocion (si existen)
        $description = function_exists('get_field') ? get_field('promocion_card_text', $post_id) : '';
        // Fallback al excerpt si no hay texto personalizado
        if (empty($description)) {
          $description = get_the_excerpt();
        }

        $link_url = function_exists('get_field') ? get_field('promocion_link_url', $post_id) : '';
        $link_text = function_exists('get_field') ? get_field('promocion_link_text', $post_id) : '';
        $link_text = !empty($link_text) ? $link_text : 'Ver condiciones';

        // Si no hay link personalizado, usar el permalink del post
        if (empty($link_url)) {
          $link_url = get_permalink($post_id);
        }
        ?>
        <div class="promotions-grid__item js-promo-item" data-page="<?php echo esc_attr($page_number); ?>"
          style="<?php echo $page_number > 1 ? 'display: none;' : ''; ?>">
          <div class="promotions-grid__card">

            <?php if ($image_url): ?>
              <div class="promotions-grid__image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt ?: $title); ?>"
                  loading="lazy">
              </div>
            <?php endif; ?>

            <div class="promotions-grid__content">
              <?php if ($title): ?>
                <h3 class="promotions-grid__title title-5"><?php echo esc_html($title); ?></h3>
              <?php endif; ?>

              <?php if ($description): ?>
                <div class="promotions-grid__description paragraph-4">
                  <?php echo wp_kses_post(wpautop($description)); ?>
                </div>
              <?php endif; ?>

              <?php if ($link_url): ?>
                <a href="<?php echo esc_url($link_url); ?>" class="promotions-grid__link" target="_blank" rel="noopener">
                  <?php echo esc_html($link_text); ?>
                </a>
              <?php endif; ?>
            </div>

          </div>
        </div>
        <?php
        $index++;
      endwhile;
      wp_reset_postdata();
      ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="promotions-grid__pagination">
        <!-- Flecha izquierda -->
        <button type="button" class="promotions-grid__nav promotions-grid__nav--prev js-promo-prev" data-page="1"
          disabled>
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow-right.png'); ?>"
            alt="Anterior">
        </button>

        <!-- Números de página -->
        <div class="promotions-grid__pages js-promo-pages">
          <?php for ($i = 1; $i <= min(3, $total_pages); $i++): ?>
            <button type="button" class="promotions-grid__page js-promo-page <?php echo $i === 1 ? 'is-active' : ''; ?>"
              data-page="<?php echo $i; ?>">
              <?php echo $i; ?>
            </button>
          <?php endfor; ?>

          <?php if ($total_pages > 5): ?>
            <span class="promotions-grid__dots">...</span>
          <?php endif; ?>

          <?php if ($total_pages > 3): ?>
            <?php for ($i = max(4, $total_pages - 1); $i <= $total_pages; $i++): ?>
              <button type="button" class="promotions-grid__page js-promo-page" data-page="<?php echo $i; ?>">
                <?php echo $i; ?>
              </button>
            <?php endfor; ?>
          <?php endif; ?>
        </div>

        <!-- Flecha derecha -->
        <button type="button" class="promotions-grid__nav promotions-grid__nav--next js-promo-next" data-page="1" <?php echo $total_pages <= 1 ? 'disabled' : ''; ?>>
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow-right.png'); ?>"
            alt="Siguiente">
        </button>
      </div>
    <?php endif; ?>
  </div>
</section>