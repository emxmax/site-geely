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
                    
                    $image_url = theme_attach_get_post_image_url($image, 'medium');
                    $image_alt = theme_attach_get_post_image_alt($image, $title);
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
