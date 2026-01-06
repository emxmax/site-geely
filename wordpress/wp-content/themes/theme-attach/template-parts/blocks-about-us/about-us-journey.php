<?php
if (!defined('ABSPATH')) exit;

// Block fields
$block_journey_title = get_field('block_journey_title') ?: 'UN VIAJE POR DELANTE';
$block_journey_mission_image = get_field('block_journey_mission_image');
$block_journey_mission_title = get_field('block_journey_mission_title') ?: 'MISIÓN';
$block_journey_mission_text = get_field('block_journey_mission_text') ?: '';
$block_journey_vision_image = get_field('block_journey_vision_image');
$block_journey_vision_title = get_field('block_journey_vision_title') ?: 'VISIÓN';
$block_journey_vision_text = get_field('block_journey_vision_text') ?: '';

$mission_image_url = theme_attach_get_post_image_url($block_journey_mission_image, 'large');
$mission_image_alt = theme_attach_get_post_image_alt($block_journey_mission_image, $block_journey_mission_title);

$vision_image_url = theme_attach_get_post_image_url($block_journey_vision_image, 'large');
$vision_image_alt = theme_attach_get_post_image_alt($block_journey_vision_image, $block_journey_vision_title);
?>

<section class="about-journey">
    <div class="about-journey__container">
        <?php if ($block_journey_title): ?>
            <h2 class="about-journey__title"><?php echo esc_html($block_journey_title); ?></h2>
        <?php endif; ?>

        <div class="about-journey__grid">
            <!-- Mission -->
            <div class="about-journey__card">
                <?php if ($mission_image_url): ?>
                    <div class="about-journey__card-image-wrapper">
                        <img 
                            src="<?php echo esc_url($mission_image_url); ?>" 
                            alt="<?php echo esc_attr($mission_image_alt); ?>"
                            class="about-journey__card-image"
                        >
                        <div class="about-journey__card-overlay">
                            <h3 class="about-journey__card-title"><?php echo esc_html($block_journey_mission_title); ?></h3>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($block_journey_mission_text): ?>
                    <div class="about-journey__card-content">
                        <?php echo wp_kses_post($block_journey_mission_text); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Vision -->
            <div class="about-journey__card">
                <?php if ($vision_image_url): ?>
                    <div class="about-journey__card-image-wrapper">
                        <img 
                            src="<?php echo esc_url($vision_image_url); ?>" 
                            alt="<?php echo esc_attr($vision_image_alt); ?>"
                            class="about-journey__card-image"
                        >
                        <div class="about-journey__card-overlay">
                            <h3 class="about-journey__card-title"><?php echo esc_html($block_journey_vision_title); ?></h3>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($block_journey_vision_text): ?>
                    <div class="about-journey__card-content">
                        <?php echo wp_kses_post($block_journey_vision_text); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
