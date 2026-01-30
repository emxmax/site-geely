<?php

/**
 * Bloque: Catálogo de Pasaportes de Servicio
 * 
 * Muestra pasaportes del CPT 'pasaporte' con filtros por categoría
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

// Usar constante global de pasaportes por página
$posts_per_page = defined('PASSPORTS_PER_PAGE') ? PASSPORTS_PER_PAGE : 6;

// Obtener categorías
$categories = get_terms([
  'taxonomy' => 'categoria_pasaporte',
  'hide_empty' => true,
  'orderby' => 'name',
  'order' => 'ASC',
]);

if (is_wp_error($categories)) {
  $categories = [];
}

$initial_category_slug = 'todos';

// Configurar query args
$args = [
  'post_type' => 'pasaporte',
  'post_status' => 'publish',
  'posts_per_page' => -1, // Obtener todos para paginar en cliente
  'orderby' => 'date',
  'order' => 'DESC',
];

$q = new WP_Query($args);
if (!$q->have_posts()) {
  wp_reset_postdata();
  return;
}

$total_items = $q->post_count;
$total_pages = ceil($total_items / $posts_per_page);

$has_categories = !empty($categories);
?>

<section class="passport-catalog" data-total-pages="<?= esc_attr($total_pages); ?>">
  <div class="passport-catalog__inner">

    <?php if ($has_categories): ?>
      <div class="passport-catalog__tabs">
        <button type="button"
          class="paragraph-2 paragraph-sm-4 passport-catalog__tab passport-catalog__tab--active js-passport-tab"
          data-tab="todos">
          <span>Todos</span>
        </button>

        <?php
        foreach ($categories as $category):
          if (!is_object($category))
            continue;
        ?>
          <button type="button" class="paragraph-2 paragraph-sm-4 passport-catalog__tab js-passport-tab"
            data-tab="<?= esc_attr($category->slug); ?>">
            <span><?= esc_html($category->name); ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="passport-catalog__grid js-passport-grid">
      <?php
      $index = 0;
      while ($q->have_posts()):
        $q->the_post();

        $page_number = floor($index / $posts_per_page) + 1;
        $post_id = get_the_ID();
        $title = get_the_title();
        $image_id = get_post_thumbnail_id($post_id);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
        $image_alt = $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '';

        // Obtener el PDF del pasaporte
        $service_passport = get_field('service_passport', $post_id);
        $pdf_url = '';

        if ($service_passport) {
          if (is_array($service_passport)) {
            $pdf_url = $service_passport['url'] ?? '';
          } elseif (is_numeric($service_passport)) {
            $pdf_url = wp_get_attachment_url($service_passport) ?: '';
          } else {
            $pdf_url = $service_passport;
          }
        }
      ?>
        <div class="passport-catalog__item js-passport-item" data-page="<?= esc_attr($page_number); ?>"
          style="<?= $page_number > 1 ? 'display: none;' : ''; ?>">

          <div class="passport-catalog__card">

            <?php if ($pdf_url): ?>
              <a
                href="<?php echo esc_url($pdf_url); ?>"
                class="passport-catalog__link-overlay"
                target="_blank"
                rel="noopener"
                aria-label="<?php echo esc_attr($title ?: 'Pasaporte de servicios'); ?>">
              </a>
            <?php endif; ?>

            <?php if ($title): ?>
              <h3 class="passport-catalog__title title-6 title-mobile-sm-4">
                <?= esc_html($title); ?>
              </h3>
            <?php endif; ?>

            <?php if ($image_url): ?>
              <div class="passport-catalog__image">
                <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($image_alt ?: $title); ?>" loading="lazy">
              </div>
            <?php endif; ?>

            <div class="passport-catalog__content">
              <?php if ($pdf_url): ?>
                <a href="<?= esc_url($pdf_url); ?>" class="passport-catalog__button title-7 title-mobile-sm-5"
                  target="_blank" rel="noopener">

                  <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                  </svg> -->
                  <img src="<?= IMG . '/icon-download.svg' ?>" alt="Descargar Pasaporte" class="" width="24" height="24" />
                  Pasaporte de servicios
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
      <div class="passport-catalog__pagination">
        <button type="button" class="passport-catalog__nav-button js-passport-prev" data-page="1" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>

        <div class="passport-catalog__page-buttons">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <button type="button" class="passport-catalog__page-button js-passport-page <?= $i === 1 ? 'is-active' : ''; ?>"
              data-page="<?= esc_attr($i); ?>">
              <?= esc_html($i); ?>
            </button>
          <?php endfor; ?>
        </div>

        <button type="button" class="passport-catalog__nav-button js-passport-next" data-page="1" <?= $total_pages === 1 ? 'disabled' : ''; ?>>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    <?php endif; ?>

  </div>
</section>