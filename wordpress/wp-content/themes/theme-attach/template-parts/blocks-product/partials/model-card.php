<?php if (!defined('ABSPATH'))
    exit;
/**
 * Partial: Models Finder Card
 * Espera $c = [
 *  title, url, img, type, label, usd, local
 * ]
 */
$type_terms = get_the_terms($c['id'], 'product_type');
$type_term  = (!empty($type_terms) && !is_wp_error($type_terms)) ? $type_terms[0] : null;

// icono ACF del término (tu campo: tp_icon)
$tp_icon = null;
$tp_icon_url = '';

if ($type_term) {
    $tp_icon = get_field('tp_icon', 'product_type_' . $type_term->term_id);

    if (is_array($tp_icon) && !empty($tp_icon['url'])) {
        $tp_icon_url = $tp_icon['url'];
    } elseif (is_numeric($tp_icon)) {
        $tp_icon_url = wp_get_attachment_image_url((int)$tp_icon, 'full') ?: '';
    }
}

$payload = [
    'id'    => $c['id'],
    'title' => $c['title'],
    'img'   => $c['img'],
    'usd'   => $c['usd'],
    'local' => $c['local'],

    'specs' => [
        'maximum_power' => get_field('spec_maximum_power', $c['id']) ?: '',
        'transmission'  => get_field('spec_transmission', $c['id']) ?: '',
        'security'      => get_field('spec_security', $c['id']) ?: '',
        'seating'       => get_field('spec_seating', $c['id']) ?: '',
        'push_button'   => get_field('spec_sush_button', $c['id']) ?: '',

        'type' => [
            'name'     => $type_term ? $type_term->name : '',
            'slug'     => $type_term ? $type_term->slug : '',
            'term_id'  => $type_term ? (int) $type_term->term_id : 0,
            'icon_url' => $tp_icon_url,
        ],
    ],

    'versions' => array_values(array_filter(array_map(function ($m) {
        $name = $m['model_name'] ?? $m['model_version'] ?? '';
        return $name ? ['name' => $name] : null;
    }, (array) get_field('product_models', $c['id'])))),
];
?>
<article class="mf-card">
    <header class="mf-card__top">
        <h3 class="mf-card__title"><?php echo esc_html($c['title']); ?></h3>
    </header>

    <div class="mf-card__meta">
        <div class="mf-card__type">
            <?php if (!empty($c['type_term']) && is_object($c['type_term'])): ?>
                <?php
                $term = $c['type_term'];

                $icon = get_field('tp_icon', 'product_type_' . $term->term_id);
                $icon_url = '';
                if (is_array($icon) && !empty($icon['url'])) {
                    $icon_url = $icon['url'];
                } elseif (is_numeric($icon)) {
                    $icon_url = wp_get_attachment_image_url((int)$icon, 'full') ?: '';
                }
                ?>

                <?php if ($icon_url): ?>
                    <img src="<?php echo esc_url($icon_url); ?>"
                        alt="<?php echo esc_attr($term->name); ?>"
                        class="mf-card__typeIcon"
                        loading="lazy">
                <?php endif; ?>

                <span class="mf-card__typeText"><?php echo esc_html($term->name); ?></span>
            <?php endif; ?>
        </div>

        <?php $class = should_hide_versions_button($c['id']) ? 'button-hidden' : ''; ?>

        <button type="button" class="mf-card__versions js-mf-open-versions <?= esc_attr($class); ?>"
            data-product-id="<?php echo esc_attr($c['id']); ?>" data-title="<?= esc_attr($c['title']); ?>"
            data-img="<?= esc_url($c['img']); ?>" data-usd="<?= esc_attr($c['usd']); ?>"
            data-local="<?= esc_attr($c['local']); ?>">
            <img src="<?= esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-search.svg'); ?>" alt="icon-search"
                class="">
            Ver versiones
        </button>
        <?php if (!should_hide_versions_button($c['id'])): ?>
        <?php endif; ?>

    </div>

    <?php if (!empty($c['img'])): ?>
        <div class="mf-card__media">
            <img class="mf-card__img" src="<?php echo esc_url($c['img']); ?>" alt="<?php echo esc_attr($c['title']); ?>"
                loading="lazy">
        </div>
    <?php endif; ?>

    <div class="mf-card__body">
        <div class="mf-card__label">
            <?php echo esc_html(!empty($c['label']) ? $c['label'] : 'Precio desde'); ?>
        </div>

        <div class="mf-card__prices">
            <span class="mf-card__usd">USD <?php echo esc_html(number_format((float) $c['usd'], 0, '.', ',')); ?></span>
            <span class="mf-card__dot">o</span>
            <span class="mf-card__local">PEN
                <?php echo esc_html(number_format((float) $c['local'], 0, '.', ',')); ?></span>
        </div>
    </div>

    <footer class="mf-card__actions">
        <a class="mf-btn mf-btn--ghost" href="<?php echo esc_url($c['url']); ?>">Ver modelo</a>
        <a class="mf-btn mf-btn--solid" href="<?= esc_url(mg_quote_build_url($c['id'])); ?>">Cotizar</a>
    </footer>
</article>
