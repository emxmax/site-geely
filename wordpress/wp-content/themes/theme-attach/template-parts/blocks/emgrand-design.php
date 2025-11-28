<?php
/**
 * Block: EMGRAND – Diseño
 *
 * Campos del BLOQUE (ACF):
 *  - block_design_title        (Text)
 *  - block_design_description  (Textarea)
 *  - block_design_tabs         (Repeater)
 *      - design_tab_slug       (Text)  ej: "exterior", "interior"
 *      - design_tab_label      (Text)  ej: "Exterior", "Interior"
 *      - design_tab_title      (Text)  Título interno
 *      - design_tab_description(Textarea) Descripción interna
 *      - design_tab_items      (Repeater)
 *          - design_item_image       (Image)
 *          - design_item_title       (Text)     [opcional]
 *          - design_item_description (Textarea) [opcional]
 */

if ( ! function_exists( 'get_field' ) ) {
    return;
}

$post_id = get_the_ID();
if ( ! $post_id ) {
    return;
}

// Título y descripción del bloque
$block_title = get_field( 'block_design_title' );
$block_desc  = get_field( 'block_design_description' );

if ( ! $block_title ) {
    $block_title = 'Diseño';
}

// Tabs
$tabs = get_field( 'block_design_tabs' );
if ( empty( $tabs ) || ! is_array( $tabs ) ) {
    return;
}

// Normalizamos tabs (quitando vacíos)
$tabs = array_values(
    array_filter(
        $tabs,
        static function ( $tab ) {
            return ! empty( $tab['design_tab_label'] ) && ! empty( $tab['design_tab_items'] );
        }
    )
);

if ( empty( $tabs ) ) {
    return;
}
?>
<section class="emg-design">
    <div class="emg-design__inner">

        <!-- Header bloque -->
        <header class="emg-design__header">
            <h2 class="emg-design__title">
                <?php echo esc_html( $block_title ); ?>
            </h2>

            <?php if ( $block_desc ) : ?>
                <p class="emg-design__description">
                    <?php echo esc_html( $block_desc ); ?>
                </p>
            <?php endif; ?>
        </header>

        <!-- Tabs Exterior / Interior -->
        <div class="emg-design__tabs" role="tablist">
            <?php foreach ( $tabs as $index => $tab ) :
                $slug        = $tab['design_tab_slug'] ?: 'tab-' . $index;
                $label       = $tab['design_tab_label'];
                $is_active   = ( 0 === $index );
                $tab_id      = 'emg-design-tab-' . esc_attr( $slug );
                $panel_id    = 'emg-design-panel-' . esc_attr( $slug );
                ?>
                <button
                    type="button"
                    class="emg-design__tab<?php echo $is_active ? ' is-active' : ''; ?>"
                    role="tab"
                    id="<?php echo esc_attr( $tab_id ); ?>"
                    aria-controls="<?php echo esc_attr( $panel_id ); ?>"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                    data-design-tab="<?php echo esc_attr( $slug ); ?>"
                >
                    <?php echo esc_html( $label ); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Paneles por tab -->
        <div class="emg-design__panels">
            <?php foreach ( $tabs as $index => $tab ) :
                $slug           = $tab['design_tab_slug'] ?: 'tab-' . $index;
                $panel_id       = 'emg-design-panel-' . esc_attr( $slug );
                $tab_id         = 'emg-design-tab-' . esc_attr( $slug );
                $is_active      = ( 0 === $index );
                $tab_title      = $tab['design_tab_title'] ?? '';
                $tab_desc       = $tab['design_tab_description'] ?? '';
                $items          = $tab['design_tab_items'] ?? [];
                $items          = is_array( $items ) ? array_values( $items ) : [];
                if ( empty( $items ) ) {
                    continue;
                }
                ?>
                <section
                    class="emg-design__tab-panel<?php echo $is_active ? ' is-active' : ''; ?>"
                    role="tabpanel"
                    id="<?php echo esc_attr( $panel_id ); ?>"
                    aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
                    data-design-panel="<?php echo esc_attr( $slug ); ?>"
                >
                    <?php if ( $tab_title || $tab_desc ) : ?>
                        <header class="emg-design__subheader">
                            <?php if ( $tab_title ) : ?>
                                <h3 class="emg-design__sub-title">
                                    <?php echo esc_html( $tab_title ); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( $tab_desc ) : ?>
                                <p class="emg-design__sub-description">
                                    <?php echo esc_html( $tab_desc ); ?>
                                </p>
                            <?php endif; ?>
                        </header>
                    <?php endif; ?>

                    <div class="emg-design__slider-wrapper">
                        <div class="swiper emg-design__swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ( $items as $item ) :
                                    $img_field   = $item['design_item_image'] ?? null;
                                    $img_url     = is_array( $img_field ) ? ( $img_field['url'] ?? '' ) : ( $img_field ?: '' );
                                    $img_alt     = is_array( $img_field ) ? ( $img_field['alt'] ?? '' ) : '';
                                    $img_title   = is_array( $img_field ) ? ( $img_field['title'] ?? '' ) : '';
                                    $over_title  = $item['design_item_title'] ?? '';
                                    $over_text   = $item['design_item_description'] ?? '';

                                    if ( ! $img_url ) {
                                        continue;
                                    }
                                    ?>
                                    <div class="swiper-slide emg-design__slide">
                                        <figure class="emg-design__card">
                                            <img
                                                src="<?php echo esc_url( $img_url ); ?>"
                                                alt="<?php echo esc_attr( $img_alt ?: $img_title ); ?>"
                                                class="emg-design__image"
                                                loading="lazy"
                                            >
                                            <?php if ( $over_title || $over_text ) : ?>
                                                <figcaption class="emg-design__overlay">
                                                    <?php if ( $over_title ) : ?>
                                                        <h4 class="emg-design__overlay-title">
                                                            <?php echo esc_html( $over_title ); ?>
                                                        </h4>
                                                    <?php endif; ?>

                                                    <?php if ( $over_text ) : ?>
                                                        <p class="emg-design__overlay-text">
                                                            <?php echo esc_html( $over_text ); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </figcaption>
                                            <?php endif; ?>
                                        </figure>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Flechas de navegación -->
                        <div class="emg-design__nav">
                            <button
                                type="button"
                                class="emg-design__nav-btn emg-design__nav-btn--prev"
                                aria-label="<?php esc_attr_e( 'Anterior', 'theme-textdomain' ); ?>">
                                <span>&larr;</span>
                            </button>
                            <button
                                type="button"
                                class="emg-design__nav-btn emg-design__nav-btn--next"
                                aria-label="<?php esc_attr_e( 'Siguiente', 'theme-textdomain' ); ?>">
                                <span>&rarr;</span>
                            </button>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</section>
