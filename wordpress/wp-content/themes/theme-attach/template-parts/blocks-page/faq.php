<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * FAQ BLOCK
 * CPT: faq
 * ACF field: faq_answer
 */

// Título del bloque (opcional desde ACF)
$title = get_field('faq_block_title') ?: 'PREGUNTAS FRECUENTES';

// ACF de tipo taxonomía
$selected_category = get_field('faq_items_category');

// Query FAQs
$query_args = [
    'post_type'      => 'faq',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
];

// Filtramos por categoria
if (!empty($selected_category)) {
    $query_args['tax_query'] = [
        [
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => is_array($selected_category) ? $selected_category : [$selected_category],
        ],
    ];
}

$q = new WP_Query($query_args);
?>

<?php if ($q->have_posts()) : ?>
<section class="faq-block">
    <div class="faq-block__inner">

        <h2 class="faq-block__title">
            <?php echo esc_html($title); ?>
        </h2>

        <div class="faq-block__list js-faq" role="list">

            <?php while ($q->have_posts()) : $q->the_post(); ?>
                <?php
                $post_id = get_the_ID();
                $uid     = 'faq-' . $post_id;

                // ==========================
                // RESPUESTA (ACF CORREGIDO)
                // ==========================
                $answer = get_field('faq_answer', $post_id);

                // Fallback si ACF no responde en render de bloque
                if ($answer === null || $answer === '') {
                    $answer = get_post_meta($post_id, 'faq_answer', true);
                }
                ?>

                <div class="faq-item" role="listitem">

                    <button
                        class="faq-item__question"
                        type="button"
                        aria-expanded="false"
                        aria-controls="<?php echo esc_attr($uid); ?>"
                    >
                        <span class="faq-item__question-text">
                            <?php the_title(); ?>
                        </span>

                        <span class="faq-item__icon" aria-hidden="true">
                            <span class="faq-item__icon-plus">
                                <img
                                src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/faq-plus.png'); ?>"
                                alt="Plus Icon">
                            </span>
                            <span class="faq-item__icon-minus">
                                <img
                                src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/faq-minus.png'); ?>"
                                alt="Minus Icon">
                            </span>
                        </span>
                    </button>

                    <?php if (!empty($answer)) : ?>
                        <div
                            class="faq-item__answer"
                            id="<?php echo esc_attr($uid); ?>"
                            hidden
                        >
                            <?php echo wp_kses_post(wpautop($answer)); ?>
                        </div>
                    <?php endif; ?>

                </div>

            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>

        </div>

    </div>
</section>
<?php endif; ?>
