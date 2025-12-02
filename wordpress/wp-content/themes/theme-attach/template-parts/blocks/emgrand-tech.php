<?php

/**
 * Block: EMGRAND – Tecnología Avanzada
 *
 * Campos:
 * - block_tech_id
 * - block_tech_subtitle
 * - block_tech_title
 * - block_tech_description
 * - block_tech_image
 * - block_tech_cards (Repeater)
 */

if (!function_exists('get_field')) return;

$block_id = get_field('block_tech_id');
$sub = get_field('block_tech_subtitle');
$title = get_field('block_tech_title');
$desc = get_field('block_tech_description');
$image = get_field('block_tech_image');
$cards = get_field('block_tech_cards');

if (!$title) return;
?>

<section class="emg-tech" id="<?php echo esc_attr($block_id); ?>">
    <div class="emg-tech__inner">

        <!-- Header -->
        <header class="emg-tech__header">
            <?php if ($sub): ?>
                <p class="emg-tech__subtitle"><?php echo esc_html($sub); ?></p>
            <?php endif; ?>

            <h2 class="emg-tech__title"><?php echo esc_html($title); ?></h2>

            <?php if ($desc): ?>
                <p class="emg-tech__description"><?php echo esc_html($desc); ?></p>
            <?php endif; ?>
        </header>

        <!-- Imagen principal -->
        <?php if (!empty($image['url'])): ?>
            <figure class="emg-tech__image-wrapper">
                <img
                    src="<?php echo esc_url($image['url']); ?>"
                    alt="<?php echo esc_attr($image['alt']); ?>"
                    class="emg-tech__image"
                    loading="lazy">
            </figure>
        <?php endif; ?>

        <!-- Cards -->
        <?php if (!empty($cards)): ?>
            <div class="emg-tech__cards">
                <?php foreach ($cards as $card):
                    $icon  = $card['card_icon'];
                    $ctitle = $card['card_title'];
                    $cdesc  = $card['card_description'];
                ?>
                    <div class="emg-tech__card">
                        <?php if (!empty($icon['url'])): ?>
                            <img src="<?php echo esc_url($icon['url']); ?>"
                                alt="<?php echo esc_attr($icon['alt']); ?>"
                                class="emg-tech__card-icon">
                        <?php endif; ?>

                        <?php if ($ctitle): ?>
                            <h3 class="emg-tech__card-title"><?php echo esc_html($ctitle); ?></h3>
                        <?php endif; ?>

                        <?php if ($cdesc): ?>
                            <p class="emg-tech__card-text"><?php echo esc_html($cdesc); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>