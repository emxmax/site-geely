<?php

/**
 * Bloque: Emgrand Hero con carrusel + navegación
 */

// Campos generales del bloque
$title      = get_field('hero_title');
$subtitle   = get_field('hero_subtitle');

$cta_quote_text = get_field('hero_cta_quote_text');
$cta_quote_url  = get_field('hero_cta_quote_url');

$nav_items = get_field('hero_nav_items');

$cta_datasheet_text = get_field('hero_cta_datasheet_text');
$cta_datasheet_url  = get_field('hero_cta_datasheet_url');

if (is_singular('producto')) {
    // DEBUG: quitar luego
    // var_dump( get_the_ID(), $cta_datasheet_url );
    $product_title = get_the_title($product_id);
    $product_datasheet_url = get_field('product_datasheet_url', get_the_ID());
    if ($product_datasheet_url) {
        $cta_datasheet_url = $product_datasheet_url;
    }
    if ($product_title) {
        $title = $product_title;
    }
}


// Repeater de slides
$slides = get_field('hero_slides');

// Fallback: si no hay slides usamos hero_car_image
if (!$slides) {
    $fallback_img = get_field('hero_car_image');
    if ($fallback_img) {
        $slides = [
            [
                'slide_type'  => 'image',
                'slide_image' => $fallback_img,
                'slide_alt'   => '',
            ],
        ];
    }
}
?>

<?php if ($slides) : ?>
    <section class="emg-hero">
        <div class="swiper emg-hero__swiper">
            <div class="swiper-wrapper">

                <?php foreach ($slides as $slide) : ?>

                    <?php
                    // Tipo de slide: 'image' o 'video'
                    $type = $slide['slide_type'] ?? 'image';

                    // Campo que contiene la URL o array del archivo (imagen o video)
                    $media      = $slide['slide_image'] ?? null;
                    $media_url  = '';

                    if (is_array($media)) {
                        // Si el campo ACF devuelve "Array"
                        $media_url = $media['url'] ?? '';
                        $alt_text  = $slide['slide_alt'] ?? ($media['alt'] ?? '');
                    } else {
                        // Si el campo ACF devuelve "URL"
                        $media_url = $media ?: '';
                        $alt_text  = $slide['slide_alt'] ?? '';
                    }

                    // Para imágenes usamos background-image
                    $bg_style  = '';
                    $video_url = '';

                    if ($type === 'video') {
                        $video_url = $media_url; // el mp4 está en el mismo campo
                    } else {
                        if ($media_url) {
                            $bg_style = "background-image:url('" . esc_url($media_url) . "');";
                        }
                    }

                    // Clases auxiliares por tipo
                    $slide_class = $type === 'video'
                        ? 'emg-hero__slide emg-hero__slide--video'
                        : 'emg-hero__slide emg-hero__slide--image';
                    ?>

                    <div class="swiper-slide <?php echo esc_attr($slide_class); ?>"
                        style="<?php echo esc_attr($bg_style); ?>">

                        <?php if ($type === 'video' && $video_url) : ?>
                            <video class="emg-hero__bg-video"
                                src="<?php echo esc_url($video_url); ?>"
                                autoplay
                                muted
                                loop
                                playsinline>
                            </video>
                        <?php endif; ?>

                        <div class="emg-hero__overlay">

                            <!-- ZONA SUPERIOR -->
                            <div class="emg-hero__top">
                                <div class="emg-hero__top-inner">
                                    <?php if ($title) : ?>
                                        <h1 class="emg-hero__title">
                                            <?php echo esc_html($title); ?>
                                        </h1>
                                    <?php endif; ?>

                                    <?php if ($subtitle) : ?>
                                        <p class="emg-hero__subtitle">
                                            <?php echo esc_html($subtitle); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($cta_quote_text && $cta_quote_url) : ?>
                                    <a href="<?php echo esc_url($cta_quote_url); ?>"
                                        class="emg-hero__btn emg-hero__btn--light">
                                        <?php echo esc_html($cta_quote_text); ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- BARRA NEGRA INFERIOR -->
                            <div class="emg-hero__bottom">

                                <div class="emg-hero__brand">
                                    <?php echo esc_html($title); ?>
                                </div>

                                <?php if ($nav_items) : ?>
                                    <nav class="emg-hero__nav">
                                        <?php foreach ($nav_items as $item) :
                                            $label = $item['item_title'] ?? '';
                                            $url   = $item['item_url'] ?? '#';
                                            if (!$label) {
                                                continue;
                                            }
                                        ?>
                                            <a href="<?php echo esc_url($url); ?>"
                                                class="emg-hero__nav-link">
                                                <?php echo esc_html($label); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </nav>
                                <?php endif; ?>

                                <?php if ($cta_datasheet_text && $cta_datasheet_url) : ?>
                                    <a target="_blank" href="<?php echo esc_url($cta_datasheet_url); ?>"
                                        class="emg-hero__btn emg-hero__btn--outline">
                                        <?php echo esc_html($cta_datasheet_text); ?>
                                    </a>
                                <?php endif; ?>

                            </div><!-- /.emg-hero__bottom -->
                        </div><!-- /.emg-hero__overlay -->

                    </div><!-- /.swiper-slide -->

                <?php endforeach; ?>

            </div><!-- /.swiper-wrapper -->
        </div><!-- /.emg-hero__swiper -->
    </section>
<?php endif; ?>