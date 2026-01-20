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

/** Helper: pad "08" */
if (!function_exists('mg_quote_pad2')) {
  function mg_quote_pad2($v)
  {
    $v = (string)$v;
    $v = preg_replace('/[^0-9]/', '', $v);
    if ($v === '') return '';
    return str_pad($v, 2, '0', STR_PAD_LEFT);
  }
}

/**
 * Lookup tienda:
 * devuelve: [nombre, codigoventa, codigocanal, RegionId, ProvinciaId, ComunaId]
 */
if (!function_exists('mg_quote_lookup_store_meta')) {
  function mg_quote_lookup_store_meta($store_id)
  {
    global $wpdb;

    $store_id = (int)$store_id;
    if (!$store_id) return [null, null, null, 0, 0, 0];
    if (!mg_quote_table_exists('bp_tiendas')) return [null, null, null, 0, 0, 0];

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

    $colRegion = $hasCol('RegionId') ? 'RegionId' : '';
    $colProv   = $hasCol('ProvinciaId') ? 'ProvinciaId' : '';
    $colComuna = $hasCol('ComunaId') ? 'ComunaId' : '';

    $select = [];
    if ($colNombre) $select[] = "`$colNombre` AS nombre";
    if ($colVenta)  $select[] = "`$colVenta` AS codigoventa";
    if ($colCanal)  $select[] = "`$colCanal` AS codigocanal";
    if ($colRegion) $select[] = "`$colRegion` AS region_id";
    if ($colProv)   $select[] = "`$colProv` AS provincia_id";
    if ($colComuna) $select[] = "`$colComuna` AS comuna_id";

    if (empty($select)) {
      $wpdb->suppress_errors(false);
      return [null, null, null, 0, 0, 0];
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

    if (!$row) return [null, null, null, 0, 0, 0];

    $nombre = isset($row['nombre']) ? trim((string)$row['nombre']) : null;
    $venta  = isset($row['codigoventa']) ? trim((string)$row['codigoventa']) : null;
    $canal  = isset($row['codigocanal']) ? trim((string)$row['codigocanal']) : null;

    $region_id    = isset($row['region_id']) ? (int)$row['region_id'] : 0;
    $provincia_id = isset($row['provincia_id']) ? (int)$row['provincia_id'] : 0;
    $comuna_id    = isset($row['comuna_id']) ? (int)$row['comuna_id'] : 0;

    return [$nombre ?: null, $venta ?: null, $canal ?: null, $region_id, $provincia_id, $comuna_id];
  }
}

/**
 * Map GEO:
 * - dpto: match bp_regiones.RegionId -> devuelve bp_regiones.regionIdAG
 * - prov: match bp_provincias.ProvinciaId -> devuelve bp_provincias.CodigoAG
 * - dist: match bp_comunas.ComunaId -> devuelve bp_comunas.RegionId (según pedido)
 *   fallback: bp_comunas.ComunaIdGildemeister (si RegionId viene vacío/0)
 */
if (!function_exists('mg_quote_geo_from_store_ids')) {
  function mg_quote_geo_from_store_ids($regionId, $provinciaId, $comunaId)
  {
    global $wpdb;

    $regionId = (int)$regionId;
    $provinciaId = (int)$provinciaId;
    $comunaId = (int)$comunaId;

    $out = ['dpto' => '', 'prov' => '', 'dist' => '', 'raw' => []];

    // ===== DPTO =====
    if ($regionId && mg_quote_table_exists('bp_regiones')) {
      $cols = $wpdb->get_results("SHOW COLUMNS FROM bp_regiones", ARRAY_A);
      $has = function ($f) use ($cols) {
        foreach ((array)$cols as $c) if (!empty($c['Field']) && $c['Field'] === $f) return true;
        return false;
      };

      if ($has('RegionId') && $has('regionIdAG')) {
        $sql = $wpdb->prepare(
          "SELECT regionIdAG FROM bp_regiones WHERE RegionId = %d LIMIT 1",
          $regionId
        );
        $val = $wpdb->get_var($sql);
        $out['dpto'] = mg_quote_pad2($val);
        $out['raw']['regionIdAG'] = $val;
      }
    }

    // ===== PROV =====
    if ($provinciaId && mg_quote_table_exists('bp_provincias')) {
      $cols = $wpdb->get_results("SHOW COLUMNS FROM bp_provincias", ARRAY_A);
      $has = function ($f) use ($cols) {
        foreach ((array)$cols as $c) if (!empty($c['Field']) && $c['Field'] === $f) return true;
        return false;
      };

      if ($has('ProvinciaId') && $has('CodigoAG')) {
        $sql = $wpdb->prepare(
          "SELECT CodigoAG FROM bp_provincias WHERE ProvinciaId = %d LIMIT 1",
          $provinciaId
        );
        $val = $wpdb->get_var($sql);
        $out['prov'] = mg_quote_pad2($val);
        $out['raw']['CodigoAG'] = $val;
      }
    }

    // ===== DIST =====
    if ($comunaId && mg_quote_table_exists('bp_comunas')) {
      $cols = $wpdb->get_results("SHOW COLUMNS FROM bp_comunas", ARRAY_A);
      $has = function ($f) use ($cols) {
        foreach ((array)$cols as $c) if (!empty($c['Field']) && $c['Field'] === $f) return true;
        return false;
      };

      // pedido: enviar RegionId
      $selectDist = [];
      if ($has('RegionId')) $selectDist[] = "RegionId";
      if ($has('ComunaIdGildemeister')) $selectDist[] = "ComunaIdGildemeister";

      if (!empty($selectDist) && $has('ComunaId')) {
        $sql = $wpdb->prepare(
          "SELECT " . implode(',', $selectDist) . " FROM bp_comunas WHERE ComunaId = %d LIMIT 1",
          $comunaId
        );
        $row = $wpdb->get_row($sql, ARRAY_A);

        $dist = '';
        if ($row) {
          // primero RegionId (según pedido)
          $regionDist = isset($row['RegionId']) ? (string)$row['RegionId'] : '';
          $regionDist = preg_replace('/[^0-9]/', '', $regionDist);

          if ($regionDist !== '' && (int)$regionDist > 0) {
            $dist = $regionDist;
            $out['raw']['bp_comunas.RegionId'] = $row['RegionId'];
          } else {
            // fallback
            $gid = isset($row['ComunaIdGildemeister']) ? (string)$row['ComunaIdGildemeister'] : '';
            $gid = preg_replace('/[^0-9]/', '', $gid);
            $dist = $gid;
            $out['raw']['bp_comunas.ComunaIdGildemeister'] = $row['ComunaIdGildemeister'] ?? '';
          }
        }

        $out['dist'] = mg_quote_pad2($dist);
      }
    }

    return $out;
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

  // Meta tienda + ids geo internos (RegionId/ProvinciaId/ComunaId)
  [
    $nombre_comercial_de_la_tienda,
    $codigo_venta_de_la_tienda,
    $codigo_canal_db,
    $store_region_id,
    $store_provincia_id,
    $store_comuna_id
  ] = mg_quote_lookup_store_meta($store_id_int);

  $nombre_comercial_de_la_tienda = $nombre_comercial_de_la_tienda ?: '';
  $codigo_venta_de_la_tienda     = $codigo_venta_de_la_tienda ?: '';

  // GEO codes (AG)
  $geo = mg_quote_geo_from_store_ids($store_region_id, $store_provincia_id, $store_comuna_id);

  // Si no logra mapear, conserva valores por defecto (los que tenías)
  $Coddpto = $geo['dpto'] ?: "08";
  $Codprov = $geo['prov'] ?: "01";
  $Coddist = $geo['dist'] ?: "01";

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

    // GEO dinámico desde tienda -> regiones/provincias/comunas
    'Coddpto' => (string)$Coddpto,
    'Codprov' => (string)$Codprov,
    'Coddist' => (string)$Coddist,

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

      // debug GEO
      'store.RegionId' => (int)$store_region_id,
      'store.ProvinciaId' => (int)$store_provincia_id,
      'store.ComunaId' => (int)$store_comuna_id,
      'geo.Coddpto' => (string)$Coddpto,
      'geo.Codprov' => (string)$Codprov,
      'geo.Coddist' => (string)$Coddist,
      'geo.raw' => $geo['raw'] ?? [],
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
    'post_type'   => 'cotizacion',
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
    'post_type'   => 'cotizacion',
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
    $values = is_array($values) ? array_values($values) : [''];
    $labels = is_array($labels) ? array_values($labels) : ['Selecciona una opción'];

    foreach ($labels as $i => $lab) {
      $t = strtolower(trim((string)$lab));
      if (strpos($t, 'seleccion') !== false) { // selecciona/seleccionar
        unset($labels[$i], $values[$i]);
      }
    }

    // reindex
    $values = array_values($values);
    $labels = array_values($labels);

    // fuerza placeholder final
    array_unshift($values, '');
    array_unshift($labels, 'Selecciona una opción');
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

    // Validaciones mínimas de tablas
    if (mg_quote_table_exists('bp_regiones') && mg_quote_table_exists('bp_tiendas')) {

      $colsReg = $get_columns('bp_regiones');
      $colId   = $pick_first_existing($colsReg, ['RegionId']);
      $colName = $pick_first_existing($colsReg, ['Descripcion']);

      // columnas bp_tiendas para decidir si existe RegionId o toca fallback
      $colsStores = $get_columns('bp_tiendas');
      $storesHasRegionId = in_array('RegionId', $colsStores, true);

      // opcional: filtrar solo tiendas activas si existe Activo
      $storesHasActivo = in_array('Activo', $colsStores, true);
      $storesActivoCond = $storesHasActivo ? " AND (t.`Activo` = 1 OR t.`Activo` IS NULL)" : "";

      if ($colId && $colName) {

        // ==========================
        // Caso 1: bp_tiendas tiene RegionId
        // ==========================
        if ($storesHasRegionId) {
          $sql = "
          SELECT r.`$colId` AS code, r.`$colName` AS name
          FROM `bp_regiones` r
          WHERE EXISTS (
            SELECT 1
            FROM `bp_tiendas` t
            WHERE t.`RegionId` = r.`$colId`
            $storesActivoCond
          )
          ORDER BY r.`$colName` ASC
        ";

          $wpdb->hide_errors();
          $wpdb->suppress_errors(true);
          $rows = $wpdb->get_results($sql, ARRAY_A);
          $wpdb->suppress_errors(false);

          foreach ((array)$rows as $r) {
            $code = trim((string)($r['code'] ?? ''));
            $lab  = trim((string)($r['name'] ?? ''));
            if ($code === '' || $lab === '') continue;
            $values[] = $code;
            $labels[] = $lab;
          }
        }

        // ==========================
        // Caso 2: fallback sin RegionId en bp_tiendas
        // ==========================
        else {
          // Requiere tablas intermedias y ComunaId en tiendas
          $storesHasComunaId = in_array('ComunaId', $colsStores, true);

          if (
            $storesHasComunaId &&
            mg_quote_table_exists('bp_comunas') &&
            mg_quote_table_exists('bp_provincias') &&
            mg_quote_table_exists('bp_regiones')
          ) {

            $sql = "
            SELECT r.`$colId` AS code, r.`$colName` AS name
            FROM `bp_regiones` r
            WHERE EXISTS (
              SELECT 1
              FROM `bp_tiendas` t
              INNER JOIN `bp_comunas` c ON c.`ComunaId` = t.`ComunaId`
              INNER JOIN `bp_provincias` p ON p.`ProvinciaId` = c.`ProvinciaId`
              WHERE p.`RegionId` = r.`$colId`
              $storesActivoCond
            )
            ORDER BY r.`$colName` ASC
          ";

            $wpdb->hide_errors();
            $wpdb->suppress_errors(true);
            $rows = $wpdb->get_results($sql, ARRAY_A);
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

/**
 * AJAX: tiendas más cercanas (SIN JOINS, solo lat/lng)
 */
if (!function_exists('mg_quote_ajax_nearest_stores')) {
  function mg_quote_ajax_nearest_stores()
  {
    if (!check_ajax_referer('mg_quote_ajax', 'nonce', false)) {
      wp_send_json_error(['message' => 'Nonce inválido'], 403);
    }

    global $wpdb;

    $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : 0;
    $lng = isset($_POST['lng']) ? (float) $_POST['lng'] : 0;

    if (!$lat || !$lng) {
      wp_send_json_success(['items' => [], 'debug' => ['reason' => 'missing lat/lng']]);
    }

    if (!mg_quote_table_exists('bp_tiendas')) {
      wp_send_json_success(['items' => [], 'debug' => ['reason' => 'bp_tiendas not found', 'db' => $wpdb->dbname ?? '']]);
    }

    // Detectar columnas lat/lng reales (case-insensitive)
    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);
    $cols = $wpdb->get_results("SHOW COLUMNS FROM `bp_tiendas`", ARRAY_A);
    $wpdb->suppress_errors(false);

    $latCol = '';
    $lngCol = '';

    $latCandidates = ['latitud', 'latitude', 'lat'];
    $lngCandidates = ['longitud', 'longitude', 'lng', 'lon'];

    foreach ((array)$cols as $c) {
      $f = (string)($c['Field'] ?? '');
      $fLower = strtolower($f);
      if (!$latCol && in_array($fLower, $latCandidates, true)) $latCol = $f;
      if (!$lngCol && in_array($fLower, $lngCandidates, true)) $lngCol = $f;
    }

    if ($latCol === '' || $lngCol === '') {
      wp_send_json_success([
        'items' => [],
        'debug' => [
          'reason' => 'lat/lng columns not detected',
          'columns' => array_map(fn($x) => $x['Field'] ?? '', (array)$cols)
        ]
      ]);
    }

    /**
     * Convertir varchar -> número de forma tolerante:
     * - quita espacios
     * - cambia coma decimal a punto
     * - "+ 0" fuerza numérico
     */
    $latNum = "(REPLACE(REPLACE(TRIM(t.`$latCol`), ' ', ''), ',', '.') + 0)";
    $lngNum = "(REPLACE(REPLACE(TRIM(t.`$lngCol`), ' ', ''), ',', '.') + 0)";

    $sql = $wpdb->prepare(
      "SELECT
          t.TiendaId AS id,
          COALESCE(NULLIF(TRIM(t.NombreComercial),''), NULLIF(TRIM(t.Tienda),'')) AS name,
          (
            6371 * 2 * ASIN(
              SQRT(
                POWER(SIN((RADIANS(%f) - RADIANS($latNum)) / 2), 2) +
                COS(RADIANS($latNum)) * COS(RADIANS(%f)) *
                POWER(SIN((RADIANS(%f) - RADIANS($lngNum)) / 2), 2)
              )
            )
          ) AS distance_km
       FROM bp_tiendas t
       WHERE TRIM(t.`$latCol`) <> '' AND TRIM(t.`$lngCol`) <> ''
         AND $latNum BETWEEN -90 AND 90
         AND $lngNum BETWEEN -180 AND 180
       ORDER BY distance_km ASC
       LIMIT 5",
      $lat,
      $lat,
      $lng
    );

    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);
    $rows = $wpdb->get_results($sql, ARRAY_A);
    $err  = $wpdb->last_error;
    $wpdb->suppress_errors(false);

    $items = [];
    foreach ((array)$rows as $r) {
      $id = (string)($r['id'] ?? '');
      $name = (string)($r['name'] ?? '');
      if ($id === '' || $name === '') continue;

      $items[] = [
        'id' => $id,
        'name' => $name,
        'distance_km' => isset($r['distance_km']) ? round((float)$r['distance_km'], 2) : null,
        'value' => $id . '|' . $name,
        'label' => $name,
      ];
    }

    wp_send_json_success([
      'items' => $items,
      'debug' => [
        'db' => $wpdb->dbname ?? '',
        'latCol' => $latCol,
        'lngCol' => $lngCol,
        'lat' => $lat,
        'lng' => $lng,
        'rows' => is_array($rows) ? count($rows) : 0,
        'sql_error' => $err ?: null,
      ]
    ]);
  }
}
add_action('wp_ajax_mg_quote_nearest_stores', 'mg_quote_ajax_nearest_stores');
add_action('wp_ajax_nopriv_mg_quote_nearest_stores', 'mg_quote_ajax_nearest_stores');

// ==============================
// AJAX: tiendas por departamento (REGIONID) - value SOLO ID
// ==============================
if (!function_exists('mg_quote_ajax_get_stores_by_department')) {
  function mg_quote_ajax_get_stores_by_department()
  {
    if (!check_ajax_referer('mg_quote_ajax', 'nonce', false)) {
      wp_send_json_error(['message' => 'Nonce inválido'], 403);
    }

    global $wpdb;

    $dept_raw = isset($_POST['department']) ? sanitize_text_field(wp_unslash($_POST['department'])) : '';
    $dept_raw = trim($dept_raw);

    // El select envía RegionId => int
    $regionId = (int) preg_replace('/[^0-9]/', '', $dept_raw);
    if ($regionId <= 0) {
      wp_send_json_success(['items' => []]);
    }

    if (!function_exists('mg_quote_table_exists') || !mg_quote_table_exists('bp_tiendas')) {
      wp_send_json_success(['items' => []]);
    }

    // Detectar columnas reales en bp_tiendas
    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);
    $cols = $wpdb->get_results("SHOW COLUMNS FROM `bp_tiendas`", ARRAY_A);
    $wpdb->suppress_errors(false);

    $fields = array_map(function ($c) {
      return (string)($c['Field'] ?? '');
    }, (array)$cols);

    $has = function ($f) use ($fields) {
      return in_array($f, $fields, true);
    };

    // Elegir expresión de nombre (prioridad: NombreComercial > Tienda > Nombre)
    $nameExpr = "''";
    if ($has('NombreComercial') && $has('Tienda')) {
      $nameExpr = "COALESCE(NULLIF(TRIM(t.`NombreComercial`),''), NULLIF(TRIM(t.`Tienda`),''))";
    } elseif ($has('NombreComercial')) {
      $nameExpr = "NULLIF(TRIM(t.`NombreComercial`),'')";
    } elseif ($has('Tienda')) {
      $nameExpr = "NULLIF(TRIM(t.`Tienda`),'')";
    } elseif ($has('Nombre')) {
      $nameExpr = "NULLIF(TRIM(t.`Nombre`),'')";
    }

    $items = [];

    // =========================================
    // Caso 1: bp_tiendas ya tiene RegionId
    // =========================================
    if ($has('RegionId')) {
      $sql = $wpdb->prepare(
        "SELECT
           t.`TiendaId` AS id,
           {$nameExpr} AS name
         FROM `bp_tiendas` t
         WHERE t.`RegionId` = %d
         ORDER BY name ASC",
        $regionId
      );

      $wpdb->hide_errors();
      $wpdb->suppress_errors(true);
      $rows = $wpdb->get_results($sql, ARRAY_A);
      $wpdb->suppress_errors(false);

      foreach ((array)$rows as $r) {
        $id = (string)((int)($r['id'] ?? 0));
        $name = trim((string)($r['name'] ?? ''));

        if ($id === '0' || $name === '') continue;

        $items[] = [
          'id'    => $id,
          'name'  => $name,
          'label' => $name,
          'value' => $id, // SOLO ID (sin pipes)
        ];
      }

      wp_send_json_success(['items' => $items]);
    }

    // =========================================
    // Caso 2: fallback con JOINS (si NO hay RegionId en bp_tiendas)
    // =========================================
    if (
      !mg_quote_table_exists('bp_comunas') ||
      !mg_quote_table_exists('bp_provincias') ||
      !mg_quote_table_exists('bp_regiones')
    ) {
      wp_send_json_success(['items' => []]);
    }

    // Necesitamos ComunaId en bp_tiendas para poder joinear
    if (!$has('ComunaId')) {
      wp_send_json_success(['items' => []]);
    }

    $sql = $wpdb->prepare(
      "SELECT
         t.`TiendaId` AS id,
         {$nameExpr} AS name
       FROM `bp_tiendas` t
       INNER JOIN `bp_comunas` c ON c.`ComunaId` = t.`ComunaId`
       INNER JOIN `bp_provincias` p ON p.`ProvinciaId` = c.`ProvinciaId`
       INNER JOIN `bp_regiones` r ON r.`RegionId` = p.`RegionId`
       WHERE r.`RegionId` = %d
       ORDER BY name ASC",
      $regionId
    );

    $wpdb->hide_errors();
    $wpdb->suppress_errors(true);
    $rows = $wpdb->get_results($sql, ARRAY_A);
    $wpdb->suppress_errors(false);

    foreach ((array)$rows as $r) {
      $id = (string)((int)($r['id'] ?? 0));
      $name = trim((string)($r['name'] ?? ''));

      if ($id === '0' || $name === '') continue;

      $items[] = [
        'id'    => $id,
        'name'  => $name,
        'label' => $name,
        'value' => $id, // SOLO ID (sin pipes)
      ];
    }

    wp_send_json_success(['items' => $items]);
  }
}

if (!has_action('wp_ajax_mg_quote_get_stores')) {
  add_action('wp_ajax_mg_quote_get_stores', 'mg_quote_ajax_get_stores_by_department');
  add_action('wp_ajax_nopriv_mg_quote_get_stores', 'mg_quote_ajax_get_stores_by_department');
}
