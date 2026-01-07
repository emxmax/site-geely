<?php
if (!defined('ABSPATH')) exit;

// Block fields
$block_tech_title = get_field('block_tech_title') ?: 'NUESTRO VIAJE ES AHORA';
$block_tech_items = get_field('block_tech_items') ?: [];
?>

<section class="about-tech">
    <div class="about-tech__header">
        <?php if ($block_tech_title): ?>
            <h2 class="about-tech__title"><?php echo esc_html($block_tech_title); ?></h2>
        <?php endif; ?>
    </div>

    <?php if (!empty($block_tech_items)): ?>
        <div class="about-tech__slider">
            <div class="about-tech__slider-wrapper">
                <?php foreach ($block_tech_items as $index => $item): 
                    $image = $item['image'] ?? null;
                    $title = $item['title'] ?? '';
                    $description = $item['description'] ?? '';
                    
                    // Obtener URL y alt de la imagen ACF
                    $image_url = '';
                    $image_alt = $title ?: 'Tecnología Geely';
                    
                    if ($image) {
                        if (is_array($image)) {
                            $image_url = $image['url'] ?? '';
                            $image_alt = $image['alt'] ?? $image_alt;
                        } elseif (is_numeric($image)) {
                            $image_url = wp_get_attachment_image_url($image, 'large') ?: '';
                            $image_alt = get_post_meta($image, '_wp_attachment_image_alt', true) ?: $image_alt;
                        } else {
                            $image_url = $image;
                        }
                    }
                ?>
                    <div class="about-tech__slide">
                        <div class="about-tech__slide-container">
                            <?php if ($image_url): ?>
                                <div class="about-tech__slide-image-wrapper">
                                    <img 
                                        src="<?php echo esc_url($image_url); ?>" 
                                        alt="<?php echo esc_attr($image_alt); ?>"
                                        class="about-tech__slide-image"
                                    >
                                </div>
                            <?php endif; ?>

                            <div class="about-tech__slide-content">
                                <?php if ($title): ?>
                                    <h3 class="about-tech__slide-title"><?php echo esc_html($title); ?></h3>
                                <?php endif; ?>

                                <?php if ($description): ?>
                                    <div class="about-tech__slide-description">
                                        <?php echo wp_kses_post($description); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($block_tech_items) > 1): ?>
                <div class="about-tech__navigation">
                    <button class="about-tech__nav-button about-tech__nav-button--prev" aria-label="Previous slide">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button class="about-tech__nav-button about-tech__nav-button--next" aria-label="Next slide">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
