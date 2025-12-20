<?php

/**
 * Block: EMGRAND – Momentos
 *
 * Campos del BLOQUE:
 *  - block_moments_id      (Text)
 *  - block_moments_title         (Text)
 *  - block_moments_description   (Textarea)
 *  - block_moments_bg_desktop    (Image)
 *  - block_moments_bg_mobile     (Image)
 *
 * Campos del PRODUCTO (CPT "producto"):
 *  - product_gallery (Gallery)
 */

if (! function_exists('get_field')) {
    return;
}

$post_id = get_the_ID();
if (! $post_id) {
    return;
}

// Título + descripción del bloque
$block_id = get_field('block_moments_id');
$block_title = get_field('block_moments_title');
$block_desc  = get_field('block_moments_description');

if (! $block_title) {
    $block_title = 'Hecho para cada momento';
}

// Fondos desktop / mobile
$bg_desktop_field = get_field('block_moments_bg_desktop');
$bg_mobile_field  = get_field('block_moments_bg_mobile');

$bg_desktop_url = is_array($bg_desktop_field)
    ? ($bg_desktop_field['url'] ?? '')
    : ($bg_desktop_field ?: '');

$bg_mobile_url  = is_array($bg_mobile_field)
    ? ($bg_mobile_field['url'] ?? '')
    : ($bg_mobile_field ?: '');

// Style inline con CSS vars para los fondos
$style_attr = '';
if ($bg_desktop_url) {
    $style_attr .= "--emg-moments-bg-desktop:url('" . esc_url($bg_desktop_url) . "');";
}
if ($bg_mobile_url) {
    $style_attr .= "--emg-moments-bg-mobile:url('" . esc_url($bg_mobile_url) . "');";
}

// Galería de producto
$gallery = get_field('product_gallery', $post_id);
$images  = (is_array($gallery)) ? array_values($gallery) : [];

if (empty($images)) {
    // Si no hay imágenes, no mostramos el bloque
    return;
}

// Para desktop usamos solo 5 imágenes como mosaico
$desktop_images = array_slice($images, 0, 5);
?>
<section class="emg-moments" id="<?php echo esc_attr($block_id); ?>" style="<?php echo esc_attr($style_attr); ?>">
    <div class="emg-moments__inner">

        <!-- Cabecera -->
        <header class="emg-moments__header">
            <h2 class="emg-moments__title">
                <?php echo esc_html($block_title); ?>
            </h2>

            <?php if ($block_desc) : ?>
                <p class="emg-moments__description">
                    <?php echo esc_html($block_desc); ?>
                </p>
            <?php endif; ?>
        </header>

        <!-- Galería desktop (mosaico 5 imágenes) -->
        <div class="emg-moments__grid">
            <?php foreach ($desktop_images as $index => $image) :
                $url   = isset($image['url'])   ? $image['url']   : '';
                $alt   = isset($image['alt'])   ? $image['alt']   : '';
                $title = isset($image['title']) ? $image['title'] : '';

                if (! $url) {
                    continue;
                }

                // Clase modificadora según la posición
                $mod_class = 'emg-moments__item--' . ($index + 1);
            ?>
                <figure class="emg-moments__item <?php echo esc_attr($mod_class); ?>">
                    <img
                        src="<?php echo esc_url($url); ?>"
                        alt="<?php echo esc_attr($alt ?: $title); ?>"
                        class="emg-moments__image"
                        loading="lazy">
                </figure>
            <?php endforeach; ?>
        </div>

        <!-- Galería mobile (slider Swiper) -->
        <div class="emg-moments__mobile">
            <div class="emg-moments__slider emg-moments__slider--mobile swiper emg-moments__swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($images as $image) : ?>
                        <?php
                        $url   = $image['url'] ?? '';
                        $alt   = $image['alt'] ?? '';
                        $title = $image['title'] ?? '';
                        if (! $url) continue;
                        ?>
                        <div class="swiper-slide emg-moments__slide">
                            <img
                                src="<?php echo esc_url($url); ?>"
                                alt="<?php echo esc_attr($alt ?: $title); ?>"
                                class="emg-moments__image emg-moments__image--mobile"
                                loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="swiper-pagination emg-moments__pagination"></div>
        </div>


    </div>
</section>