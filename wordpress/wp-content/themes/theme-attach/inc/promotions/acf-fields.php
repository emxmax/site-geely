<?php
/**
 * Campos ACF para Promociones
 * 
 * Registra campos personalizados para el CPT promocion
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar campos ACF para promociones
 */
add_action('acf/init', 'theme_attach_register_promotion_fields');

function theme_attach_register_promotion_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Grupo: Configuración del Formulario (sidebar)
    acf_add_local_field_group([
        'key' => 'group_promocion_form_settings',
        'title' => 'Configuración del Formulario',
        'fields' => [
            [
                'key' => 'field_promocion_hide_form',
                'label' => 'Ocultar formulario',
                'name' => 'promocion_hide_form',
                'type' => 'true_false',
                'instructions' => 'Activa esta opción para ocultar el formulario de cotización en esta promoción.',
                'required' => 0,
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => 'Oculto',
                'ui_off_text' => 'Visible',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'promocion',
                ],
            ],
        ],
        'menu_order' => 5,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ]);
}
