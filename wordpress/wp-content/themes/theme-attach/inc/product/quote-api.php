<?php
if (!defined('ABSPATH')) exit;

/** =========================
 *  CONFIG
 *  ========================= */
if (!defined('MG_QUOTE_API_ENDPOINT')) {
    define('MG_QUOTE_API_ENDPOINT', 'https://ag-peru-experience-api-prod.us-e1.cloudhub.io/peru/lead');
}
if (!defined('MG_QUOTE_API_DEBUG')) {
    define('MG_QUOTE_API_DEBUG', true); // true => escribe en error_log
}

/** Guardaremos el último resultado del API para devolverlo al front (CF7 ajax JSON) */
$GLOBALS['mg_quote_last_api'] = null;

/** Helper debug */
function mg_quote_log($msg, $data = null)
{
    if (!MG_QUOTE_API_DEBUG) return;

    if ($data !== null) {
        error_log('[MG_QUOTE] ' . $msg . ' ' . wp_json_encode($data, JSON_UNESCAPED_UNICODE));
    } else {
        error_log('[MG_QUOTE] ' . $msg);
    }
}

/** Map de Tipo Documento requerido por API: 01..06 */
function mg_map_doc_type($v)
{
    $v = strtoupper(trim((string)$v));
    $map = [
        'DNI' => '01',
        'Pasaporte' => '02',
        'RUC' => '03',
        'CE' => '04',
        'CI' => '05',
        'OTRO' => '06',
        '01' => '01',
        '02' => '02',
        '03' => '03',
        '04' => '04',
        '05' => '05',
        '06' => '06',
    ];
    return $map[$v] ?? $v;
}

/** Enviar a API y retornar auditoría */
function mg_quote_send_api($payload)
{
    $out = [
        'ok' => 0,
        'status' => 0,
        'response' => '',
        'error' => '',
    ];

    if (empty(MG_QUOTE_API_ENDPOINT) || strpos(MG_QUOTE_API_ENDPOINT, 'http') !== 0) {
        $out['error'] = 'API endpoint no configurado';
        return $out;
    }

    // Basic Auth (usuario:password) -> base64
    $user = '1a88d1e3f8004af48dae91fdaee1ad1d';
    $pass = '25f688A7F6d249AD93B77EB2822e4356';
    $auth = base64_encode($user . ':' . $pass);

    // APIKIT pide que vaya envuelto en "data"
    $body = ['data' => $payload];

    mg_quote_log('POST -> API', [
        'endpoint' => MG_QUOTE_API_ENDPOINT,
        'headers'  => ['Authorization' => 'Basic ***'],
        'body'     => $body,
    ]);

    $res = wp_remote_post(MG_QUOTE_API_ENDPOINT, [
        'timeout' => 60,
        'httpversion' => '1.1',
        'sslverify' => true,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Basic ' . $auth,
        ],
        'body' => wp_json_encode($body),
    ]);

    if (is_wp_error($res)) {
        $out['error'] = $res->get_error_message();
        mg_quote_log('API ERROR (wp_error)', $out);
        return $out;
    }

    $out['status']   = (int) wp_remote_retrieve_response_code($res);
    $out['response'] = (string) wp_remote_retrieve_body($res);
    $out['ok']       = ($out['status'] >= 200 && $out['status'] < 300) ? 1 : 0;

    if (!$out['ok']) $out['error'] = 'HTTP ' . $out['status'];

    mg_quote_log('API RESULT', $out);
    return $out;
}

/** =========================
 *  1) Guardar cotización + disparar API en el submit de CF7
 *  ========================= */
add_action('wpcf7_before_send_mail', function ($contact_form) {

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();
    if (empty($data) || !is_array($data)) return;

    // Helper
    $get = function ($key) use ($data) {
        $v = $data[$key] ?? '';
        if (is_array($v)) $v = reset($v);
        return is_string($v) ? trim($v) : $v;
    };

    // ===== Contexto =====
    $cot_product_id         = (int) $get('product_id');
    $cot_product_title      = (string) $get('product_title');
    $cot_model_slug         = (string) $get('model_slug');
    $cot_model_name         = (string) $get('model_name');
    $cot_model_year         = (string) $get('model_year');
    $cot_model_price_usd    = (string) $get('model_price_usd');
    $cot_model_price_local  = (string) $get('model_price_local');
    $cot_color_name         = (string) $get('color_name');
    $cot_color_hex          = (string) $get('color_hex');

    // ===== Lead =====
    $cot_names              = (string) $get('cot_names');
    $cot_lastnames          = (string) $get('cot_lastnames');
    $cot_document_type_raw  = (string) $get('cot_document_type');
    $cot_document_type      = mg_map_doc_type($cot_document_type_raw); // MAP
    $cot_document           = (string) $get('cot_document');
    $cot_phone              = (string) $get('cot_phone');
    $cot_email              = (string) $get('cot_email');
    $cot_department         = (string) $get('cot_department'); // ideal: código 2 dígitos
    $cot_store              = (string) $get('cot_store');      // ideal: punto de venta (id/código)

    // IDs API (hidden)
    $nid_marca              = (string) $get('nid_marca');
    $nid_modelo             = (string) $get('nid_modelo');

    // Tracking
    $utm_campaign           = (string) $get('utm_campaign');
    $utm_content            = (string) $get('utm_content');
    $utm_medium             = (string) $get('utm_medium');
    $utm_source             = (string) $get('utm_source');
    $utm_term               = (string) $get('utm_term');
    $gclid                  = (string) $get('gclid');
    $fbclid                 = (string) $get('fbclid');

    // Consent
    $cot_consent_raw = $get('cot_consent');
    $cot_consent = (!empty($cot_consent_raw) && $cot_consent_raw !== '0') ? 1 : 0;

    // Campos obligatorios API (defaults)
    $co_familia      = 'PV'; // PV/LCV/CV/SN
    $co_canal        = 'CO'; // SU o CO
    $co_origen       = 'WEB'; // 3 chars (si te dieron otro, cámbialo)
    $fl_recibir_info = $cot_consent ? 'S' : 'N';
    $fl_fecha_consen = current_time('d/m/Y H:i:s'); // DD/MM/YYYY HH:MM:SS
    $fe_nacimiento   = '01/01/1900'; // el API lo está pidiendo como key

    // Validación mínima local
    if (!$cot_product_id || !$cot_names || !$cot_lastnames || !$cot_phone || !$cot_email) {
        mg_quote_log('VALIDATION: missing required fields', [
            'product_id' => $cot_product_id,
            'names' => $cot_names,
            'lastnames' => $cot_lastnames,
            'phone' => $cot_phone,
            'email' => $cot_email
        ]);
        return;
    }

    // ===== Crear post cotizacion =====
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
        mg_quote_log('WP INSERT ERROR', ['error' => is_wp_error($post_id) ? $post_id->get_error_message() : 'unknown']);
        return;
    }

    // ===== Guardar ACF (si ACF está activo) =====
    if (function_exists('update_field')) {
        update_field('cot_product_id', $cot_product_id, $post_id);
        update_field('cot_product_title', $cot_product_title, $post_id);
        update_field('cot_model_slug', $cot_model_slug, $post_id);
        update_field('cot_model_name', $cot_model_name, $post_id);
        update_field('cot_model_year', $cot_model_year, $post_id);
        update_field('cot_model_price_usd', $cot_model_price_usd, $post_id);
        update_field('cot_model_price_local', $cot_model_price_local, $post_id);
        update_field('cot_color_name', $cot_color_name, $post_id);
        update_field('cot_color_hex', $cot_color_hex, $post_id);

        update_field('cot_names', $cot_names, $post_id);
        update_field('cot_lastnames', $cot_lastnames, $post_id);
        update_field('cot_document_type', $cot_document_type, $post_id); // guardamos ya mapeado
        update_field('cot_document', $cot_document, $post_id);
        update_field('cot_phone', $cot_phone, $post_id);
        update_field('cot_email', $cot_email, $post_id);
        update_field('cot_department', $cot_department, $post_id);
        update_field('cot_store', $cot_store, $post_id);
        update_field('cot_consent', $cot_consent, $post_id);
    }

    // Apellido paterno / materno
    $parts = preg_split('/\s+/', trim($cot_lastnames));
    $ape_pat = $parts[0] ?? '';
    $ape_mat = trim(implode(' ', array_slice($parts, 1)));

    /**
     * Payload API: recuerda que el API te está validando keys en /data/...
     * Ya está envuelto en "data" en mg_quote_send_api().
     */
    $payload = [
        'co_tipo_documento' => $cot_document_type,
        'nu_documento'      => $cot_document,

        'no_contacto' => $cot_names,
        'no_ape_pat'  => $ape_pat,
        'no_ape_mat'  => $ape_mat,

        'no_correo'  => $cot_email,
        'nu_celular' => $cot_phone,

        'nid_marca'  => $nid_marca,
        'nid_modelo' => $nid_modelo,
        'Coddpto'         => $cot_department,

        // tienda/punto de venta: si te piden nid_punto_venta usa ese:
        'co_tienda'        => $cot_store,
        'nid_punto_venta'  => $cot_store,

        'GPVersion' => $cot_model_name,
        'co_ano'    => $cot_model_year,

        // obligatorios por API (según tu error)
        'co_familia'      => $co_familia,
        'co_canal'        => $co_canal,
        'co_origen'       => $co_origen,
        'fl_recibir_info' => $fl_recibir_info,
        'fl_fecha_consen' => $fl_fecha_consen,
        'fe_nacimiento'   => $fe_nacimiento,

        // tracking
        'utm_campaign' => $utm_campaign,
        'utm_content'  => $utm_content,
        'utm_medium'   => $utm_medium,
        'utm_source'   => $utm_source,
        'utm_term'     => $utm_term,
        'gclid'        => $gclid,
        'fbclid'       => $fbclid,

        // extra auditoría local (no debería romper, pero si el API es estricto, lo quitas)
        'source' => [
            'site' => home_url('/'),
            'submitted_at' => current_time('mysql'),
            'post_id' => (int) $post_id,
        ],
    ];

    // ===== Disparar API YA (mismo submit) =====
    $api = mg_quote_send_api($payload);

    // Guardar auditoría en ACF
    if (function_exists('update_field')) {
        update_field('cot_api_ok', (int)$api['ok'], $post_id);
        update_field('cot_api_status', (int)$api['status'], $post_id);
        update_field('cot_api_response', (string)$api['response'], $post_id);
        update_field('cot_api_error', (string)$api['error'], $post_id);
    }

    // Guardar para exponerlo al JSON de CF7 (front console)
    $GLOBALS['mg_quote_last_api'] = [
        'ok'     => (int)$api['ok'],
        'status' => (int)$api['status'],
        'error'  => (string)$api['error'],
        'response' => (string)$api['response'], // si hay data sensible, comenta esta línea
        'post_id' => (int)$post_id,
    ];
}, 10, 1);

/** =========================
 *  2) Meter el resultado del API en la respuesta AJAX JSON de CF7
 *  ========================= */
add_filter('wpcf7_ajax_json_echo', function ($response, $result) {
    if (!is_array($response)) $response = [];

    $response['mg_api'] = !empty($GLOBALS['mg_quote_last_api'])
        ? $GLOBALS['mg_quote_last_api']
        : [
            'ok' => 0,
            'status' => 0,
            'error' => 'mg_quote_last_api vacío (no corrió el hook o no llegó)',
        ];

    return $response;
}, 10, 2);


/** =========================
 *  2) Meter el resultado del API en la respuesta AJAX JSON de CF7
 *  (así lo ves en console en el navegador)
 *  ========================= */
add_filter('wpcf7_ajax_json_echo', function ($response, $result) {
    if (!is_array($response)) $response = [];

    if (!empty($GLOBALS['mg_quote_last_api'])) {
        $response['mg_api'] = $GLOBALS['mg_quote_last_api'];
    } else {
        $response['mg_api'] = [
            'ok' => 0,
            'status' => 0,
            'error' => 'mg_quote_last_api vacío (no corrió el hook o no llegó)',
        ];
    }

    return $response;
}, 10, 2);


if (!defined('MG_QUOTE_API_ENDPOINT')) {
    define('MG_QUOTE_API_ENDPOINT', ''); // vacío por ahora
}


add_filter('wpcf7_form_tag', function ($tag) {

    // CF7 puede pasar $tag como objeto o como array-like
    $is_obj = is_object($tag);
    $is_arr = is_array($tag);

    if (!$is_obj && !$is_arr) return $tag;

    // Leer name y basetype de forma compatible
    $name = $is_obj ? ($tag->name ?? '') : ($tag['name'] ?? '');
    if (!$name) return $tag;

    $basetype = $is_obj ? ($tag->basetype ?? '') : ($tag['basetype'] ?? '');
    if ($basetype !== 'select') return $tag;

    // Helpers para setear values/labels/raw_values en ambos formatos
    $set_prop = function ($key, $val) use (&$tag, $is_obj) {
        if ($is_obj) $tag->$key = $val;
        else $tag[$key] = $val;
    };

    $get_prop = function ($key, $default = []) use (&$tag, $is_obj) {
        $v = $is_obj ? ($tag->$key ?? $default) : ($tag[$key] ?? $default);
        return is_array($v) ? $v : $default;
    };

    // Placeholder fijo
    $ensure_placeholder = function (&$values, &$labels) {
        // Siempre forzamos primer option como placeholder con value ""
        if (empty($labels)) {
            $labels = ['Selecciona una opción'];
            $values = [''];
            return;
        }

        $labels[0] = 'Selecciona una opción';
        $values[0] = '';
    };

    // ===== DEPARTAMENTO =====
    if ($name === 'cot_department') {

        $values = [''];
        $labels = ['Selecciona una opción'];

        $terms = get_terms([
            'taxonomy'   => 'departamento',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $values[] = (string) $t->slug; // value estable
                $labels[] = (string) $t->name; // label visible
            }
        }

        $ensure_placeholder($values, $labels);

        $set_prop('raw_values', $values);
        $set_prop('values', $values);
        $set_prop('labels', $labels);

        // 🔥 CRÍTICO: reconstruir pipes (si no, CF7 valida con lista vieja)
        if (class_exists('WPCF7_Pipes')) {
            $set_prop('pipes', new WPCF7_Pipes($values));
        }

        return $tag;
    }

    // ===== TIENDA =====
    if ($name === 'cot_store') {

        $values = [''];
        $labels = ['Selecciona una opción'];

        $stores = get_posts([
            'post_type'      => 'tienda',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);

        if (!empty($stores)) {
            foreach ($stores as $id) {
                $id = (int) $id;
                $title = get_the_title($id);
                if (!$id || !$title) continue;

                $values[] = (string) $id;     // value = ID puro
                $labels[] = (string) $title;  // label visible
            }
        }

        $ensure_placeholder($values, $labels);

        $set_prop('raw_values', $values);
        $set_prop('values', $values);
        $set_prop('labels', $labels);

        // CRÍTICO: reconstruir pipes
        if (class_exists('WPCF7_Pipes')) {
            $set_prop('pipes', new WPCF7_Pipes($values));
        }

        return $tag;
    }

    return $tag;
}, 20, 1);
