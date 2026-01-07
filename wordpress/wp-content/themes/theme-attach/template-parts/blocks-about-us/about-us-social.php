<?php
if (!defined('ABSPATH')) exit;

// Block fields
$block_social_eyebrow = get_field('block_social_eyebrow') ?: '';
$block_social_title = get_field('block_social_title') ?: 'RESPONSABILIDAD SOCIAL EMPRESARIAL';
$block_social_subtitle = get_field('block_social_subtitle') ?: 'GEELY HOPE';
$block_social_description = get_field('block_social_description') ?: '';
$block_social_content = get_field('block_social_content') ?: '';
$block_social_image = get_field('block_social_image');

$image_url = theme_attach_get_post_image_url($block_social_image, 'large');
$image_alt = theme_attach_get_post_image_alt($block_social_image, $block_social_subtitle);
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
