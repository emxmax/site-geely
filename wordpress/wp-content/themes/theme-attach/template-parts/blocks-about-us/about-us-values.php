<?php
if (!defined('ABSPATH')) exit;

// Block fields
$block_values_title = get_field('block_values_title') ?: 'NUESTROS VALORES';
$block_values_items = get_field('block_values_items') ?: [];
?>

<section class="about-values">
    <div class="about-values__container">
        <?php if ($block_values_title): ?>
            <h2 class="about-values__title"><?php echo esc_html($block_values_title); ?></h2>
        <?php endif; ?>

        <?php if (!empty($block_values_items)): ?>
            <div class="about-values__grid">
                <?php foreach ($block_values_items as $item): 
                    $image = $item['image'] ?? null;
                    $title = $item['title'] ?? '';
                    $description = $item['description'] ?? '';
                    
                    // Obtener URL y alt de la imagen ACF
                    $image_url = '';
                    $image_alt = $title ?: 'Valor Geely';
                    
                    if ($image) {
                        if (is_array($image)) {
                            $image_url = $image['url'] ?? '';
                            $image_alt = $image['alt'] ?? $image_alt;
                        } elseif (is_numeric($image)) {
                            $image_url = wp_get_attachment_image_url($image, 'medium') ?: '';
                            $image_alt = get_post_meta($image, '_wp_attachment_image_alt', true) ?: $image_alt;
                        } else {
                            $image_url = $image;
                        }
                    }
                ?>
                    <div class="about-values__item">
                        <?php if ($image_url): ?>
                            <div class="about-values__item-image-wrapper">
                                <img 
                                    src="<?php echo esc_url($image_url); ?>" 
                                    alt="<?php echo esc_attr($image_alt); ?>"
                                    class="about-values__item-image"
                                >
                            </div>
                        <?php endif; ?>

                        <?php if ($title): ?>
                            <h3 class="about-values__item-title"><?php echo esc_html($title); ?></h3>
                        <?php endif; ?>

                        <?php if ($description): ?>
                            <div class="about-values__item-description">
                                <?php echo wp_kses_post($description); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
