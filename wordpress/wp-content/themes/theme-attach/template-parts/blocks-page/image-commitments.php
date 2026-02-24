<?php
if (! defined('ABSPATH')) exit;

// Background fields
$bg_desktop = get_field('sec_bg_image_desktop'); // Image
$bg_mobile  = get_field('sec_bg_image_mobile');  // Image (optional)

// Text fields
$subtitle   = get_field('sec_subtitle') ?: '';
$title      = get_field('sec_title') ?: '';
$desc       = get_field('sec_description') ?: '';
$btn_text   = get_field('sec_button_text') ?: '';
$btn_url    = get_field('sec_button_url') ?: '';
$btn_newtab = (bool) get_field('sec_button_new_tab');

// Right media (NOW: video file mp4)
$hero_video = get_field('sec_big_image'); // ACF File (mp4) -> keep same key

// Commitments
$commitments_title = get_field('commitments_title') ?: '';
$items = get_field('commitments_items'); // repeater
$items_count = (is_array($items)) ? count($items) : 0;
$show_nav = $items_count > 2;

$uid = 'ic-' . uniqid();

/**
 * Helpers (protegidos para evitar redeclare)
 */
if (! function_exists('ta_ic_get_img_data')) {
    function ta_ic_get_img_data($img)
    {
        $url = '';
        $alt = '';
        if (is_array($img)) {
            $url = $img['url'] ?? '';
            $alt = $img['alt'] ?? '';
        } elseif (is_numeric($img)) {
            $url = wp_get_attachment_image_url((int)$img, 'full');
            $alt = get_post_meta((int)$img, '_wp_attachment_image_alt', true);
        } elseif (is_string($img)) {
            $url = $img;
        }
        return [$url, $alt];
    }
}

if (! function_exists('ta_ic_get_file_url')) {
    /**
     * ACF File field can return:
     * - array: ['url'=>..., 'mime_type'=>..., ...]
     * - ID
     * - URL (string)
     */
    function ta_ic_get_file_url($file)
    {
        if (empty($file)) return '';
        if (is_array($file)) return $file['url'] ?? '';
        if (is_numeric($file)) return wp_get_attachment_url((int)$file) ?: '';
        if (is_string($file)) return $file;
        return '';
    }
}

[$bg_desktop_url] = ta_ic_get_img_data($bg_desktop);
[$bg_mobile_url]  = ta_ic_get_img_data($bg_mobile);

// Right media (video o imagen) - MISMA KEY
$hero_media = get_field('sec_big_image'); // puede ser File (mp4) o Image

// Detectar tipo
$media_is_video = false;
$video_url = '';
$hero_img_url = '';
$hero_img_alt = '';

// Si viene como array, podemos inferir por mime o url
if (is_array($hero_media)) {
    $mime = $hero_media['mime_type'] ?? '';
    $url  = $hero_media['url'] ?? '';

    if (strpos($mime, 'video/') === 0 || preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
        $media_is_video = true;
        $video_url = $url;
    } else {
        // tratarlo como imagen ACF image array
        [$hero_img_url, $hero_img_alt] = ta_ic_get_img_data($hero_media);
    }
} else {
    // Si no es array: puede ser ID o string
    $url = ta_ic_get_file_url($hero_media);

    // Heurística por extensión
    if ($url && preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
        $media_is_video = true;
        $video_url = $url;
    } else {
        // podría ser ID de imagen o url de imagen
        [$hero_img_url, $hero_img_alt] = ta_ic_get_img_data($hero_media);
        // si venía como string URL y ta_ic_get_img_data lo devolvió vacío en url, usa $url
        if (!$hero_img_url && $url) $hero_img_url = $url;
    }
}

$target = $btn_newtab ? '_blank' : '_self';
$rel    = $btn_newtab ? 'noopener noreferrer' : '';
?>

<section
    class="ic-block block-image-commitments"
    id="<?php echo esc_attr($uid); ?>"
    style="
    <?php if (!empty($bg_desktop_url)): ?>
      --ic-bg-desktop: url('<?php echo esc_url($bg_desktop_url); ?>');
    <?php endif; ?>
    <?php if (!empty($bg_mobile_url)): ?>
      --ic-bg-mobile: url('<?php echo esc_url($bg_mobile_url); ?>');
    <?php endif; ?>
  ">
    <div class="ic-block__inner">

        <div class="ic-block__grid">

            <div class="ic-block__left">
                <?php if ($subtitle): ?>
                    <p class="ic-block__subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>

                <?php if ($title): ?>
                    <h2 class="ic-block__title"><?php echo esc_html($title); ?></h2>
                <?php endif; ?>

                <?php if ($desc): ?>
                    <p class="ic-block__desc"><?php echo esc_html($desc); ?></p>
                <?php endif; ?>

                <?php if ($btn_url && $btn_text): ?>
                    <a class="ic-block__btn"
                        href="<?php echo esc_url($btn_url); ?>"
                        target="<?php echo esc_attr($target); ?>"
                        rel="<?php echo esc_attr($rel); ?>">
                        <?php echo esc_html($btn_text); ?>
                    </a>
                <?php endif; ?>

                <?php if ($commitments_title): ?>
                    <h3 class="ic-block__section-title"><?php echo esc_html($commitments_title); ?></h3>
                <?php endif; ?>

                <div class="ic-block__slider-wrap">
                    <div class="swiper ic-block__swiper">
                        <div class="swiper-wrapper">

                            <?php if (!empty($items) && is_array($items)): ?>
                                <?php foreach ($items as $row):
                                    $img = $row['item_image'] ?? null;
                                    $it  = $row['item_title'] ?? '';
                                    $id  = $row['item_description'] ?? '';

                                    [$url, $alt] = ta_ic_get_img_data($img);
                                ?>
                                    <div class="swiper-slide ic-block__slide">
                                        <div class="ic-block__card">

                                            <?php if ($url): ?>
                                                <div class="ic-block__card-media">
                                                    <img class="ic-block__card-img" src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($alt); ?>">
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($it): ?>
                                                <h4 class="ic-block__card-title"><?php echo esc_html($it); ?></h4>
                                            <?php endif; ?>

                                            <?php if ($id): ?>
                                                <p class="ic-block__card-desc"><?php echo esc_html($id); ?></p>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>
                    </div>


                    <?php if ($show_nav): ?>
                        <div class="ic-block__controls">
                            <button class="ic-block__nav ic-block__prev" type="button">
                                <img
                                    src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
                                    alt="Prev">
                            </button>

                            <button class="ic-block__nav ic-block__next" type="button">
                                <img
                                    src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-arrow.png'); ?>"
                                    alt="Next">
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_nav): ?>
                        <!-- Mobile paginations -->
                        <div class="swiper-pagination ic-block__pagination"></div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="ic-block__right">
                <div class="ic-block__hero">
                    <?php if ($media_is_video && $video_url): ?>
                        <video
                            class="ic-block__hero-video"
                            muted
                            autoplay
                            loop
                            playsinline
                            preload="auto">
                            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                        </video>

                    <?php elseif ($hero_img_url): ?>
                        <img
                            class="ic-block__hero-img"
                            src="<?php echo esc_url($hero_img_url); ?>"
                            alt="<?php echo esc_attr($hero_img_alt ?: ''); ?>"
                            loading="lazy"
                            decoding="async" />
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        window.__IC_BLOCKS__ = window.__IC_BLOCKS__ || [];
        window.__IC_BLOCKS__.push("#<?php echo esc_js($uid); ?>");
    </script>
</section>