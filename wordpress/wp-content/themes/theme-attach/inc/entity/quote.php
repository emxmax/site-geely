<?php
/**
 * Columnas extra en el listado del CPT "cotizacion" (ACF fields).
 * Pegar en functions.php del child theme.
 */

/** 1) Definir columnas */
add_filter('manage_cotizacion_posts_columns', function ($columns) {

    $new = [];

    foreach ($columns as $key => $label) {
        $new[$key] = $label;

        if ($key === 'title') {
            $new['cot_product_title'] = 'Producto';
            $new['cot_model_name']    = 'Modelo';
            $new['cot_model_year']    = 'Año';
            $new['cot_color_name']    = 'Color';
            $new['cot_customer']      = 'Cliente';
            $new['cot_document']      = 'Documento';
        }
    }

    // Asegurar que "Fecha" quede al final
    if (isset($new['date'])) {
        $date = $new['date'];
        unset($new['date']);
        $new['date'] = $date;
    }

    return $new;
});

/** 2) Pintar valores de columnas */
add_action('manage_cotizacion_posts_custom_column', function ($column, $post_id) {

    $get = fn($key) => get_post_meta($post_id, $key, true);

    switch ($column) {
        case 'cot_product_title':
            echo esc_html($get('cot_product_title') ?: '—');
            break;

        case 'cot_model_name':
            echo esc_html($get('cot_model_name') ?: '—');
            break;

        case 'cot_model_year':
            echo esc_html($get('cot_model_year') ?: '—');
            break;

        case 'cot_color_name':
            $color = $get('cot_color_name');
            $hex   = $get('cot_color_hex');

            if ($color) {
                $badge = '';
                if ($hex && preg_match('/^#?[0-9a-fA-F]{6}$/', $hex)) {
                    $hex = ltrim($hex, '#');
                    $badge = '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;vertical-align:middle;margin-right:6px;border:1px solid #ccc;background:#'.$hex.'"></span>';
                }
                echo $badge . esc_html($color);
            } else {
                echo '—';
            }
            break;

        case 'cot_customer':
            $names     = trim((string)$get('cot_names'));
            $lastnames = trim((string)$get('cot_lastnames'));
            $full      = trim($names . ' ' . $lastnames);

            echo esc_html($full ?: '—');
            break;

        case 'cot_document':
            $type = $get('cot_document_type');
            $doc  = $get('cot_document');
            $out  = trim(($type ? $type . ': ' : '') . ($doc ?: ''));

            echo esc_html($out ?: '—');
            break;
    }
}, 10, 2);

/** 3) (Opcional) Columnas ordenables */
add_filter('manage_edit-cotizacion_sortable_columns', function ($columns) {
    $columns['cot_model_year'] = 'cot_model_year';
    $columns['cot_model_name'] = 'cot_model_name';
    return $columns;
});

/** 4) (Opcional) Soportar sorting por meta_key */
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'cotizacion') return;

    $orderby = $query->get('orderby');

    if (in_array($orderby, ['cot_model_year', 'cot_model_name'], true)) {
        $query->set('meta_key', $orderby);

        if ($orderby === 'cot_model_year') {
            $query->set('orderby', 'meta_value_num');
        } else {
            $query->set('orderby', 'meta_value');
        }
    }
});

/** 5) (Opcional) Ajuste de ancho visual */
add_action('admin_head', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'cotizacion') return;
    ?>
    <style>
      .column-cot_product_title { width: 14%; }
      .column-cot_model_name    { width: 14%; }
      .column-cot_model_year    { width: 6%;  }
      .column-cot_color_name    { width: 10%; }
      .column-cot_customer      { width: 18%; }
      .column-cot_document      { width: 14%; }
    </style>
    <?php
});
