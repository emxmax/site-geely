<?php

/**
 * Block: EMGRAND – Seguridad
 *
 * Campos del BLOQUE (ACF):
 *  - block_safety_id            (Text)
 *  - block_safety_title            (Text)
 *  - block_safety_description      (Textarea)
 *  - block_safety_bg_desktop       (Image)
 *  - block_safety_bg_mobile        (Image)
 *  - block_safety_tabs             (Repeater)
 *      - safety_tab_slug           (Text)  ej: "interior", "exterior"
 *      - safety_tab_label          (Text)  ej: "Interior", "Exterior"
 *      - safety_tab_title          (Text)  título interno de la pestaña
 *      - safety_tab_description    (Textarea) descripción interna
 *      - safety_tab_items          (Repeater)
 *          - safety_item_image         (Image)
 *          - safety_item_title         (Text)     [opcional]
 *          - safety_item_description   (Textarea) [opcional]
 */

if (! function_exists('get_field')) return;

$post_id = get_the_ID();

// Texto principal del bloque
$block_id = get_field('block_safety_id');
$block_title = get_field('block_safety_title') ?: 'Seguridad';
$block_desc  = get_field('block_safety_description');

// Fondos administrables
$bg_desktop_field = get_field('block_safety_bg_desktop');
$bg_mobile_field  = get_field('block_safety_bg_mobile');

$bg_desktop_url = is_array($bg_desktop_field)
    ? ($bg_desktop_field['url'] ?? '')
    : ($bg_desktop_field ?: '');

$bg_mobile_url  = is_array($bg_mobile_field)
    ? ($bg_mobile_field['url'] ?? '')
    : ($bg_mobile_field ?: '');

// CSS variables inline
$style_attr = "";
if ($bg_desktop_url) {
    $style_attr .= "--emg-safety-bg-desktop:url('" . esc_url($bg_desktop_url) . "');";
}
if ($bg_mobile_url) {
    $style_attr .= "--emg-safety-bg-mobile:url('" . esc_url($bg_mobile_url) . "');";
}

// Tabs
$tabs = get_field('block_safety_tabs');
if (empty($tabs) || !is_array($tabs)) return;

// Filtrar tabs vacíos
$tabs = array_values(array_filter($tabs, function ($t) {
    return !empty($t['safety_tab_label']) && !empty($t['safety_tab_items']);
}));

if (empty($tabs)) return;
?>
<section class="emg-safety" id="<?php echo esc_attr($block_id); ?>" style="<?php echo esc_attr($style_attr); ?>">
    <div class="emg-safety__inner">

        <header class="emg-safety__header">
            <h2 class="emg-safety__title"><?php echo esc_html($block_title); ?></h2>
            <?php if ($block_desc): ?>
                <p class="emg-safety__description"><?php echo esc_html($block_desc); ?></p>
            <?php endif; ?>
        </header>

        <!-- Tabs -->
        <!-- Tabs (título + descripción dentro de la barra) -->
        <div class="emg-safety__tabs" role="tablist">
            <?php foreach ($tabs as $i => $tab):
                $slug       = $tab['safety_tab_slug'] ?: "tab-$i";
                $is_active  = $i === 0;
                $tab_label  = $tab['safety_tab_label'] ?: '';
                $tab_desc   = $tab['safety_tab_description'] ?? ''; // usamos la descripción del tab aquí
            ?>
                <button
                    class="emg-safety__tab<?php echo $is_active ? ' is-active' : ''; ?>"
                    role="tab"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                    data-safety-tab="<?php echo esc_attr($slug); ?>">
                    <span class="emg-safety__tab-label">
                        <?php echo esc_html($tab_label); ?>
                    </span>

                    <?php if ($tab_desc) : ?>
                        <span class="emg-safety__tab-desc">
                            <?php echo esc_html($tab_desc); ?>
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>


        <!-- Paneles -->
        <div class="emg-safety__panels">
            <?php foreach ($tabs as $i => $tab):
                $slug = $tab['safety_tab_slug'] ?: "tab-$i";
                $is_active = $i === 0;
                $tab_items = array_values($tab['safety_tab_items']);
            ?>
                <section
                    class="emg-safety__tab-panel<?php echo $is_active ? ' is-active' : ''; ?>"
                    data-safety-panel="<?php echo esc_attr($slug); ?>">

                    <!-- Slider -->
                    <div class="emg-safety__slider-wrapper">
                        <div class="swiper emg-safety__swiper">
                            <div class="swiper-wrapper">

                                <?php foreach ($tab_items as $item):
                                    $img = $item['safety_item_image'];
                                    $img_url = is_array($img) ? ($img['url'] ?? '') : '';
                                    $img_alt = is_array($img) ? ($img['alt'] ?? '') : '';
                                ?>
                                    <div class="swiper-slide emg-safety__slide">
                                        <article class="emg-safety__card">

                                            <?php if ($img_url): ?>
                                                <div class="emg-safety__media">
                                                    <img src="<?php echo esc_url($img_url); ?>"
                                                        alt="<?php echo esc_attr($img_alt); ?>"
                                                        class="emg-safety__image">
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($item['safety_item_title'] || $item['safety_item_description']): ?>
                                                <div class="emg-safety__card-body">

                                                    <?php if ($item['safety_item_title']): ?>
                                                        <h4 class="emg-safety__card-title">
                                                            <?php echo esc_html($item['safety_item_title']); ?>
                                                        </h4>
                                                    <?php endif; ?>

                                                    <?php if ($item['safety_item_description']): ?>
                                                        <p class="emg-safety__card-text">
                                                            <?php echo esc_html($item['safety_item_description']); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                </div>
                                            <?php endif; ?>
                                        </article>
                                    </div>
                                <?php endforeach; ?>

                            </div>
                        </div>

                        <div class="emg-safety__controls">
                            <button class="emg-safety__nav-btn emg-safety__nav-btn--prev is-disabled">
                                <img
                                    src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
                                    alt="icon-arrow"
                                    class="emg-config__icon-arrow-img">
                            </button>
                            <div class="swiper-pagination emg-safety__pagination"></div>
                            <button class="emg-safety__nav-btn emg-safety__nav-btn--next">
                                <img
                                    src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
                                    alt="icon-arrow"
                                    class="emg-config__icon-arrow-img">
                            </button>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>

    </div>
</section>