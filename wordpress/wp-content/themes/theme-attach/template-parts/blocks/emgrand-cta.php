<?php

/**
 * Block: EMGRAND – CTA Final
 *
 * Campos del BLOQUE (ACF):
 *  - cta_title               (Text)
 *  - cta_description         (Textarea)
 *  - cta_button_text         (Text)
 *  - cta_bg_desktop          (Image)
 *  - cta_bg_mobile           (Image)
 *
 * Además, consumimos:
 *  - product_datasheet_url   (campo del producto actual)
 */

if (! function_exists('get_field')) {
    return;
}

$post_id = get_the_ID();
if (! $post_id) {
    return;
}

// Contenidos del bloque
$cta_title       = get_field('cta_title') ?: 'El momento es ahora';
$cta_description = get_field('cta_description') ?: '';
$cta_btn_text    = get_field('cta_button_text') ?: 'Cotizar';

// Fondos
$bg_desktop_field = get_field('cta_bg_desktop');
$bg_mobile_field  = get_field('cta_bg_mobile');

$bg_desktop_url = is_array($bg_desktop_field)
    ? ($bg_desktop_field['url'] ?? '')
    : ($bg_desktop_field ?: '');

$bg_mobile_url  = is_array($bg_mobile_field)
    ? ($bg_mobile_field['url'] ?? '')
    : ($bg_mobile_field ?: '');

// URL de cotizar desde el producto
$product_datasheet_url = get_field('product_datasheet_url', $post_id);
$cta_btn_url = $product_datasheet_url ?: '#';

// CSS vars inline
$style_attr = '';

if ($bg_desktop_url) {
    $style_attr .= "--emg-cta-bg-desktop:url('" . esc_url($bg_desktop_url) . "');";
}
if ($bg_mobile_url) {
    $style_attr .= "--emg-cta-bg-mobile:url('" . esc_url($bg_mobile_url) . "');";
}
?>
<section class="emg-cta" style="<?php echo esc_attr($style_attr); ?>">
    <div class="emg-cta__overlay"></div>

    <div class="emg-cta__inner">
        <div class="emg-cta__content">
            <?php if ($cta_title) : ?>
                <h2 class="emg-cta__title">
                    <?php echo esc_html($cta_title); ?>
                </h2>
            <?php endif; ?>

            <?php if ($cta_description) : ?>
                <p class="emg-cta__description">
                    <?php echo esc_html($cta_description); ?>
                </p>
            <?php endif; ?>

            <?php if ($cta_btn_url && $cta_btn_text) : ?>
                <a
                    href="<?php echo esc_url($cta_btn_url); ?>"
                    class="emg-cta__button">
                    <?php echo esc_html($cta_btn_text); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>