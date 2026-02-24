<?php
if (!defined('ABSPATH')) exit;

/**
 * ============================================================
 *  ACF 360 ZIP HANDLER (Windows + Linux compatible)
 * ============================================================
 * Estructura ACF:
 * product_models (repeater)
 *  - model_colors (repeater)
 *     - color_360_zip (File)
 *     - color_360_folder (Text)
 *     - color_360_pattern (Text)
 *     - color_360_amount (Number)
 */

define('PRODUCT_MODELS_REPEATER', 'product_models');
define('MODEL_COLORS_REPEATER',  'model_colors');

define('PRODUCT_360_DIRNAME',    'product-360'); // uploads/product-360/{postid}-{slug}/
define('PRODUCT_360_DEBUG',      true);

add_action('acf/save_post', 'product_process_color_360_zips', 999);

function product_process_color_360_zips($post_id)
{
    static $running = false;
    if ($running) return;

    if (!$post_id || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;

    // Si deseas limitar al CPT:
    // if (get_post_type($post_id) !== 'producto') return;

    if (!have_rows(PRODUCT_MODELS_REPEATER, $post_id)) return;

    $running = true;

    while (have_rows(PRODUCT_MODELS_REPEATER, $post_id)) {
        the_row();

        if (!have_rows(MODEL_COLORS_REPEATER)) continue;

        while (have_rows(MODEL_COLORS_REPEATER)) {
            the_row();

            $zip_value = get_sub_field('color_360_zip');
            if (!$zip_value) continue;

            $zip_id = product_360_get_attachment_id($zip_value);
            if (!$zip_id) continue;

            $data = product_360_unpack_and_get($zip_id, $post_id);
            if (!$data) continue;

            // Guardar en la misma fila (repeater)
            update_sub_field('color_360_folder',  $data['folder']);
            update_sub_field('color_360_pattern', $data['pattern']);
            update_sub_field('color_360_amount',  (int) $data['amount']);
        }
    }

    $running = false;
}

/** -------------------------
 *  Helpers
 * ------------------------- */
function product_360_log($msg, $data = null)
{
    if (!PRODUCT_360_DEBUG) return;
    if ($data !== null) {
        error_log('[PRODUCT_360] ' . $msg . ' ' . wp_json_encode($data, JSON_UNESCAPED_UNICODE));
    } else {
        error_log('[PRODUCT_360] ' . $msg);
    }
}

function product_360_get_attachment_id($value)
{
    if (is_numeric($value)) return (int) $value;

    if (is_array($value)) {
        if (!empty($value['ID'])) return (int) $value['ID'];
        if (!empty($value['id'])) return (int) $value['id'];
        if (!empty($value['url'])) return (int) attachment_url_to_postid($value['url']);
    }

    if (is_string($value)) {
        return (int) attachment_url_to_postid($value);
    }

    return 0;
}

function product_360_unpack_and_get($zip_id, $post_id)
{
    // si ya existe y tiene imágenes, retorna
    $existing = product_360_get_data($zip_id, $post_id);
    if ($existing) return $existing;

    // si no existe, descomprime
    if (!product_360_unpack_zip($zip_id, $post_id)) return null;

    // luego lee data ya normalizada
    return product_360_get_data($zip_id, $post_id);
}

function product_360_paths($zip_id, $post_id)
{
    $zip_path = get_attached_file($zip_id);
    if (!$zip_path) return null;

    $upload   = wp_upload_dir();
    $base_dir = wp_normalize_path($upload['basedir']);
    $base_url = trailingslashit($upload['baseurl']);

    $slug = sanitize_title(pathinfo($zip_path, PATHINFO_FILENAME));

    $root_dir   = trailingslashit($base_dir . '/' . PRODUCT_360_DIRNAME);
    $target_dir = trailingslashit($root_dir . $post_id . '-' . $slug);

    $folder_url = trailingslashit($base_url . PRODUCT_360_DIRNAME . '/' . $post_id . '-' . $slug);

    return [
        'zip_path'   => $zip_path,
        'root_dir'   => $root_dir,
        'target_dir' => $target_dir,
        'folder_url' => $folder_url,
        'slug'       => $slug,
    ];
}

function product_360_unpack_zip($zip_id, $post_id)
{
    $p = product_360_paths($zip_id, $post_id);
    if (!$p) return false;

    if (!file_exists($p['zip_path'])) {
        product_360_log('ZIP no existe en disco', $p['zip_path']);
        return false;
    }

    if (!file_exists($p['root_dir'])) wp_mkdir_p($p['root_dir']);
    if (!file_exists($p['target_dir'])) wp_mkdir_p($p['target_dir']);

    // WP_Filesystem + unzip_file (mejor compatibilidad)
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    WP_Filesystem();

    $result = unzip_file($p['zip_path'], $p['target_dir']);
    if (is_wp_error($result)) {
        product_360_log('unzip_file error', $result->get_error_message());
        return false;
    }

    // Aplanar cualquier subcarpeta a root del target
    product_360_flatten_images($p['target_dir']);

    // Tomar todas las imágenes (ya planas)
    $files = glob(trailingslashit($p['target_dir']) . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    if (!$files) {
        product_360_log('No se encontraron imágenes luego de unzip', $p['target_dir']);
        return false;
    }

    // Orden natural y renombrado SIEMPRE a secuencia estable
    natsort($files);
    $files = array_values($files);

    $ext = strtolower(pathinfo($files[0], PATHINFO_EXTENSION));
    if (!$ext) $ext = 'png';

    $amount = count($files);

    for ($i = 0; $i < $amount; $i++) {
        $new_name = 'frame-' . ($i + 1) . '.' . $ext;
        $new_path = trailingslashit($p['target_dir']) . $new_name;

        if (wp_normalize_path($files[$i]) !== wp_normalize_path($new_path)) {
            // si ya existe por alguna razón, lo borramos y reemplazamos
            if (file_exists($new_path)) @unlink($new_path);
            @rename($files[$i], $new_path);
        }
    }

    product_360_log('unzip ok', [
        'target_dir' => $p['target_dir'],
        'amount' => $amount,
        'ext' => $ext,
    ]);

    return true;
}

function product_360_flatten_images($dir)
{
    $dir = wp_normalize_path(trailingslashit($dir));

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) continue;

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) continue;

        $src  = wp_normalize_path($file->getPathname());
        $dest = $dir . basename($src);

        if ($src === wp_normalize_path($dest)) continue;

        // si ya existe, lo movemos con nombre temporal (luego se normaliza a frame-N)
        if (file_exists($dest)) {
            $dest = $dir . uniqid('tmp_', true) . '.' . $ext;
        }

        @rename($src, $dest);
    }
}

function product_360_get_data($zip_id, $post_id)
{
    $p = product_360_paths($zip_id, $post_id);
    if (!$p) return null;

    if (!file_exists($p['target_dir'])) return null;

    $files = glob(trailingslashit($p['target_dir']) . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    if (!$files) return null;

    natsort($files);
    $files = array_values($files);

    $amount = count($files);
    $first  = basename($files[0]);
    $ext    = pathinfo($first, PATHINFO_EXTENSION) ?: 'png';

    // patrón estable que sí existe
    $pattern = 'frame-{index}.' . $ext;

    return [
        'folder'  => $p['folder_url'],
        'pattern' => $pattern,
        'amount'  => $amount,
    ];
}