<?php
if (!defined('ABSPATH')) exit;

// Block fields
$block_evolution_title = get_field('block_evolution_title') ?: 'NUESTRA EVOLUCIÓN';
$block_evolution_description = get_field('block_evolution_description') ?: '';
$block_evolution_timeline = get_field('block_evolution_timeline') ?: [];
$block_evolution_milestone_title = get_field('block_evolution_milestone_title') ?: '';
$block_evolution_milestone_description = get_field('block_evolution_milestone_description') ?: '';
$block_evolution_milestone_image = get_field('block_evolution_milestone_image');

$milestone_image_url = theme_attach_get_post_image_url($block_evolution_milestone_image, 'large');
$milestone_image_alt = theme_attach_get_post_image_alt($block_evolution_milestone_image, $block_evolution_milestone_title);
?>

<section class="about-evolution">
    <div class="about-evolution__container">
        <?php if ($block_evolution_title): ?>
            <h2 class="about-evolution__title"><?php echo esc_html($block_evolution_title); ?></h2>
        <?php endif; ?>

        <?php if ($block_evolution_description): ?>
            <div class="about-evolution__description">
                <?php echo wp_kses_post($block_evolution_description); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($block_evolution_timeline)): ?>
            <div class="about-evolution__timeline">
                <?php foreach ($block_evolution_timeline as $item): 
                    $year = $item['year'] ?? '';
                    $is_active = !empty($item['is_active']);
                ?>
                    <div class="about-evolution__year <?php echo $is_active ? 'is-active' : ''; ?>">
                        <span class="about-evolution__year-text"><?php echo esc_html($year); ?></span>
                        <span class="about-evolution__year-dot"></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($block_evolution_milestone_title || $milestone_image_url): ?>
            <div class="about-evolution__milestone">
                <div class="about-evolution__milestone-content">
                    <?php if ($block_evolution_milestone_title): ?>
                        <h3 class="about-evolution__milestone-title">
                            <?php echo esc_html($block_evolution_milestone_title); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ($block_evolution_milestone_description): ?>
                        <div class="about-evolution__milestone-description">
                            <?php echo wp_kses_post($block_evolution_milestone_description); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($milestone_image_url): ?>
                    <div class="about-evolution__milestone-image-wrapper">
                        <img 
                            src="<?php echo esc_url($milestone_image_url); ?>" 
                            alt="<?php echo esc_attr($milestone_image_alt); ?>"
                            class="about-evolution__milestone-image"
                        >
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
