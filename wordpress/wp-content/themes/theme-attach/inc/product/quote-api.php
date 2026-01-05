<?php
// functions.php

if (!defined('ABSPATH')) exit;

define('MG_QUOTE_API_ENDPOINT', 'https://TU_API.com/quotes'); // CAMBIA

/**
 * Hook: cuando CF7 ya armó el submission y está por enviar mail.
 * Aquí guardamos el lead como post tipo "cotizacion" y mandamos la API.
 */
add_action('wpcf7_before_send_mail', function ($contact_form) {

    // Si quieres filtrar por un form específico:
    // $form_id = (int) $contact_form->id();
    // if ($form_id !== 123) return;

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();
    if (empty($data) || !is_array($data)) return;

    // Helpers
    $get = function ($key) use ($data) {
        $v = $data[$key] ?? '';
        if (is_array($v)) $v = reset($v);
        return is_string($v) ? trim($v) : $v;
    };

    // ====== 1) Producto / Versión (contexto) ======
    $cot_product_id     = (int) $get('product_id');
    $cot_product_title  = (string) $get('product_title');
    $cot_model_slug     = (string) $get('model_slug');
    $cot_model_name     = (string) $get('model_name');
    $cot_model_year     = (string) $get('model_year');
    $cot_model_price_usd   = (string) $get('model_price_usd');
    $cot_model_price_local = (string) $get('model_price_local');
    $cot_color_name     = (string) $get('color_name');
    $cot_color_hex      = (string) $get('color_hex');

    // ====== 2) Datos del cliente (lead) ======
    $cot_names          = (string) $get('cot_names');
    $cot_lastnames      = (string) $get('cot_lastnames');
    $cot_document_type  = (string) $get('cot_document_type');
    $cot_document       = (string) $get('cot_document');
    $cot_phone          = (string) $get('cot_phone');
    $cot_email          = (string) $get('cot_email');
    $cot_department     = (string) $get('cot_department');
    $cot_store          = (string) $get('cot_store');

    // acceptance suele venir como "1" o texto. Lo normalizamos:
    $cot_consent_raw = $get('cot_consent');
    $cot_consent = (!empty($cot_consent_raw) && $cot_consent_raw !== '0') ? 1 : 0;

    // Validación mínima
    if (!$cot_product_id || empty($cot_names) || empty($cot_lastnames) || empty($cot_phone) || empty($cot_email)) {
        // No cortamos el envío de CF7, pero no guardamos si está incompleto
        return;
    }

    // ====== Crear post cotizacion ======
    $title = sprintf(
        'Cotización - %s %s (%s)',
        $cot_names,
        $cot_lastnames,
        $cot_product_title ?: ('ID ' . $cot_product_id)
    );

    $post_id = wp_insert_post([
        'post_type'   => 'cotizacion',
        'post_status' => 'publish',
        'post_title'  => wp_strip_all_tags($title),
    ], true);

    if (is_wp_error($post_id) || !$post_id) {
        return;
    }

    // ====== Guardar ACF por NOMBRE (debe existir el field group en ACF) ======
    // 1) Producto / Versión
    update_field('cot_product_id', $cot_product_id, $post_id);
    update_field('cot_product_title', $cot_product_title, $post_id);
    update_field('cot_model_slug', $cot_model_slug, $post_id);
    update_field('cot_model_name', $cot_model_name, $post_id);
    update_field('cot_model_year', $cot_model_year, $post_id);
    update_field('cot_model_price_usd', $cot_model_price_usd, $post_id);
    update_field('cot_model_price_local', $cot_model_price_local, $post_id);
    update_field('cot_color_name', $cot_color_name, $post_id);
    update_field('cot_color_hex', $cot_color_hex, $post_id);

    // 2) Cliente
    update_field('cot_names', $cot_names, $post_id);
    update_field('cot_lastnames', $cot_lastnames, $post_id);
    update_field('cot_document_type', $cot_document_type, $post_id);
    update_field('cot_document', $cot_document, $post_id);
    update_field('cot_phone', $cot_phone, $post_id);
    update_field('cot_email', $cot_email, $post_id);
    update_field('cot_department', $cot_department, $post_id);
    update_field('cot_store', $cot_store, $post_id);
    update_field('cot_consent', $cot_consent, $post_id);

    // 4) Estado interno por defecto (si lo tienes)
    if (function_exists('get_field') && get_field('cot_status', $post_id) === null) {
        update_field('cot_status', 'nuevo', $post_id); // debe coincidir con choices del select
    }

    // ====== 3) Integración API (auditoría) ======
    $payload = [
        'product' => [
            'id' => $cot_product_id,
            'title' => $cot_product_title,
            'model' => [
                'slug' => $cot_model_slug,
                'name' => $cot_model_name,
                'year' => $cot_model_year,
                'price_usd' => $cot_model_price_usd,
                'price_local' => $cot_model_price_local,
            ],
            'color' => [
                'name' => $cot_color_name,
                'hex' => $cot_color_hex,
            ],
        ],
        'lead' => [
            'names' => $cot_names,
            'lastnames' => $cot_lastnames,
            'document_type' => $cot_document_type,
            'document' => $cot_document,
            'phone' => $cot_phone,
            'email' => $cot_email,
            'department' => $cot_department,
            'store' => $cot_store,
            'consent' => (bool)$cot_consent,
        ],
        'source' => [
            'site' => home_url('/'),
            'submitted_at' => current_time('mysql'),
            'post_id' => $post_id,
        ],
    ];

    $api_ok = 0;
    $api_status = 0;
    $api_response = '';
    $api_error = '';

    // Si aún no tienes endpoint, igual guardamos la cotización sin API
    if (!empty(MG_QUOTE_API_ENDPOINT) && strpos(MG_QUOTE_API_ENDPOINT, 'http') === 0) {

        $res = wp_remote_post(MG_QUOTE_API_ENDPOINT, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                // 'Authorization' => 'Bearer TU_TOKEN', // si aplica
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($res)) {
            $api_ok = 0;
            $api_error = $res->get_error_message();
        } else {
            $api_status = (int) wp_remote_retrieve_response_code($res);
            $api_response = (string) wp_remote_retrieve_body($res);
            $api_ok = ($api_status >= 200 && $api_status < 300) ? 1 : 0;
            if (!$api_ok) {
                $api_error = 'HTTP ' . $api_status;
            }
        }
    } else {
        $api_error = 'API endpoint no configurado';
    }

    update_field('cot_api_ok', $api_ok, $post_id);
    update_field('cot_api_status', $api_status, $post_id);
    update_field('cot_api_response', $api_response, $post_id);
    update_field('cot_api_error', $api_error, $post_id);
}, 10, 1);

if (!defined('MG_QUOTE_API_ENDPOINT')) {
  define('MG_QUOTE_API_ENDPOINT', ''); // vacío por ahora
}
