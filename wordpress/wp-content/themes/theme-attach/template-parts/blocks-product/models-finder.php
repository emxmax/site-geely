<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('get_field')) return;

// Campos del bloque
$title = get_field('finder_title') ?: 'ENCUENTRA TU GEELY IDEAL';
$desc  = get_field('finder_desc') ?: '';
$terms = get_field('finder_categories') ?: []; // tax terms

$post_type = 'producto';
$taxonomy  = 'category';

$block_id = !empty($block['anchor']) ? $block['anchor'] : ('models-finder-' . ($block['id'] ?? uniqid()));
$root_selector = '#' . $block_id;

// Config
$initialCount = 9; // mostrar 9 al inicio
$stepCount    = 6; // cargar +6 cada click (puedes poner 9 si quieres)

if (!function_exists('mf_parse_price')) {
  function mf_parse_price($v) {
    if ($v === null || $v === '') return null;
    $s = str_replace([',',' '], '', (string)$v);
    $s = preg_replace('/[^0-9.]/', '', $s);
    return $s === '' ? null : (float)$s;
  }
}

if (!function_exists('mf_get_image_url')) {
  function mf_get_image_url($img) {
    if (empty($img)) return '';
    if (is_numeric($img)) {
      return (string)(wp_get_attachment_image_url((int)$img, 'full') ?: '');
    }
    if (is_array($img)) return (string)($img['url'] ?? '');
    return (string)$img;
  }
}

if (!function_exists('mf_get_selected_model_image')) {
  // usa el primer color con color_image_in_card=true
  function mf_get_selected_model_image($model) {
    $colors = $model['model_colors'] ?? [];
    if (!is_array($colors)) return '';
    foreach ($colors as $c) {
      if (!empty($c['color_image_in_card'])) {
        // para finder usaremos desktop (se ve bien en cards)
        return mf_get_image_url($c['color_image_desktop'] ?? null) ?: mf_get_image_url($c['color_image_mobile'] ?? null);
      }
    }
    return '';
  }
}

if (!function_exists('mf_pick_model_for_card')) {
  function mf_pick_model_for_card($models) {
    if (empty($models) || !is_array($models)) return null;

    $candidates = [];
    foreach ($models as $m) {
      $img = mf_get_selected_model_image($m);
      if ($img) $candidates[] = $m;
    }
    if (empty($candidates)) return null;

    // menor precio (USD si existe, si no Local)
    $best = null; $bestPrice = null;
    foreach ($candidates as $m) {
      $usd = mf_parse_price($m['model_price_usd'] ?? null);
      $loc = mf_parse_price($m['model_price_local'] ?? null);
      $price = $usd !== null ? $usd : ($loc !== null ? $loc : PHP_FLOAT_MAX);

      if ($best === null || $price < $bestPrice) {
        $best = $m;
        $bestPrice = $price;
      }
    }
    return $best ?: $candidates[0];
  }
}

if (!function_exists('mf_get_cards_for_term')) {
  function mf_get_cards_for_term($term_id, $post_type, $taxonomy) {
    $args = [
      'post_type'      => $post_type,
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'orderby'        => 'menu_order',
      'order'          => 'ASC',
    ];

    // term_id 0 = “Todos”
    if ((int)$term_id !== 0) {
      $args['tax_query'] = [[
        'taxonomy' => $taxonomy,
        'field'    => 'term_id',
        'terms'    => (int)$term_id,
      ]];
    }

    $q = new WP_Query($args);
    $cards = [];

    if ($q->have_posts()) {
      while ($q->have_posts()) {
        $q->the_post();
        $post_id = get_the_ID();

        $models = get_field('product_models', $post_id);
        $model  = mf_pick_model_for_card($models);
        if (!$model) continue;

        $img = mf_get_selected_model_image($model);
        if (!$img) continue;

        $label = (string)($model['model_price_label'] ?? 'Precio desde');
        $usd   = (string)($model['model_price_usd'] ?? '');
        $local = (string)($model['model_price_local'] ?? '');

        // Tipo (Gasolina / Híbrido)
        $type = (string) get_field('spec_type', $post_id);

        $cards[] = [
          'id'    => $post_id,
          'title' => get_the_title(),
          'url'   => get_permalink(),
          'img'   => $img,
          'type'  => $type,
          'label' => $label,
          'usd'   => $usd,
          'local' => $local,
        ];
      }
      wp_reset_postdata();
    }

    return $cards;
  }
}

// Terms para tabs: “Todos” + seleccionados
$tabs = [];
$tabs[] = (object)[ 'term_id' => 0, 'name' => 'Todos', 'slug' => 'all' ];

foreach ($terms as $t) {
  $term_id  = is_object($t) ? $t->term_id : (int)$t;
  $term_obj = is_object($t) ? $t : get_term($term_id);
  if ($term_obj && !is_wp_error($term_obj)) $tabs[] = $term_obj;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="mf">
  <div class="mf__inner">

    <header class="mf__header">
      <h2 class="mf__title"><?php echo esc_html($title); ?></h2>
      <?php if ($desc) : ?><p class="mf__desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
    </header>

    <div class="mf__tabs" role="tablist" aria-label="Categorías">
      <?php foreach ($tabs as $i => $term_obj) :
        $slug = $term_obj->slug ?: ('term-' . $term_obj->term_id);
        $active = $i === 0;
      ?>
        <button
          type="button"
          class="mf__tab <?php echo $active ? 'is-active' : ''; ?>"
          data-mf-tab="<?php echo esc_attr($slug); ?>"
          aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
          <?php echo esc_html($term_obj->name); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="mf__panels">
      <?php foreach ($tabs as $i => $term_obj) :
        $slug = $term_obj->slug ?: ('term-' . $term_obj->term_id);
        $active = $i === 0;

        $cards = mf_get_cards_for_term((int)$term_obj->term_id, $post_type, $taxonomy);
        $total = count($cards);
        $hasMore = $total > $initialCount;
      ?>
        <div class="mf__panel <?php echo $active ? 'is-active' : ''; ?>"
          data-mf-panel="<?php echo esc_attr($slug); ?>">

          <?php if (empty($cards)) : ?>
            <p class="mf__empty">No hay modelos para esta categoría.</p>
          <?php else : ?>
            <div class="mf__grid js-mf-grid"
              data-initial="<?php echo esc_attr($initialCount); ?>"
              data-step="<?php echo esc_attr($stepCount); ?>">

              <?php foreach ($cards as $idx => $c) : ?>
                <div class="mf__item <?php echo ($idx >= $initialCount) ? 'is-hidden' : ''; ?>">
                  <?php
                    // partial
                    include locate_template('template-parts/blocks-product/partials/model-card.php');
                  ?>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if ($hasMore) : ?>
              <div class="mf__more">
                <button type="button" class="mf__moreBtn js-mf-more">
                  Cargar más
                </button>
              </div>
            <?php endif; ?>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    </div>

  </div>

  <script>
    window.__MF_BLOCKS__ = window.__MF_BLOCKS__ || [];
    window.__MF_BLOCKS__.push('<?php echo esc_js($root_selector); ?>');
  </script>
</section>

<?php get_template_part(
  'template-parts/blocks-product/partials/model-versions-modal'
); ?>
