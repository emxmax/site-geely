<?php
if (!defined('ABSPATH')) exit;

// Block fields
$block_social_eyebrow = get_field('block_social_eyebrow') ?: '';
$block_social_title = get_field('block_social_title') ?: 'RESPONSABILIDAD SOCIAL EMPRESARIAL';
$block_social_subtitle = get_field('block_social_subtitle') ?: 'GEELY HOPE';
$block_social_description = get_field('block_social_description') ?: '';
$block_social_content = get_field('block_social_content') ?: '';
$block_social_image = get_field('block_social_image');

// Obtener URL y alt de la imagen ACF
$image_url = '';
$image_alt = $block_social_subtitle ?: 'Geely Hope';

if ($block_social_image) {
    if (is_array($block_social_image)) {
        $image_url = $block_social_image['url'] ?? '';
        $image_alt = $block_social_image['alt'] ?? $image_alt;
    } elseif (is_numeric($block_social_image)) {
        $image_url = wp_get_attachment_image_url($block_social_image, 'large') ?: '';
        $image_alt = get_post_meta($block_social_image, '_wp_attachment_image_alt', true) ?: $image_alt;
    } else {
        $image_url = $block_social_image;
    }
}
?>

<section class="about-social">
    <div class="about-social__overlay">
        <div class="about-social__container">
            <div class="about-social__header">
                <?php if ($block_social_eyebrow): ?>
                    <p class="about-social__eyebrow"><?php echo esc_html($block_social_eyebrow); ?></p>
                <?php endif; ?>

                <?php if ($block_social_title): ?>
                    <h2 class="about-social__title"><?php echo esc_html($block_social_title); ?></h2>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="about-social__content-wrapper">
        <div class="about-social__container">
            <div class="about-social__content">
                <div class="about-social__text-content">
                    <?php if ($block_social_subtitle): ?>
                        <h3 class="about-social__subtitle"><?php echo esc_html($block_social_subtitle); ?></h3>
                    <?php endif; ?>

                    <?php if ($block_social_description): ?>
                        <div class="about-social__description">
                            <?php echo wp_kses_post($block_social_description); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($block_social_content): ?>
                        <div class="about-social__text">
                            <?php echo wp_kses_post($block_social_content); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($image_url): ?>
                    <div class="about-social__image-wrapper">
                        <img 
                            src="<?php echo esc_url($image_url); ?>" 
                            alt="<?php echo esc_attr($image_alt); ?>"
                            class="about-social__image"
                        >
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
