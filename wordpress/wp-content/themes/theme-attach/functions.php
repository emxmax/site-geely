<?php

/**
 * Funciones del tema Theme Attach
 */

if (! defined('ABSPATH')) {
    exit; // Seguridad básica
}

/**
 * Encolar estilos y scripts generales del tema
 */
function theme_attach_assets()
{
    // Estilos del tema (usa style.css)
    wp_enqueue_style(
        'theme-attach-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );

    // Swiper CSS (para sliders globales)
    wp_enqueue_style(
        'swiper',
        'https://unpkg.com/swiper/swiper-bundle.min.css',
        [],
        '11.0.0'
    );

    // Swiper JS (para sliders globales)
    wp_enqueue_script(
        'swiper',
        'https://unpkg.com/swiper/swiper-bundle.min.js',
        [],
        '11.0.0',
        true
    );

    // JS propio del tema
    wp_enqueue_script(
        'theme-attach-main',
        get_stylesheet_directory_uri() . '/assets/js/main.js',
        ['swiper'],
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'theme_attach_assets');

/**
 * Registrar bloques de ACF
 */
function theme_attach_register_acf_blocks()
{
    if (! function_exists('acf_register_block_type')) {
        return;
    }

    // Bloque HERO Emgrand
    acf_register_block_type([
        'name'            => 'emgrand-hero',
        'title'           => __('Producto Bloque - Hero', 'theme-attach'),
        'description'     => __('Hero principal del producto', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-hero.php',
        'category'        => 'layout',
        'icon'            => 'car',
        'keywords'        => ['emgrand', 'hero', 'geely'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Bloque EMGRAND – Configurador
    acf_register_block_type([
        'name'            => 'emgrand-config',
        'title'           => __('Producto Bloque - Configurador', 'theme-attach'),
        'description'     => __('Configurador de versiones, colores y precios del modelo Producto', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-config.php',
        'category'        => 'layout',
        'icon'            => 'admin-generic',
        'keywords'        => ['emgrand', 'config', 'versiones', 'colores'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // bloque: Momentos Emgrand
    acf_register_block_type([
        'name'            => 'emgrand-moments',
        'title'           => __('Producto Bloque - Momentos', 'theme-attach'),
        'description'     => __('Sección "Hecho para cada momento" con galería de imágenes del producto', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-moments.php',
        'category'        => 'layout',
        'icon'            => 'images-alt2',
        'keywords'        => ['emgrand', 'momentos', 'galeria', 'geely'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // NUEVO BLOQUE: Diseño Emgrand (Exterior / Interior)
    acf_register_block_type([
        'name'            => 'emgrand-design',
        'title'           => __('Producto Bloque - Diseño', 'theme-attach'),
        'description'     => __('Sección de diseño con pestañas Exterior / Interior y slider de imágenes', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-design.php',
        'category'        => 'layout',
        'icon'            => 'format-gallery',
        'keywords'        => ['emgrand', 'diseno', 'design', 'exterior', 'interior'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Bloque EMGRAND – Tecnología Avanzada
    acf_register_block_type([
        'name'            => 'emgrand-tech',
        'title'           => __('Producto Bloque - Tecnología Avanzada', 'theme-attach'),
        'description'     => __('Bloque de tecnología con imagen y cards', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-tech.php',
        'category'        => 'layout',
        'icon'            => 'admin-generic',
        'keywords'        => ['emgrand', 'tech', 'tecnologia'],
        'supports'        => ['align' => false],
    ]);

    // Bloque: Experiencia Única
    acf_register_block_type([
        'name'            => 'emgrand-experience',
        'title'           => __('Producto Bloque - Experiencia', 'theme-attach'),
        'description'     => __('Sección "Una experiencia única" con cards de imagen + título y fondo admin.', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-experience.php',
        'category'        => 'layout',
        'icon'            => 'format-image',
        'keywords'        => ['emgrand', 'experience', 'experiencia', 'cards'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Bloque EMGRAND – Seguridad
    acf_register_block_type([
        'name'            => 'emgrand-safety',
        'title'           => __('Producto Bloque - Seguridad', 'theme-attach'),
        'description'     => __('Sección de seguridad con tabs y slider de tarjetas', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-safety.php',
        'category'        => 'layout',
        'icon'            => 'shield',
        'keywords'        => ['emgrand', 'seguridad', 'safety'],
        'supports'        => [
            'align' => false,
        ],
    ]);

    // Bloque EMGRAND – CTA final "El momento es ahora"
    acf_register_block_type([
        'name'            => 'emgrand-cta',
        'title'           => __('Producto Bloque - CTA Final', 'theme-attach'),
        'description'     => __('Bloque CTA con imagen de fondo, título, descripción y botón Cotizar', 'theme-attach'),
        'render_template' => 'template-parts/blocks/emgrand-cta.php',
        'category'        => 'layout',
        'icon'            => 'megaphone',
        'keywords'        => ['emgrand', 'cta', 'cotizar', 'geely'],
        'supports'        => [
            'align' => false,
        ],
    ]);
}
add_action('acf/init', 'theme_attach_register_acf_blocks');

/**
 * Assets específicos de bloques Emgrand
 */
function emg_hero_assets()
{
    // Swiper (fallback si no viene del otro handle, pero lo mantenemos como lo tienes)
    wp_enqueue_style(
        'swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css'
    );

    wp_enqueue_script(
        'swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        [],
        null,
        true
    );

    // HERO
    wp_enqueue_style(
        'emg-hero-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-hero.css'
    );

    // CONFIGURADOR
    wp_enqueue_style(
        'emg-config-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-config.css',
        [],
        null
    );

    // MOMENTOS
    wp_enqueue_style(
        'emg-moments-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-moments.css',
        [],
        null
    );

    // DISEÑO
    wp_enqueue_style(
        'emg-design-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-design.css',
        [],
        null
    );

    // TECNOLOGÍA
    wp_enqueue_style(
        'emg-tech-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-tech.css',
        [],
        null
    );

    // EXPERIENCIA ÚNICA
    wp_enqueue_style(
        'emg-experience-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-experience.css',
        [],
        null
    );

    // SEGURIDAD
    wp_enqueue_style(
        'emg-safety-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-safety.css',
        [],
        null
    );

    // CTA FINAL
    wp_enqueue_style(
        'emg-cta-css',
        get_stylesheet_directory_uri() . '/template-parts/blocks/emgrand-cta.css',
        [],
        null
    );

    // Librería 360 Cloudimage
    wp_enqueue_script(
        'ci-360',
        'https://scaleflex.cloudimg.io/v7/plugins/js-cloudimage-360-view/latest/js-cloudimage-360-view.min.js',
        [],
        null,
        true
    );

    // JS HERO
    wp_enqueue_script(
        'emg-hero-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-hero.js',
        ['swiper-js'],
        null,
        true
    );

    // JS CONFIGURADOR
    wp_enqueue_script(
        'emg-config-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-config.js',
        ['ci-360'],
        null,
        true
    );

    // JS MOMENTOS
    wp_enqueue_script(
        'emg-moments-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-moments.js',
        ['swiper-js'],
        null,
        true
    );

    // JS DISEÑO
    wp_enqueue_script(
        'emg-design-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-design.js',
        ['swiper-js'],
        null,
        true
    );

    // JS SEGURIDAD
    wp_enqueue_script(
        'emg-safety-js',
        get_stylesheet_directory_uri() . '/assets/js/emg-safety.js',
        ['swiper-js'],
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'emg_hero_assets');

function attach_enqueue_fonts()
{
    wp_enqueue_style(
        'attach-fonts',
        get_template_directory_uri() . '/assets/css/fonts.css',
        array(),
        filemtime(get_template_directory() . '/assets/css/fonts.css')
    );
}
add_action('wp_enqueue_scripts', 'attach_enqueue_fonts');

/**
 * Cuando se guarda el campo "color_360_zip" (subcampo de un color),
 * descomprime el ZIP y rellena:
 *  - color_360_folder
 *  - color_360_pattern
 *  - color_360_amount
 */
add_filter('acf/update_value/name=color_360_zip', 'emg_handle_color_360_zip', 10, 3);

function emg_handle_color_360_zip( $value, $post_id, $field ) {
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
    $root_360_dir = $base_dir . 'emgrand-360/';
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

    // Si el ZIP venía con una carpeta interna (p.ej. "Ejemplo modelo 360")
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
    $files   = array_values( $files );
    $amount  = count( $files );
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
        $base_url . 'emgrand-360/' . $post_id . '-' . $color_folder_slug . '/'
    );

    /**
     * 🔑 IMPORTANTE:
     * $field['name'] trae algo como:
     *   producto_modelos_1_modelo_colores_2_color_360_zip
     * Cambiamos solo el sufijo por los otros subcampos
     */
    $folder_selector  = str_replace( 'color_360_zip', 'color_360_folder', $field['name'] );
    $pattern_selector = str_replace( 'color_360_zip', 'color_360_pattern', $field['name'] );
    $amount_selector  = str_replace( 'color_360_zip', 'color_360_amount', $field['name'] );

    // Ahora sí, se guardan en la MISMA fila del repeater
    acf_update_value( $folder_url, $post_id, $folder_selector );
    acf_update_value( $pattern,    $post_id, $pattern_selector );
    acf_update_value( $amount,     $post_id, $amount_selector );

    return $value;
}

/**
 * Helper: a partir del ZIP (color_360_zip) y el post_id
 * devuelve folder, pattern y amount si existe la carpeta 360.
 */
function emg_get_360_data_from_zip( $zip_attachment_id, $post_id ) {
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

    // Carpeta donde ya descomprimimos: emgrand-360/{post}-{slug-del-zip}/
    $zip_filename      = pathinfo( $zip_path, PATHINFO_FILENAME );
    $color_folder_slug = sanitize_title( $zip_filename );

    $target_dir = trailingslashit(
        $base_dir . 'emgrand-360/' . $post_id . '-' . $color_folder_slug . '/'
    );

    if ( ! file_exists( $target_dir ) ) {
        // Aún no existe carpeta = no hay 360
        return null;
    }

    // Buscamos imágenes
    $files = glob( $target_dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE );
    if ( empty( $files ) ) {
        return null;
    }

    natsort( $files );
    $files   = array_values( $files );
    $amount  = count( $files );
    $first_name = basename( $files[0] );

    // Intentamos deducir patrón: prefijo + número + extensión
    if ( preg_match( '/^(.+?)(\d+)\.(jpe?g|png|webp)$/i', $first_name, $m ) ) {
        $prefix  = $m[1]; // ej: emgrand-white-
        $ext     = $m[3]; // png, jpg...
        $pattern = $prefix . '{index}.' . $ext;
    } else {
        // fallback genérico
        $ext     = pathinfo( $first_name, PATHINFO_EXTENSION );
        $pattern = 'frame-{index}.' . $ext;
    }

    $folder_url = trailingslashit(
        $base_url . 'emgrand-360/' . $post_id . '-' . $color_folder_slug . '/'
    );

    return [
        'folder'  => $folder_url,
        'pattern' => $pattern,
        'amount'  => $amount,
    ];
}
