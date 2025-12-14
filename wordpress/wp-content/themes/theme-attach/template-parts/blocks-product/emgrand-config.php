<?php

/**
 * Block: EMGRAND – Configurador
 *
 * Campos del BLOQUE:
 *  - block_config_id      (Text)
 *  - block_config_main_title      (Text)
 *  - block_config_main_bg_desktop (Image)
 *  - block_config_main_bg_mobile  (Image)
 *
 * Campos del PRODUCTO (CPT "producto"):
 *  - product_status            (Select: active/hidden)
 *  - product_quote_url         (URL)
 *  - product_datasheet_url     (URL/File)
 *  - product_models (Repeater)
 *      - model_name            (Text)
 *      - model_slug            (Text)
 *      - model_active          (True/False)
 *      - model_quote_url       (URL)
 *      - model_price_label     (Text)
 *      - model_price_usd       (Text/Number)
 *      - model_price_local     (Text/Number)
 *      - model_wheels_size     (Text)
 *      - wheels_size_diameter     (Text)
 *      - model_wheels_material (Text)
 *      - model_gear_speeds     (Text/Number)
 *      - model_transmission    (Text)
 *      - model_datasheet       (URL/File)
 *      - model_colors (Repeater)
 *          - color_name            (Text)
 *          - color_hex             (Text / Color picker)
 *          - color_image_desktop   (Image)
 *          - color_image_mobile    (Image)
 */

if (! function_exists('get_field')) {
    return;
}

$post_id = get_the_ID();
if (! $post_id) {
    return;
}

/**
 * PRODUCTO: estado y datos base
 */
$product_status = get_field('product_status', $post_id);
if ($product_status === 'hidden') {
    // No mostramos nada si el producto está oculto
    return;
}

$product_title = get_the_title($post_id);            // Nombre del producto (EMGRAND)
$product_slug  = get_post_field('post_name', $post_id);
$product_quote = get_field('product_quote_url', $post_id);
$product_sheet = get_field('product_datasheet_url', $post_id);

/**
 * BLOQUE: título + fondos
 */
$block_id = get_field('block_config_id');
$block_title = get_field('block_config_main_title');
if (! $block_title) {
    $block_title = $product_title;
}

$bg_desktop_field = get_field('block_config_main_bg_desktop');
$bg_mobile_field  = get_field('block_config_main_bg_mobile');

$bg_desktop_url = is_array($bg_desktop_field) ? ($bg_desktop_field['url'] ?? '') : ($bg_desktop_field ?: '');
$bg_mobile_url  = is_array($bg_mobile_field)  ? ($bg_mobile_field['url'] ?? '')  : ($bg_mobile_field ?: '');

$style_attr = '';
if ($bg_desktop_url) {
    $style_attr .= "--emg-config-bg-desktop:url('" . esc_url($bg_desktop_url) . "');";
}
if ($bg_mobile_url) {
    $style_attr .= "--emg-config-bg-mobile:url('" . esc_url($bg_mobile_url) . "');";
}

/**
 * Repeater de submodelos
 */
$models = get_field('product_models', $post_id);
if (empty($models) || ! is_array($models)) {
    return;
}

// Buscar el primer modelo activo
$first_model_index = null;
foreach ($models as $idx => $model) {
    if (! empty($model['model_active'])) {
        $first_model_index = $idx;
        break;
    }
}
if ($first_model_index === null) {
    $first_model_index = 0;
}

/**
 * Helper global para url de archivo (file o string)
 */
if (! function_exists('emg_get_file_url')) {
    function emg_get_file_url($file_or_url)
    {
        if (is_array($file_or_url)) {
            return $file_or_url['url'] ?? '';
        }
        return $file_or_url ?: '';
    }
}
?>
<section
    class="emg-config"
    id="<?php echo esc_attr($block_id); ?>"
    data-product="<?php echo esc_attr($product_slug); ?>"
    style="<?php echo esc_attr($style_attr); ?>">
    <div class="emg-config__inner">

        <!-- Top bar: título del bloque + tabs de versiones -->
        <div class="emg-config__top">
            <header class="emg-config__header">
                <h2 class="emg-config__title">
                    <?php echo esc_html($block_title); ?>
                </h2>
            </header>

            <div class="emg-config__versions emg-config__versions--float">
                <?php
                foreach ($models as $index => $model) :

                    // Saltar modelos inactivos
                    if (isset($model['model_active']) && ! $model['model_active']) {
                        continue;
                    }

                    $model_name = $model['model_name'] ?? '';
                    if (! $model_name) {
                        continue;
                    }

                    $model_slug_model = $model['model_slug'] ?: 'model-' . $index;
                    $is_active        = ($index === $first_model_index);
                ?>
                    <button
                        type="button"
                        class="emg-config__version<?php echo $is_active ? ' is-active' : ''; ?>"
                        data-model="<?php echo esc_attr($model_slug_model); ?>">
                        <?php echo esc_html($model_name); ?>
                    </button>
                <?php endforeach; ?>
            </div><!-- /.emg-config__versions -->
        </div><!-- /.emg-config__top -->

        <!-- Paneles / contenido por modelo -->
        <div class="emg-config__panels">
            <?php foreach ($models as $index => $model) :

                // Saltar modelos inactivos
                if (isset($model['model_active']) && ! $model['model_active']) {
                    continue;
                }

                $model_slug_model = $model['model_slug'] ?: 'model-' . $index;
                $is_active        = ($index === $first_model_index);

                // Datos del modelo
                $model_title      = $model['model_title'] ?? '';
                $display_title    = $model_title ?: $block_title;

                $price_label      = $model['model_price_label'] ?: 'Precio desde';
                $price_usd        = $model['model_price_usd'] ?? '';
                $price_local      = $model['model_price_local'] ?? '';

                $wheels_size          = $model['model_wheels_size'] ?? '';
                $wheels_size_diameter = $model['model_wheels_size_diameter'] ?? '';
                $wheels_material      = $model['model_wheels_material'] ?? '';
                $gear_speeds          = $model['model_gear_speeds'] ?? '';
                $transmission         = $model['model_transmission'] ?? '';

                // Ficha técnica: modelo > producto
                $model_sheet_url  = emg_get_file_url($model['model_datasheet'] ?? '');
                $sheet_url        = $model_sheet_url ?: emg_get_file_url($product_sheet);

                // URL de cotizar: modelo > producto
                $model_quote      = $model['model_quote_url'] ?? '';
                $quote_url        = $model_quote ?: $product_quote;

                // Colores
                $colors = $model['model_colors'] ?? [];
            ?>
                <div class="emg-config__panel<?php echo $is_active ? ' is-active' : ''; ?>"
                    data-model="<?php echo esc_attr($model_slug_model); ?>">
                    <div class="emg-config__layout">

                        <!-- Visual: auto + selector de colores -->
                        <div class="emg-config__visual">
                            <div class="emg-config__image-wrapper">
                                <?php
                                $first_color_index = 0;

                                foreach ($colors as $c_idx => $color) :

                                    $color_name = $color['color_name'] ?? '';
                                    $color_hex  = $color['color_hex'] ?? '#cccccc';
                                    $color_id   = $model_slug_model . '-color-' . $c_idx;

                                    // Imagen estática actual (fallback si no hay 360)
                                    // Imagen estática actual (fallback si no hay 360)
                                    $img_field = $color['color_image_desktop'] ?? null;
                                    $img_url   = is_array($img_field)
                                        ? ($img_field['url'] ?? '')
                                        : ($img_field ?: '');

                                    /**
                                     * ========================
                                     *  DATOS 360 DESDE EL ZIP
                                     * ========================
                                     */
                                    // Puede venir como ID o como array (según ACF)
                                    $zip_field = $color['color_360_zip'] ?? null;

                                    if (is_array($zip_field)) {
                                        $zip_id = isset($zip_field['ID'])
                                            ? (int) $zip_field['ID']
                                            : (int) ($zip_field['id'] ?? 0);
                                    } else {
                                        $zip_id = (int) $zip_field;
                                    }

                                    $ci_folder  = '';
                                    $ci_pattern = '';
                                    $ci_amount  = 0;

                                    if ($zip_id) {
                                        $zip_path = get_attached_file($zip_id);

                                        if ($zip_path && file_exists($zip_path)) {
                                            $upload   = wp_upload_dir();
                                            $base_dir = trailingslashit($upload['basedir']);
                                            $base_url = trailingslashit($upload['baseurl']);

                                            $root_360_dir = $base_dir . 'emgrand-360/';

                                            $zip_filename      = pathinfo($zip_path, PATHINFO_FILENAME);
                                            $color_folder_slug = sanitize_title($zip_filename);

                                            $post_id    = get_the_ID(); // ya lo tienes arriba, pero no molesta
                                            $target_dir = trailingslashit($root_360_dir . $post_id . '-' . $color_folder_slug . '/');

                                            $files = glob($target_dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);

                                            if (!empty($files)) {
                                                natsort($files);
                                                $files     = array_values($files);
                                                $ci_amount = count($files);

                                                $first_name = basename($files[0]);

                                                if (preg_match('/^(.+?)(\d+)\.(jpe?g|png|webp)$/i', $first_name, $m)) {
                                                    $prefix = $m[1];
                                                    $ext    = $m[3];
                                                    $ci_pattern = $prefix . '{index}.' . $ext;
                                                } else {
                                                    $ext        = pathinfo($first_name, PATHINFO_EXTENSION);
                                                    $ci_pattern = 'frame-{index}.' . $ext;
                                                }

                                                $ci_folder = trailingslashit(
                                                    $base_url . 'emgrand-360/' . $post_id . '-' . $color_folder_slug . '/'
                                                );
                                            }
                                        }
                                    }

                                    $has_360 = ($ci_folder && $ci_pattern && $ci_amount > 0);


                                    $is_color_active = ($c_idx === $first_color_index);
                                    $active_class    = $is_color_active ? ' is-active' : '';
                                ?>

                                    <?php if ($has_360) : ?>
                                        <!-- Viewer 360° -->
                                        <div
                                            class="emg-config__image emg-config__image-360 cloudimage-360<?php echo $active_class; ?>"
                                            data-color="<?php echo esc_attr($color_id); ?>"

                                            data-folder="<?php echo esc_attr($ci_folder); ?>"
                                            data-filename-x="<?php echo esc_attr($ci_pattern); ?>"
                                            data-amount-x="<?php echo esc_attr($ci_amount); ?>"

                                            data-autoplay="0"
                                            data-full-screen="0"
                                            data-mouse-wheel-step="1"
                                            >
                                        </div>
                                    <?php elseif ($img_url) : ?>
                                        <!-- Fallback: imagen estática -->
                                        <img
                                            src="<?php echo esc_url($img_url); ?>"
                                            alt="<?php echo esc_attr($color_name ?: $display_title); ?>"
                                            class="emg-config__image<?php echo $active_class; ?>"
                                            data-color="<?php echo esc_attr($color_id); ?>">
                                    <?php endif; ?>


                                <?php endforeach; ?>
                            </div>

                            <?php if (! empty($colors)) : ?>
                                <div class="emg-config__colors">
                                    <span class="emg-config__colors-label">Colores</span>
                                    <div class="emg-config__colors-list">
                                        <?php
                                        $first_color_index = 0;
                                        foreach ($colors as $c_idx => $color) :
                                            $color_name      = $color['color_name'] ?? '';
                                            $color_hex       = $color['color_hex'] ?? '#cccccc';
                                            $color_id        = $model_slug_model . '-color-' . $c_idx;
                                            $is_color_active = ($c_idx === $first_color_index);
                                        ?>
                                            <button
                                                type="button"
                                                class="emg-config__color-dot<?php echo $is_color_active ? ' is-active' : ''; ?>"
                                                style="--emg-color: <?php echo esc_attr($color_hex); ?>;"
                                                data-color="<?php echo esc_attr($color_id); ?>"
                                                aria-label="<?php echo esc_attr($color_name ?: 'Color'); ?>"></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Icono 360 (decorativo, centrado bajo el auto) -->
                            <div class="emg-config__360">
                                <img
                                    src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/360-giro.png'); ?>"
                                    alt="360"
                                    class="emg-config__360-img">
                            </div>
                        </div><!-- /.emg-config__visual -->

                        <!-- Info: precios + especificaciones + CTAs -->
                        <div class="emg-config__info">
                            <div class="emg-config__bottom-grid">
                                <div class="emg-config__prices">
                                    <p class="emg-config__price-label">
                                        <?php echo esc_html($price_label); ?>
                                    </p>
                                    <p class="emg-config__price-line">
                                        <?php if ($price_usd) : ?>
                                            <span class="emg-config__price-usd">
                                                USD <?php echo esc_html($price_usd); ?>
                                                <span class="emg-config__price-or">o</span>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($price_local) : ?>
                                            <span class="emg-config__price-local">
                                                PEN <?php echo esc_html($price_local); ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="emg-config__specs">
                                    <?php if ($wheels_size || $wheels_material) : ?>
                                        <div class="emg-config__spec">
                                            <span class="emg-config__spec-label">Aros</span>
                                            <?php if ($wheels_size) : ?>
                                                <span class="emg-config__spec-main">
                                                    <?php echo esc_html($wheels_size); ?>
                                                    <span class="emg-config__price-di"><?php echo esc_html($wheels_size_diameter); ?></span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($wheels_material) : ?>
                                                <span class="emg-config__spec-sub">
                                                    <?php echo esc_html($wheels_material); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($gear_speeds || $transmission) : ?>
                                        <div class="emg-config__spec">
                                            <span class="emg-config__spec-label">Hasta</span>
                                            <?php if ($gear_speeds) : ?>
                                                <span class="emg-config__spec-main">
                                                    <?php echo esc_html($gear_speeds); ?>
                                                    <span class="emg-config__spec-ve">velocidades</span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($transmission) : ?>
                                                <span class="emg-config__spec-sub">
                                                    <?php echo esc_html($transmission); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div><!-- /.emg-config__bottom-grid -->

                        </div><!-- /.emg-config__info -->

                    </div><!-- /.emg-config__layout -->
                </div><!-- /.emg-config__panel -->
            <?php endforeach; ?>
        </div><!-- /.emg-config__panels -->

    </div><!-- /.emg-config__inner -->
</section>