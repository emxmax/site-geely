<?php
if (!defined('ABSPATH')) exit;

/** =========================
 *  CONFIG
 *  ========================= */
if (!defined('MG_QUOTE_API_ENDPOINT')) {
  define('MG_QUOTE_API_ENDPOINT', 'https://ag-peru-experience-api-prod.us-e1.cloudhub.io/peru/lead');
}
if (!defined('MG_QUOTE_API_DEBUG')) {
  define('MG_QUOTE_API_DEBUG', true);
}

/** Globals para devolver al front */
$GLOBALS['mg_quote_last_api'] = null;
$GLOBALS['mg_quote_last_payload'] = null;

/** Helper debug */
if (!function_exists('mg_quote_log')) {
  function mg_quote_log($msg, $data = null)
  {
    if (!MG_QUOTE_API_DEBUG) return;
    if ($data !== null) error_log('[MG_QUOTE] ' . $msg . ' ' . wp_json_encode($data, JSON_UNESCAPED_UNICODE));
    else error_log('[MG_QUOTE] ' . $msg);
  }
}

/** Helper: tabla existe */
if (!function_exists('mg_quote_table_exists')) {
  function mg_quote_table_exists($table)
  {
    global $wpdb;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    if ($table === '') return false;

    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    $wpdb->suppress_errors(false);

    return !empty($exists);
  }
}

/** Map tipo doc API */
if (!function_exists('mg_map_doc_type')) {
  function mg_map_doc_type($v)
  {
    $v = strtoupper(trim((string)$v));
    $map = [
      'DNI' => '01',
      'PASAPORTE' => '02',
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
}

/** Parse store id (por si viniera "ID|Nombre") */
if (!function_exists('mg_quote_parse_store_id')) {
  function mg_quote_parse_store_id($raw)
  {
    $raw = (string)$raw;
    if ($raw === '') return 0;

    if (strpos($raw, '|') !== false) {
      $parts = explode('|', $raw);
      $raw = $parts[0];
    }
    return (int)preg_replace('/[^0-9]/', '', $raw);
  }
}

/** Lookup tienda */
if (!function_exists('mg_quote_lookup_store_meta')) {
  function mg_quote_lookup_store_meta($store_id)
  {
    global $wpdb;

    $store_id = (int)$store_id;
    if (!$store_id) return [null, null, null];
    if (!mg_quote_table_exists('bp_tiendas')) return [null, null, null];

    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);

    $cols = $wpdb->get_results("SHOW COLUMNS FROM bp_tiendas", ARRAY_A);

    $hasCol = function ($field) use ($cols) {
      foreach ((array)$cols as $c) {
        if (!empty($c['Field']) && $c['Field'] === $field) return true;
      }
      return false;
    };

    $colNombre = $hasCol('NombreComercial') ? 'NombreComercial' : ($hasCol('Tienda') ? 'Tienda' : '');
    $colVenta  = $hasCol('CodigoVenta') ? 'CodigoVenta' : '';
    $colCanal  = $hasCol('codigoCanal') ? 'codigoCanal' : '';

    $select = [];
    if ($colNombre) $select[] = "`$colNombre` AS nombre";
    if ($colVenta)  $select[] = "`$colVenta` AS codigoventa";
    if ($colCanal)  $select[] = "`$colCanal` AS codigocanal";

    if (empty($select)) {
      $wpdb->suppress_errors(false);
      return [null, null, null];
    }

    $sql = $wpdb->prepare(
      "SELECT " . implode(', ', $select) . "
       FROM bp_tiendas
       WHERE TiendaId = %d
       LIMIT 1",
      $store_id
    );

    $row = $wpdb->get_row($sql, ARRAY_A);
    $wpdb->suppress_errors(false);

    if (!$row) return [null, null, null];

    $nombre = isset($row['nombre']) ? trim((string)$row['nombre']) : null;
    $venta  = isset($row['codigoventa']) ? trim((string)$row['codigoventa']) : null;
    $canal  = isset($row['codigocanal']) ? trim((string)$row['codigocanal']) : null;

    return [$nombre ?: null, $venta ?: null, $canal ?: null];
  }
}

/** Enviar a API */
if (!function_exists('mg_quote_send_api')) {
  function mg_quote_send_api($payload)
  {
    $out = ['ok' => 0, 'status' => 0, 'response' => '', 'error' => ''];

    if (empty(MG_QUOTE_API_ENDPOINT) || strpos(MG_QUOTE_API_ENDPOINT, 'http') !== 0) {
      $out['error'] = 'API endpoint no configurado';
      return $out;
    }

    // Basic Auth
    $user = '1a88d1e3f8004af48dae91fdaee1ad1d';
    $pass = '25f688A7F6d249AD93B77EB2822e4356';
    $auth = base64_encode($user . ':' . $pass);

    // APIKIT pide wrapper data
    $body = ['data' => $payload];

    $GLOBALS['mg_quote_last_payload'] = $body;

    mg_quote_log('PAYLOAD', $payload);
    mg_quote_log('BODY', $body);

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
}

/** =========================
 *  1) CF7 BEFORE SEND MAIL: llama API + prepara globals para AJAX
 *  ========================= */
add_action('wpcf7_before_send_mail', function ($contact_form) {

  $submission = WPCF7_Submission::get_instance();
  if (!$submission) return;

  $data = $submission->get_posted_data();
  if (empty($data) || !is_array($data)) return;

  $get = function ($key) use ($data) {
    $v = $data[$key] ?? '';
    if (is_array($v)) $v = reset($v);
    return is_string($v) ? trim($v) : $v;
  };

  // Lead
  $cot_product_id        = (int) $get('product_id');
  $cot_product_title     = (string) $get('product_title');
  $cot_model_slug        = (string) $get('model_slug');
  $cot_model_name        = (string) $get('model_name');
  $cot_model_year        = (string) $get('model_year');
  $cot_model_price_usd   = (string) $get('model_price_usd');
  $cot_model_price_local = (string) $get('model_price_local');

  $cot_names             = (string) $get('cot_names');
  $cot_lastnames         = (string) $get('cot_lastnames');
  $cot_document_type_raw = (string) $get('cot_document_type');
  $cot_document_type     = mg_map_doc_type($cot_document_type_raw);
  $cot_document          = (string) $get('cot_document');
  $cot_phone             = (string) $get('cot_phone');
  $cot_email             = (string) $get('cot_email');

  $cot_store_raw         = (string) $get('cot_store');
  $store_id_int          = mg_quote_parse_store_id($cot_store_raw);
  $cot_store             = (string)$store_id_int;

  // Meta tienda
  [$nombre_comercial_de_la_tienda, $codigo_venta_de_la_tienda, $codigo_canal_db] = mg_quote_lookup_store_meta($store_id_int);
  $nombre_comercial_de_la_tienda = $nombre_comercial_de_la_tienda ?: '';
  $codigo_venta_de_la_tienda     = $codigo_venta_de_la_tienda ?: '';

  // IDs API (strings)
  $nid_modelo = (string) $get('nid_modelo');

  // Tracking
  $utm_campaign = (string) $get('utm_campaign');
  $utm_content  = (string) $get('utm_content');
  $utm_medium   = (string) $get('utm_medium');
  $utm_source   = (string) $get('utm_source');
  $utm_term     = (string) $get('utm_term');
  $gclid        = (string) $get('gclid');

  // Consent
  $cot_consent_raw = $get('cot_consent');
  $cot_consent = (!empty($cot_consent_raw) && $cot_consent_raw !== '0') ? 1 : 0;

  // Defaults
  $co_familia      = 'PV';
  $co_origen       = '91';
  $co_canal        = $codigo_canal_db ?: 'CO';
  $fl_recibir_info = $cot_consent ? 'S' : 'N';
  $fl_fecha_consen = current_time('d/m/Y H:i:s');
  $fe_nacimiento   = '01/01/1900';

  // Extras (strings, NO int)
  $co_articulo      = (string) $get('co_articulo');
  $co_configuracion = (string) $get('co_configuracion');
  $gp_version       = (string) $get('gp_version');

  if ($co_articulo === '') $co_articulo = $cot_model_slug ?: (string)$cot_product_id;
  if ($gp_version === '')  $gp_version  = $cot_model_name;

  // Validación mínima
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

  // Apellido paterno / materno
  $parts = preg_split('/\s+/', trim($cot_lastnames));
  $ape_pat = $parts[0] ?? '';
  $ape_mat = trim(implode(' ', array_slice($parts, 1)));

  // Payload
  $payload = [
    'co_tipo_documento' => $cot_document_type,
    'nu_documento'      => $cot_document,

    'no_contacto'   => $cot_names,
    'no_ape_pat'    => $ape_pat,
    'no_ape_mat'    => $ape_mat,
    'fe_nacimiento' => $fe_nacimiento,

    'nu_celular' => $cot_phone,
    'no_correo'  => $cot_email,

    // TODO: por ahora fijo mientras corriges geo IDs
    'Coddpto' => "08",
    'Codprov' => "01",
    'Coddist' => "01",

    'no_direccion'     => "",
    'nid_marca'        => "GEE",
    'no_marca'         => "Geely",

    'nid_modelo'       => $nid_modelo,
    'co_articulo'      => $co_articulo,
    'co_configuracion' => $co_configuracion,

    'no_modelo'        => $cot_model_name,
    'pr_pub_usd'       => $cot_model_price_usd,
    'pr_pub_pen'       => $cot_model_price_local,

    'tx_comentario' => "[TIENDA: " . $nombre_comercial_de_la_tienda . "]",
    'fl_retoma'     => "N",
    'co_origen'     => $co_origen,
    'co_tienda'     => (string)$cot_store,
    'co_canal'      => $co_canal,
    'co_campaña'    => "",
    'no_plazo_compra' => "1 mes",

    'fl_recibir_info' => $fl_recibir_info,
    'fl_fecha_consen' => $fl_fecha_consen,
    'no_valor_adic'   => "NO",

    'nid_punto_venta' => (string)$codigo_venta_de_la_tienda,
    'co_familia'      => $co_familia,

    'fin_requerido'    => "",
    'fin_dependiente'  => "NO",
    'fin_rango'        => "NO",
    'ClientID'         => "NO",
    'GPVersion'        => $gp_version,
    'co_tipo_contacto' => "",
    'co_km'            => "",
    'co_placa'         => "",
    'co_ano'           => $cot_model_year,
    'co_inversion'     => "0",
    'co_transmision'   => "",
    'co_tipo_trabajador' => "",

    // tracking
    'utm_campaign' => $utm_campaign,
    'utm_content'  => $utm_content,
    'utm_medium'   => $utm_medium,
    'utm_source'   => $utm_source,
    'utm_term'     => $utm_term,
    'gclid'        => $gclid,
  ];

  $api = mg_quote_send_api($payload);

  $GLOBALS['mg_quote_last_api'] = [
    'ok'       => (int)$api['ok'],
    'status'   => (int)$api['status'],
    'error'    => (string)$api['error'],
    'response' => (string)$api['response'],
    'debug'    => [
      'store_id' => $store_id_int,
      'NombreComercial' => $nombre_comercial_de_la_tienda,
      'CodigoVenta' => $codigo_venta_de_la_tienda,
      'co_canal' => $co_canal,
    ],
  ];

}, 10, 1);

/** =========================
 *  2) Meter mg_api + mg_payload en respuesta AJAX CF7
 *  ========================= */
add_filter('wpcf7_ajax_json_echo', function ($response, $result) {

  if (!is_array($response)) $response = [];

  $response['mg_api'] = $GLOBALS['mg_quote_last_api'] ?? null;
  $response['mg_payload'] = !empty($GLOBALS['mg_quote_last_payload']) ? $GLOBALS['mg_quote_last_payload'] : null;

  return $response;
}, 10, 2);

/** =========================
 *  3) Cuando el mail YA SE ENVIÓ: guardar CPT + guardar respuesta API en ACF
 *  ========================= */
add_action('wpcf7_mail_sent', function ($contact_form) {

  $submission = WPCF7_Submission::get_instance();
  if (!$submission) return;

  $data = $submission->get_posted_data();
  if (empty($data) || !is_array($data)) return;

  // helper para obtener string "limpio"
  $get = function ($key) use ($data) {
    $v = $data[$key] ?? '';
    if (is_array($v)) $v = reset($v);
    return is_string($v) ? trim($v) : $v;
  };

  // =========================
  // Datos del form
  // =========================
  $cot_product_id        = (int) $get('product_id');
  $cot_product_title     = (string) $get('product_title');
  $cot_model_slug        = (string) $get('model_slug');
  $cot_model_name        = (string) $get('model_name');
  $cot_model_year        = (string) $get('model_year');
  $cot_model_price_usd   = (string) $get('model_price_usd');
  $cot_model_price_local = (string) $get('model_price_local');

  $cot_color_name        = (string) $get('color_name');
  $cot_color_hex         = (string) $get('color_hex');

  $cot_names             = sanitize_text_field($get('cot_names'));
  $cot_lastnames         = sanitize_text_field($get('cot_lastnames'));
  $cot_document_type     = sanitize_text_field($get('cot_document_type'));
  $cot_document          = sanitize_text_field($get('cot_document'));
  $cot_phone             = sanitize_text_field($get('cot_phone'));
  $cot_email             = sanitize_email($get('cot_email'));
  $cot_department        = sanitize_text_field($get('cot_department'));
  $cot_store             = sanitize_text_field($get('cot_store'));

  // =========================
  // 1) Crear CPT
  // =========================
  $post_id = wp_insert_post([
    'post_type'   => 'cotizacion',  // ✅ tu slug real
    'post_status' => 'publish',
    'post_title'  => "Cotización - {$cot_names} {$cot_lastnames} ({$cot_product_title})",
  ], true);

  if (is_wp_error($post_id) || !$post_id) {
    if (function_exists('mg_quote_log')) {
      mg_quote_log('CPT ERROR', [
        'err' => is_wp_error($post_id) ? $post_id->get_error_message() : 'post_id empty'
      ]);
    }
    return;
  }

  // =========================
  // 2) Guardar ACF (según tu grupo "Cotizacion - Datos")
  // =========================
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
    update_field('cot_document_type', $cot_document_type, $post_id);
    update_field('cot_document', $cot_document, $post_id);
    update_field('cot_phone', $cot_phone, $post_id);
    update_field('cot_email', $cot_email, $post_id);
    update_field('cot_department', $cot_department, $post_id);
    update_field('cot_store', $cot_store, $post_id);

    // Estado/Notas (opcional)
    // OJO: "cot_status" es un SELECT: el valor debe existir en las opciones del campo.
    // Si no estás segura del value exacto, déjalo vacío.
    // update_field('cot_status', 'nuevo', $post_id);
    update_field('cot_notes', '', $post_id);
  }

  // =========================
  // 3) Guardar respuesta del API en ACF
  // =========================
  $api_last = $GLOBALS['mg_quote_last_api'] ?? null;

  if (function_exists('update_field')) {
    update_field('cot_api_ok', (string)($api_last['ok'] ?? ''), $post_id);
    update_field('cot_api_status', (string)($api_last['status'] ?? ''), $post_id);
    update_field('cot_api_response', (string)($api_last['response'] ?? ''), $post_id);
    update_field('cot_api_error', (string)($api_last['error'] ?? ''), $post_id);
  }

  // (Opcional) también lo guardo como meta crudo por auditoría
  if ($api_last) {
    update_post_meta($post_id, '_mg_api_result', wp_json_encode($api_last, JSON_UNESCAPED_UNICODE));
  }

}, 10, 1);

/** =========================================
 *  4) CF7 Dynamic Selects (bp_regiones / bp_tiendas)
 *  ========================================= */
add_filter('wpcf7_form_tag', function ($tag) {

  if (!is_object($tag) && !is_array($tag)) return $tag;

  $is_obj = is_object($tag);
  $name = $is_obj ? ($tag->name ?? '') : ($tag['name'] ?? '');
  if (!$name) return $tag;

  $basetype = $is_obj ? ($tag->basetype ?? '') : ($tag['basetype'] ?? '');
  if ($basetype !== 'select') return $tag;

  global $wpdb;

  $set_prop = function ($key, $val) use (&$tag, $is_obj) {
    if ($is_obj) $tag->$key = $val;
    else $tag[$key] = $val;
  };

  $ensure_placeholder = function (&$values, &$labels) {
    $values = is_array($values) ? $values : [''];
    $labels = is_array($labels) ? $labels : ['Selecciona una opción'];
    $values[0] = '';
    $labels[0] = 'Selecciona una opción';
  };

  $get_columns = function ($table) use ($wpdb) {
    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);
    $cols = $wpdb->get_results("SHOW COLUMNS FROM `$table`", ARRAY_A);
    $wpdb->suppress_errors(false);

    $out = [];
    foreach ((array)$cols as $c) {
      if (!empty($c['Field'])) $out[] = (string)$c['Field'];
    }
    return $out;
  };

  $pick_first_existing = function (array $columns, array $candidates) {
    foreach ($candidates as $c) {
      if (in_array($c, $columns, true)) return $c;
    }
    return '';
  };

  /** DEPARTAMENTO: bp_regiones */
  if ($name === 'cot_department') {
    $values = [''];
    $labels = ['Selecciona una opción'];

    if (mg_quote_table_exists('bp_regiones')) {
      $cols = $get_columns('bp_regiones');
      $colId   = $pick_first_existing($cols, ['RegionId']);
      $colName = $pick_first_existing($cols, ['Descripcion']);

      if ($colId && $colName) {
        $wpdb->hide_errors();
        $wpdb->suppress_errors(true);

        $rows = $wpdb->get_results(
          "SELECT `$colId` AS code, `$colName` AS name
           FROM `bp_regiones`
           ORDER BY `$colName` ASC",
          ARRAY_A
        );

        $wpdb->suppress_errors(false);

        foreach ((array)$rows as $r) {
          $code = trim((string)($r['code'] ?? ''));
          $lab  = trim((string)($r['name'] ?? ''));
          if ($code === '' || $lab === '') continue;
          $values[] = $code;
          $labels[] = $lab;
        }
      }
    }

    $ensure_placeholder($values, $labels);

    $set_prop('raw_values', $values);
    $set_prop('values', $values);
    $set_prop('labels', $labels);

    if (class_exists('WPCF7_Pipes')) $set_prop('pipes', new WPCF7_Pipes($values));

    return $tag;
  }

  /** TIENDA: bp_tiendas */
  if ($name === 'cot_store') {
    $values = [''];
    $labels = ['Selecciona una opción'];

    if (mg_quote_table_exists('bp_tiendas')) {
      $cols = $get_columns('bp_tiendas');

      $colId   = $pick_first_existing($cols, ['TiendaId']);
      $colName = $pick_first_existing($cols, ['Tienda', 'NombreComercial']);

      $hasActivo = in_array('Activo', $cols, true);
      $hasOrden  = in_array('TiendaOrden', $cols, true);

      if ($colId && $colName) {
        $where = $hasActivo ? "WHERE (`Activo` = 1 OR `Activo` IS NULL)" : "";
        $order = $hasOrden
          ? "ORDER BY (CASE WHEN `TiendaOrden` IS NULL THEN 999999 ELSE `TiendaOrden` END) ASC, `$colName` ASC"
          : "ORDER BY `$colName` ASC";

        $wpdb->hide_errors();
        $wpdb->suppress_errors(true);

        $rows = $wpdb->get_results(
          "SELECT `$colId` AS id, `$colName` AS name
           FROM `bp_tiendas`
           $where
           $order",
          ARRAY_A
        );

        $wpdb->suppress_errors(false);

        foreach ((array)$rows as $r) {
          $id  = trim((string)($r['id'] ?? ''));
          $lab = trim((string)($r['name'] ?? ''));
          if ($id === '' || $lab === '') continue;

          $values[] = $id;   // SOLO ID
          $labels[] = $lab;
        }
      }
    }

    $ensure_placeholder($values, $labels);

    $set_prop('raw_values', $values);
    $set_prop('values', $values);
    $set_prop('labels', $labels);

    if (class_exists('WPCF7_Pipes')) $set_prop('pipes', new WPCF7_Pipes($values));

    return $tag;
  }

  return $tag;
}, 20, 1);
