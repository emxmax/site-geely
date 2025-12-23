<?php if (!defined('ABSPATH')) exit;
/**
 * Partial: Models Finder Card
 * Espera $c = [
 *  title, url, img, type, label, usd, local
 * ]
 */
$payload = [
    'id'    => $c['id'],
    'title' => $c['title'],
    'img'   => $c['img'],
    'usd'   => $c['usd'],
    'local' => $c['local'],

    // specs (del producto)
    'specs' => [
        'maximum_power' => get_field('spec_maximum_power', $c['id']) ?: '',
        'transmission'  => get_field('spec_transmission', $c['id']) ?: '',
        'security'      => get_field('spec_security', $c['id']) ?: '',
        'seating'       => get_field('spec_seating', $c['id']) ?: '',
        'push_button'   => get_field('spec_sush_button', $c['id']) ?: '',
        'type'          => get_field('spec_type', $c['id']) ?: '',
    ],

    // versiones (desde tu repeater ACF)
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
            <?php if (!empty($c['type'])) : ?>
                <?php if ($c['type'] === 'gasolina') : ?>
                    <img
                        src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-gasolina.png'); ?>"
                        alt="icon-search"
                        class="">
                <?php endif; ?>
                <span class="mf-card__type"><?php echo esc_html($c['type']); ?></span>
            <?php endif; ?>
        </div>

        <button
            type="button"
            class="mf-card__versions js-mf-open-versions"
            data-product-id="<?php echo esc_attr($c['id']); ?>"
            data-title="<?php echo esc_attr($c['title']); ?>"
            data-img="<?php echo esc_url($c['img']); ?>"
            data-usd="<?php echo esc_attr($c['usd']); ?>"
            data-local="<?php echo esc_attr($c['local']); ?>">
            <img
                src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-search.svg'); ?>"
                alt="icon-search"
                class="">
            Ver versiones
        </button>

    </div>

    <?php if (!empty($c['img'])) : ?>
        <div class="mf-card__media">
            <img class="mf-card__img" src="<?php echo esc_url($c['img']); ?>" alt="<?php echo esc_attr($c['title']); ?>" loading="lazy">
        </div>
    <?php endif; ?>

    <div class="mf-card__body">
        <div class="mf-card__label"><?php echo esc_html($c['label']); ?></div>
        <div class="mf-card__prices">
            <span class="mf-card__usd">USD <?php echo esc_html($c['usd']); ?></span>
            <span class="mf-card__dot">o</span>
            <span class="mf-card__local">PEN <?php echo esc_html($c['local']); ?></span>
        </div>
    </div>

    <footer class="mf-card__actions">
        <a class="mf-btn mf-btn--ghost" href="<?php echo esc_url($c['url']); ?>">Ver modelo</a>
        <a class="mf-btn mf-btn--solid" href="<?php echo esc_url($c['url']); ?>#cotizar">Cotizar</a>
    </footer>
</article>