<?php
/**
 * Block: EMGRAND – Experiencia
 *
 * Campos del BLOQUE (ACF):
 *  - block_experience_title        (Text)
 *  - block_experience_bg_desktop   (Image)
 *  - block_experience_bg_mobile    (Image)
 *  - block_experience_items        (Repeater)
 *      - experience_item_image     (Image)
 *      - experience_item_title     (Text)
 */

if (! function_exists('get_field')) {
    return;
}

$post_id = get_the_ID();
if (! $post_id) {
    return;
}

// Título del bloque
$block_title = get_field('block_experience_title');
if (! $block_title) {
    $block_title = 'Una experiencia única';
}

// Fondos desktop / mobile
$bg_desktop_field = get_field('block_experience_bg_desktop');
$bg_mobile_field  = get_field('block_experience_bg_mobile');

$bg_desktop_url = is_array($bg_desktop_field)
    ? ($bg_desktop_field['url'] ?? '')
    : ($bg_desktop_field ?: '');

$bg_mobile_url  = is_array($bg_mobile_field)
    ? ($bg_mobile_field['url'] ?? '')
    : ($bg_mobile_field ?: '');

// Style inline con CSS vars para los fondos
$style_attr = '';
if ($bg_desktop_url) {
    $style_attr .= "--emg-experience-bg-desktop:url('" . esc_url($bg_desktop_url) . "');";
}
if ($bg_mobile_url) {
    $style_attr .= "--emg-experience-bg-mobile:url('" . esc_url($bg_mobile_url) . "');";
}

// Cards
$items = get_field('block_experience_items');
$items = is_array($items) ? array_values($items) : [];

if (empty($items)) {
    return;
}
?>
<section class="emg-experience" style="<?php echo esc_attr($style_attr); ?>">
    <div class="emg-experience__inner">

        <header class="emg-experience__header">
            <h2 class="emg-experience__title">
                <?php echo esc_html($block_title); ?>
            </h2>
        </header>

        <div class="emg-experience__grid">
            <?php foreach ($items as $item) :
                $img_field = $item['experience_item_image'] ?? null;
                $img_url   = is_array($img_field) ? ($img_field['url'] ?? '') : ($img_field ?: '');
                $img_alt   = is_array($img_field) ? ($img_field['alt'] ?? '') : '';
                $img_name  = is_array($img_field) ? ($img_field['title'] ?? '') : '';
                $title     = $item['experience_item_title'] ?? '';

                if (! $img_url) {
                    continue;
                }
                ?>
                <article class="emg-experience__card">
                    <figure class="emg-experience__figure">
                        <img
                            src="<?php echo esc_url($img_url); ?>"
                            alt="<?php echo esc_attr($img_alt ?: $img_name); ?>"
                            class="emg-experience__image"
                            loading="lazy"
                        >
                        <?php if ($title) : ?>
                            <figcaption class="emg-experience__caption">
                                <span class="emg-experience__caption-text">
                                    <?php echo esc_html($title); ?>
                                </span>
                            </figcaption>
                        <?php endif; ?>
                    </figure>
                </article>
            <?php endforeach; ?>
        </div>

    </div>
</section>
