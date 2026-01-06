<?php
if (!defined('ABSPATH')) exit;

// Block fields
$block_hero_eyebrow = get_field('block_hero_eyebrow') ?: 'INNOVACION Y TECNOLOGIA';
$block_hero_title = get_field('block_hero_title') ?: 'GRUPO GEELY';
$block_hero_description = get_field('block_hero_description') ?: '';
$block_hero_image = get_field('block_hero_image');
$block_hero_ceo_name = get_field('block_hero_ceo_name') ?: '';
$block_hero_ceo_title = get_field('block_hero_ceo_title') ?: '';
$block_hero_content_title = get_field('block_hero_content_title') ?: '';
$block_hero_content_text = get_field('block_hero_content_text') ?: '';

$image_url = theme_attach_get_post_image_url($block_hero_image, 'full');
$image_alt = theme_attach_get_post_image_alt($block_hero_image, $block_hero_ceo_name);
?>

<section class="about-hero">
    <div class="about-hero__container">
        <div class="about-hero__header">
            <?php if ($block_hero_eyebrow): ?>
                <p class="about-hero__eyebrow"><?php echo esc_html($block_hero_eyebrow); ?></p>
            <?php endif; ?>

            <?php if ($block_hero_title): ?>
                <h1 class="about-hero__title"><?php echo esc_html($block_hero_title); ?></h1>
            <?php endif; ?>

            <?php if ($block_hero_description): ?>
                <div class="about-hero__description">
                    <?php echo wp_kses_post($block_hero_description); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="about-hero__content">
            <div class="about-hero__image-wrapper">
                <?php if ($image_url): ?>
                    <img 
                        src="<?php echo esc_url($image_url); ?>" 
                        alt="<?php echo esc_attr($image_alt); ?>"
                        class="about-hero__image"
                    >
                <?php endif; ?>

                <?php if ($block_hero_ceo_name || $block_hero_ceo_title): ?>
                    <div class="about-hero__ceo">
                        <?php if ($block_hero_ceo_name): ?>
                            <p class="about-hero__ceo-name"><?php echo esc_html($block_hero_ceo_name); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($block_hero_ceo_title): ?>
                            <p class="about-hero__ceo-title"><?php echo esc_html($block_hero_ceo_title); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="about-hero__text-content">
                <?php if ($block_hero_content_title): ?>
                    <h2 class="about-hero__content-title"><?php echo esc_html($block_hero_content_title); ?></h2>
                <?php endif; ?>

                <?php if ($block_hero_content_text): ?>
                    <div class="about-hero__content-text">
                        <?php echo wp_kses_post($block_hero_content_text); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
