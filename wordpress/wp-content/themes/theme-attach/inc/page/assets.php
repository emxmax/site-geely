<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Assets específicos de bloques PAGE
 */
function page_blocks_assets()
{

    /**
     * =========================
     * CSS de bloques PAGE
     * =========================
     * Convención:
     * template-parts/blocks-page/{block-name}.css
     */
    $css_blocks = [
        'experience-carousel',
        'image-commitments',
        'find-geely',
        'faq',
        'hero-carousel',
        'geely-future',
        // 'stores-locator',
    ];

    foreach ($css_blocks as $handle) {
        $rel = "/template-parts/blocks-page/{$handle}.css";
        $abs = get_stylesheet_directory() . $rel;

        wp_enqueue_style(
            "page-{$handle}-css",
            get_stylesheet_directory_uri() . $rel,
            [],
            file_exists($abs) ? filemtime($abs) : null
        );
    }

    /**
     * =========================
     * JS de bloques PAGE
     * =========================
     * Convención:
     * assets/js/{block-name}.js
     */
    $js_blocks = [
        'experience-carousel',
        'image-commitments',
        'faq',
        'hero-carousel',
    ];

    foreach ($js_blocks as $handle) {
        $rel = "/assets/js/{$handle}.js";
        $abs = get_stylesheet_directory() . $rel;

        wp_enqueue_script(
            "page-{$handle}-js",
            get_stylesheet_directory_uri() . $rel,
            ['swiper'],
            file_exists($abs) ? filemtime($abs) : null,
            true
        );
    }

    /**
     * Stores Locator (Google Maps + Swiper)
     */
    // $stores_locator_js = "/assets/js/stores-locator.js";
    // $stores_locator_js_abs = get_stylesheet_directory() . $stores_locator_js;

    // if (file_exists($stores_locator_js_abs)) {
    //     wp_enqueue_script(
    //         'page-stores-locator-js',
    //         get_stylesheet_directory_uri() . $stores_locator_js,
    //         ['swiper'],
    //         filemtime($stores_locator_js_abs),
    //         true
    //     );

    //     // Datos para JavaScript
    //     wp_localize_script('page-stores-locator-js', 'STORES_LOCATOR', [
    //         'ajax_url' => admin_url('admin-ajax.php'),
    //         'google_maps_api_key' => 'TU_API_KEY_AQUI', // CAMBIAR por tu API key real
    //     ]);
    // }
}
add_action('wp_enqueue_scripts', 'page_blocks_assets');

/**
 * =========================
 * Columnas personalizadas para CPT FAQ
 * =========================
 */

/**
 * Agregar columna de Categoría al admin de FAQ
 */
function faq_add_custom_columns($columns)
{
    $new_columns = [];
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        // Insertar columna de categoría después del título
        if ($key === 'title') {
            $new_columns['faq_category'] = __('Categoría', 'theme-attach');
        }
    }
    
    return $new_columns;
}
add_filter('manage_faq_posts_columns', 'faq_add_custom_columns');

/**
 * Llenar contenido de columna de Categoría
 */
function faq_fill_custom_columns($column, $post_id)
{
    if ($column === 'faq_category') {
        $terms = get_the_terms($post_id, 'category');
        
        if (!empty($terms) && !is_wp_error($terms)) {
            $term_links = [];
            foreach ($terms as $term) {
                $term_links[] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url(add_query_arg([
                        'post_type' => 'faq',
                        'category' => $term->slug
                    ], 'edit.php')),
                    esc_html($term->name)
                );
            }
            echo implode(', ', $term_links);
        } else {
            echo '<span aria-hidden="true">—</span>';
        }
    }
}
add_action('manage_faq_posts_custom_column', 'faq_fill_custom_columns', 10, 2);

/**
 * Hacer columna de Categoría ordenable
 */
function faq_sortable_columns($columns)
{
    $columns['faq_category'] = 'category';
    return $columns;
}
add_filter('manage_edit-faq_sortable_columns', 'faq_sortable_columns');

/**
 * Ordenamiento por taxonomía de Categoría
 */
function faq_columns_orderby($query)
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ($orderby === 'category') {
        $query->set('orderby', 'term_order');
        $query->set('order', $query->get('order') ?: 'ASC');
    }
}
add_action('pre_get_posts', 'faq_columns_orderby');