<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Cuando se guarda el campo "color_360_zip" (subcampo de un color),
 * descomprime el ZIP y rellena:
 *  - color_360_folder
 *  - color_360_pattern
 *  - color_360_amount
 */
add_filter('acf/update_value/name=color_360_zip', 'product_handle_color_360_zip', 10, 3);

function product_handle_color_360_zip( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        return $value;
    }

    $attachment_id = (int) $value;
    $zip_path      = get_attached_file( $attachment_id );

    if ( ! $zip_path || ! file_exists( $zip_path ) ) {
        return $value;
    }

    // --- rutas base uploads ---
    $upload   = wp_upload_dir();
    $base_dir = trailingslashit( $upload['basedir'] );
    $base_url = trailingslashit( $upload['baseurl'] );

    // carpeta raíz para TODOS los 360
    $root_360_dir = $base_dir . 'product-360/';
    if ( ! file_exists( $root_360_dir ) ) {
        wp_mkdir_p( $root_360_dir );
    }

    // nombre de carpeta para este color (post_id + nombre del zip)
    $zip_filename      = pathinfo( $zip_path, PATHINFO_FILENAME );
    $color_folder_slug = sanitize_title( $zip_filename );

    $target_dir = trailingslashit( $root_360_dir . $post_id . '-' . $color_folder_slug . '/' );
    if ( ! file_exists( $target_dir ) ) {
        wp_mkdir_p( $target_dir );
    }

    // --- descomprimir ZIP ---
    if ( ! function_exists( 'unzip_file' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $result = unzip_file( $zip_path, $target_dir );
    if ( is_wp_error( $result ) ) {
        return $value;
    }

    // Si el ZIP venía con una carpeta interna
    $glob_direct = glob( $target_dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE );
    if ( empty( $glob_direct ) ) {
        $subdirs = glob( $target_dir . '*', GLOB_ONLYDIR );
        if ( ! empty( $subdirs ) ) {
            $inner_dir  = trailingslashit( $subdirs[0] );
            $inner_imgs = glob( $inner_dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE );
            if ( ! empty( $inner_imgs ) ) {
                foreach ( $inner_imgs as $img_path ) {
                    @rename( $img_path, $target_dir . basename( $img_path ) );
                }
            }
        }
    }

    // --- ahora sí, imágenes planas en $target_dir ---
    $files = glob( $target_dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE );
    if ( empty( $files ) ) {
        return $value;
    }

    natsort( $files );
    $files      = array_values( $files );
    $amount     = count( $files );
    $first_name = basename( $files[0] );

    // patrón tipo: prefijo + número + ext
    if ( preg_match( '/^(.+?)(\d+)\.(jpe?g|png|webp)$/i', $first_name, $m ) ) {
        $prefix     = $m[1];
        $first_num  = $m[2];
        $ext        = $m[3];
        $pad_length = strlen( $first_num );

        for ( $i = 0; $i < $amount; $i++ ) {
            $new_index = str_pad( $i + 1, $pad_length, '0', STR_PAD_LEFT );
            $new_name  = $prefix . $new_index . '.' . $ext;
            $new_path  = $target_dir . $new_name;

            if ( basename( $files[$i] ) !== $new_name ) {
                @rename( $files[$i], $new_path );
                $files[$i] = $new_path;
            }
        }

        $pattern = $prefix . '{index}.' . $ext;
    } else {
        $ext     = pathinfo( $first_name, PATHINFO_EXTENSION );
        $pattern = 'frame-{index}.' . $ext;

        for ( $i = 0; $i < $amount; $i++ ) {
            $new_name = 'frame-' . ( $i + 1 ) . '.' . $ext;
            $new_path = $target_dir . $new_name;
            if ( basename( $files[$i] ) !== $new_name ) {
                @rename( $files[$i], $new_path );
            }
        }
    }

    // URL pública de la carpeta
    $folder_url = trailingslashit(
        $base_url . 'product-360/' . $post_id . '-' . $color_folder_slug . '/'
    );

    /**
     * IMPORTANTE:
     * $field['name'] trae algo como:
     *   producto_modelos_1_modelo_colores_2_color_360_zip
     * Cambiamos solo el sufijo por los otros subcampos
     */
    $folder_selector  = str_replace( 'color_360_zip', 'color_360_folder', $field['name'] );
    $pattern_selector = str_replace( 'color_360_zip', 'color_360_pattern', $field['name'] );
    $amount_selector  = str_replace( 'color_360_zip', 'color_360_amount', $field['name'] );

    // Guardar en la MISMA fila del repeater
    acf_update_value( $folder_url, $post_id, $folder_selector );
    acf_update_value( $pattern,    $post_id, $pattern_selector );
    acf_update_value( $amount,     $post_id, $amount_selector );

    return $value;
}

/**
 * Helper: a partir del ZIP (color_360_zip) y el post_id
 * devuelve folder, pattern y amount si existe la carpeta 360.
 */
function product_get_360_data_from_zip( $zip_attachment_id, $post_id ) {

    $zip_attachment_id = (int) $zip_attachment_id;
    if ( ! $zip_attachment_id ) {
        return null;
    }

    $zip_path = get_attached_file( $zip_attachment_id );
    if ( ! $zip_path || ! file_exists( $zip_path ) ) {
        return null;
    }

    // Rutas base de uploads
    $upload   = wp_upload_dir();
    $base_dir = trailingslashit( $upload['basedir'] );
    $base_url = trailingslashit( $upload['baseurl'] );

    // Carpeta donde ya descomprimimos: product-360/{post}-{slug-del-zip}/
    $zip_filename      = pathinfo( $zip_path, PATHINFO_FILENAME );
    $color_folder_slug = sanitize_title( $zip_filename );

    $target_dir = trailingslashit(
        $base_dir . 'product-360/' . $post_id . '-' . $color_folder_slug . '/'
    );

    if ( ! file_exists( $target_dir ) ) {
        return null;
    }

    // Buscamos imágenes
    $files = glob( $target_dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE );
    if ( empty( $files ) ) {
        return null;
    }

    natsort( $files );
    $files      = array_values( $files );
    $amount     = count( $files );
    $first_name = basename( $files[0] );

    // Intentamos deducir patrón: prefijo + número + extensión
    if ( preg_match( '/^(.+?)(\d+)\.(jpe?g|png|webp)$/i', $first_name, $m ) ) {
        $prefix  = $m[1];
        $ext     = $m[3];
        $pattern = $prefix . '{index}.' . $ext;
    } else {
        $ext     = pathinfo( $first_name, PATHINFO_EXTENSION );
        $pattern = 'frame-{index}.' . $ext;
    }

    $folder_url = trailingslashit(
        $base_url . 'product-360/' . $post_id . '-' . $color_folder_slug . '/'
    );

    return [
        'folder'  => $folder_url,
        'pattern' => $pattern,
        'amount'  => $amount,
    ];
}
