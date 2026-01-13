<?php
if (!defined('ABSPATH')) exit;

/** =========================================
 *  CONFIG
 *  ========================================= */
if (!defined('MG_QUOTE_API_ENDPOINT')) {
  define('MG_QUOTE_API_ENDPOINT', 'https://ag-peru-experience-api-prod.us-e1.cloudhub.io/peru/lead');
}
if (!defined('MG_QUOTE_API_DEBUG')) {
  define('MG_QUOTE_API_DEBUG', true); // true => error_log
}

/**
 * OJO: si lo pones en true, CF7 devolverá en JSON el body enviado (debug_request).
 * En PROD déjalo en false.
 */
if (!defined('MG_QUOTE_EXPOSE_DEBUG')) {
  define('MG_QUOTE_EXPOSE_DEBUG', false);
}

/** Guardaremos el último resultado del API para devolverlo al front (CF7 ajax JSON) */
$GLOBALS['mg_quote_last_api'] = null;

/** =========================================
 *  DEBUG LOG
 *  ========================================= */
if (!function_exists('mg_quote_log')) {
  function mg_quote_log($msg, $data = null) {
    if (!MG_QUOTE_API_DEBUG) return;
    if ($data !== null) {
      error_log('[MG_QUOTE] ' . $msg . ' ' . wp_json_encode($data, JSON_UNESCAPED_UNICODE));
    } else {
      error_log('[MG_QUOTE] ' . $msg);
    }
  }
}

/** =========================================
 *  Helpers: Store parsing + DB lookup codes
 *  Tablas: bp_tiendas -> bp_comunas -> bp_provincias -> bp_regiones
 *  ========================================= */
if (!function_exists('mg_quote_parse_store_id')) {
  function mg_quote_parse_store_id($raw) {
    $raw = (string) $raw;
    if ($raw === '') return 0;

    // si te llega "ID|Nombre"
    if (strpos($raw, '|') !== false) {
      $parts = explode('|', $raw);
      $raw = $parts[0];
    }

    return (int) preg_replace('/[^0-9]/', '', $raw);
  }
}

if (!function_exists('mg_quote_table_exists')) {
  function mg_quote_table_exists($table) {
    global $wpdb;
    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);
    $found = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    $wpdb->suppress_errors(false);
    return !empty($found);
  }
}

if (!function_exists('mg_quote_has_column')) {
  function mg_quote_has_column($table, $column) {
    global $wpdb;
    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);
    $cols = $wpdb->get_results("SHOW COLUMNS FROM `$table`", ARRAY_A);
    $wpdb->suppress_errors(false);

    foreach ((array)$cols as $c) {
      if (!empty($c['Field']) && $c['Field'] === $column) return true;
    }
    return false;
  }
}

if (!function_exists('mg_quote_lookup_codes_by_store')) {
  function mg_quote_lookup_codes_by_store($store_id) {
    global $wpdb;

    $store_id = (int)$store_id;
    if (!$store_id) return [null, null, null];

    // Evitar HTML wpdb error en pantalla (rompe JSON CF7)
    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);

    // Validar tablas
    if (
      !mg_quote_table_exists('bp_tiendas') ||
      !mg_quote_table_exists('bp_comunas') ||
      !mg_quote_table_exists('bp_provincias') ||
      !mg_quote_table_exists('bp_regiones')
    ) {
      $wpdb->suppress_errors(false);
      return [null, null, null];
    }

    // Detectar columnas Gildemeister si existen
    $rCol = mg_quote_has_column('bp_regiones', 'RegionIdGildemeister') ? 'r.RegionIdGildemeister' : 'r.RegionId';
    $pCol = mg_quote_has_column('bp_provincias', 'ProvinciaIdGildemeister') ? 'p.ProvinciaIdGildemeister' : 'p.ProvinciaId';
    $cCol = mg_quote_has_column('bp_comunas', 'ComunaIdGildemeister') ? 'c.ComunaIdGildemeister' : 'c.ComunaId';

    // OJO: según tus screenshots, bp_tiendas tiene ComunaId y bp_provincias tiene RegionId
    $sql = $wpdb->prepare(
      "SELECT
          {$rCol} AS coddpto,
          {$pCol} AS codprov,
          {$cCol} AS coddist
       FROM bp_tiendas t
       INNER JOIN bp_comunas c ON c.ComunaId = t.ComunaId
       INNER JOIN bp_provincias p ON p.ProvinciaId = c.ProvinciaId
       INNER JOIN bp_regiones r ON r.RegionId = p.RegionId
       WHERE t.TiendaId = %d
       LIMIT 1",
      $store_id
    );

    $row = $wpdb->get_row($sql, ARRAY_A);

    $wpdb->suppress_errors(false);

    if (!$row) return [null, null, null];

    return [
      isset($row['coddpto']) ? (string)$row['coddpto'] : null,
      isset($row['codprov']) ? (string)$row['codprov'] : null,
      isset($row['coddist']) ? (string)$row['coddist'] : null,
    ];
  }
}

/** =========================================
 *  Map Tipo Documento requerido por API: 01..06
 *  ========================================= */
if (!function_exists('mg_map_doc_type')) {
  function mg_map_doc_type($v) {
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

/** =========================================
 *  API call (log request body + response)
 *  ========================================= */
if (!function_exists('mg_quote_send_api')) {
  function mg_quote_send_api($payload) {
    $out = [
      'ok' => 0,
      'status' => 0,
      'response' => '',
      'error' => '',
      'debug_request' => null,
    ];

    if (empty(MG_QUOTE_API_ENDPOINT) || strpos(MG_QUOTE_API_ENDPOINT, 'http') !== 0) {
      $out['error'] = 'API endpoint no configurado';
      return $out;
    }

    // Basic Auth
    $user = '1a88d1e3f8004af48dae91fdaee1ad1d';
    $pass = '25f688A7F6d249AD93B77EB2822e4356';
    $auth = base64_encode($user . ':' . $pass);

    // APIKIT pide wrapper "data"
    $body = ['data' => $payload];

    // 👇 ESTE ES EL "BODY ENVIADO" (lo verás en error_log)
    mg_quote_log('REQUEST BODY (to API)', $body);

    $out['debug_request'] = $body; // opcional para devolverlo en CF7 JSON

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

    mg_quote_log('API RESPONSE', [
      'status' => $out['status'],
      'ok' => $out['ok'],
      'body' => $out['response'],
      'error' => $out['error'],
    ]);

    return $out;
  }
}

/** =========================================
 *  1) Submit CF7: guardar cotización + disparar API
 *  ========================================= */
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

  // ===== Contexto (hidden) =====
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
  $cot_document_type      = mg_map_doc_type($cot_document_type_raw);
  $cot_document           = (string) $get('cot_document');
  $cot_phone              = (string) $get('cot_phone');
  $cot_email              = (string) $get('cot_email');
  $cot_department         = (string) $get('cot_department');
  $cot_store              = (string) $get('cot_store'); // debería ser SOLO ID (por nuestro select)

  // Derivar códigos por tienda
  $store_id_int = mg_quote_parse_store_id($cot_store);
  [$code_dpto, $code_prov, $code_dist] = mg_quote_lookup_codes_by_store($store_id_int);

  if (empty($code_dpto)) $code_dpto = $cot_department;

  // IDs API (hidden)
  $nid_marca   = (string) $get('nid_marca');
  $nid_modelo  = (string) $get('nid_modelo');

  // Consent
  $cot_consent_raw = $get('cot_consent');
  $cot_consent = (!empty($cot_consent_raw) && $cot_consent_raw !== '0') ? 1 : 0;

  // Tracking
  $utm_campaign = (string) $get('utm_campaign');
  $utm_content  = (string) $get('utm_content');
  $utm_medium   = (string) $get('utm_medium');
  $utm_source   = (string) $get('utm_source');
  $utm_term     = (string) $get('utm_term');
  $gclid        = (string) $get('gclid');
  $fbclid       = (string) $get('fbclid');

  // Validación mínima
  if (!$cot_product_id || !$cot_names || !$cot_lastnames || !$cot_phone || !$cot_email) {
    mg_quote_log('VALIDATION missing fields', [
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

  // Campos obligatorios API (según tu integración)
  $co_familia      = 'PV';  // PV/LCV/CV/SN
  $co_canal        = 'CO';  // SU o CO
  $co_origen       = 'WEB'; // 3 chars
  $fl_recibir_info = $cot_consent ? 'S' : 'N';
  $fl_fecha_consen = current_time('d/m/Y H:i:s');
  $fe_nacimiento   = '01/01/1900';

  // Payload
  $payload = [
    'co_tipo_documento' => $cot_document_type,
    'nu_documento'      => $cot_document,

    'no_contacto' => $cot_names,
    'no_ape_pat'  => $ape_pat,
    'no_ape_mat'  => $ape_mat,

    'no_correo'   => $cot_email,
    'nu_celular'  => $cot_phone,

    'nid_marca'   => $nid_marca,
    'nid_modelo'  => $nid_modelo,

    'Coddpto'     => (string) $code_dpto,
    'Codprov'     => (string) ($code_prov ?? ''),
    'Coddist'     => (string) ($code_dist ?? ''),

    // tienda: aquí estás mandando ID (ideal)
    'co_tienda'        => (string) $cot_store,
    'nid_punto_venta'  => (string) $cot_store,

    'GPVersion' => $cot_model_name,
    'co_ano'    => $cot_model_year,

    'co_familia'      => $co_familia,
    'co_canal'        => $co_canal,
    'co_origen'       => $co_origen,
    'fl_recibir_info' => $fl_recibir_info,
    'fl_fecha_consen' => $fl_fecha_consen,
    'fe_nacimiento'   => $fe_nacimiento,

    'utm_campaign' => $utm_campaign,
    'utm_content'  => $utm_content,
    'utm_medium'   => $utm_medium,
    'utm_source'   => $utm_source,
    'utm_term'     => $utm_term,
    'gclid'        => $gclid,
    'fbclid'       => $fbclid,
  ];

  // Guardar post local (opcional)
  $title = sprintf('Cotización - %s %s (%s)', $cot_names, $cot_lastnames, $cot_product_title ?: ('ID ' . $cot_product_id));
  $post_id = wp_insert_post([
    'post_type'   => 'cotizacion',
    'post_status' => 'publish',
    'post_title'  => wp_strip_all_tags($title),
  ], true);

  if (!is_wp_error($post_id) && $post_id && function_exists('update_field')) {
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
    update_field('cot_consent', $cot_consent, $post_id);
  } else {
    $post_id = 0; // por si falló
  }

  // Disparar API
  $api = mg_quote_send_api($payload);

  // Guardar auditoría
  if ($post_id && function_exists('update_field')) {
    update_field('cot_api_ok', (int)$api['ok'], $post_id);
    update_field('cot_api_status', (int)$api['status'], $post_id);
    update_field('cot_api_response', (string)$api['response'], $post_id);
    update_field('cot_api_error', (string)$api['error'], $post_id);
  }

  // Respuesta al front (CF7 JSON)
  $GLOBALS['mg_quote_last_api'] = [
    'ok'     => (int)$api['ok'],
    'status' => (int)$api['status'],
    'error'  => (string)$api['error'],
    'response' => (string)$api['response'],
    'post_id' => (int)$post_id,
  ];

  // Para “consolear el payload body enviado”
  if (MG_QUOTE_EXPOSE_DEBUG) {
    $GLOBALS['mg_quote_last_api']['debug_request'] = $api['debug_request'];
  }

}, 10, 1);

/** =========================================
 *  2) Inyectar mg_api en respuesta AJAX de CF7 (1 SOLA VEZ)
 *  ========================================= */
add_filter('wpcf7_ajax_json_echo', function ($response, $result) {
  if (!is_array($response)) $response = [];

  $response['mg_api'] = !empty($GLOBALS['mg_quote_last_api'])
    ? $GLOBALS['mg_quote_last_api']
    : ['ok' => 0, 'status' => 0, 'error' => 'mg_quote_last_api vacío'];

  return $response;
}, 10, 2);

/** =========================================
 *  3) CF7 Dynamic Selects (bp_regiones / bp_tiendas)
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

  // util: leer columnas de una tabla
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

  /** ========= DEPARTAMENTO: bp_regiones ========= */
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

    if (class_exists('WPCF7_Pipes')) {
      $set_prop('pipes', new WPCF7_Pipes($values));
    }

    return $tag;
  }

  /** ========= TIENDA: bp_tiendas ========= */
  if ($name === 'cot_store') {
    $values = [''];
    $labels = ['Selecciona una opción'];

    if (mg_quote_table_exists('bp_tiendas')) {
      $cols = $get_columns('bp_tiendas');

      // según tu screenshot:
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

          // value SOLO ID (no "ID|Nombre")
          $values[] = $id;
          $labels[] = $lab;
        }
      }
    }

    $ensure_placeholder($values, $labels);

    $set_prop('raw_values', $values);
    $set_prop('values', $values);
    $set_prop('labels', $labels);

    if (class_exists('WPCF7_Pipes')) {
      $set_prop('pipes', new WPCF7_Pipes($values));
    }

    return $tag;
  }

  return $tag;
}, 20, 1);
