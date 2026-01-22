<?php
if (!defined('ABSPATH')) exit;

/**
 * filter-store.php
 * Endpoints AJAX para:
 * - Tiendas por departamento (RegionId) -> mg_quote_get_stores
 * - Tienda más cercana (1 sola)        -> mg_quote_get_nearest_store
 * - Recomendaciones desde tabla intermedia -> mg_quote_get_store_recommendations
 *
 * Requiere tablas:
 * - geely.bp_tiendas (TiendaId, Tienda, RegionId, latitud, longitud, Activo, TiendaOrden)
 * - geely.bp_regiones (RegionId, Descripcion)
 * - geely.bp_tiendas_recomendaciones (RegionId, TiendaMainId, TiendaSubId, Activo)
 */

global $wpdb;

/** =========================
 * Helpers
 * ========================= */
if (!function_exists('mg_quote_ajax_check')) {
    function mg_quote_ajax_check()
    {
        $ok = check_ajax_referer('mg_quote_ajax', 'nonce', false);
        if (!$ok) {
            wp_send_json_error(['message' => 'Nonce inválido'], 403);
        }
    }
}

if (!function_exists('mg_quote_float')) {
    function mg_quote_float($v, $default = null)
    {
        if ($v === null || $v === '') return $default;
        $v = str_replace(',', '.', (string)$v);
        if (!is_numeric($v)) return $default;
        return (float)$v;
    }
}

if (!function_exists('mg_quote_int')) {
    function mg_quote_int($v, $default = null)
    {
        if ($v === null || $v === '') return $default;
        if (!is_numeric($v)) return $default;
        return (int)$v;
    }
}

if (!function_exists('mg_quote_normalize_store_row')) {
    function mg_quote_normalize_store_row(array $r)
    {
        $id = (int)($r['TiendaId'] ?? 0);
        $name = trim((string)($r['Tienda'] ?? ''));
        if ($id <= 0 || $name === '') return null;
        return ['id' => $id, 'name' => $name, 'label' => $name];
    }
}

/** =========================
 * 1) Tiendas por RegionId
 * Action: mg_quote_get_stores
 * Input: department (RegionId)
 * Output: items [{id, name, label}]
 * ========================= */
if (!function_exists('mg_quote_ajax_get_stores')) {
    function mg_quote_ajax_get_stores()
    {
        mg_quote_ajax_check();
        global $wpdb;

        $dept = isset($_POST['department']) ? mg_quote_int($_POST['department'], 0) : 0;
        if (!$dept) {
            wp_send_json_success(['items' => []], 200);
        }

        $sql = $wpdb->prepare("
      SELECT t.TiendaId, t.Tienda
      FROM geely.bp_tiendas t
      WHERE t.RegionId = %d
        AND (t.Activo = 1 OR t.Activo IS NULL)
      ORDER BY t.TiendaOrden ASC, t.Tienda ASC
    ", $dept);

        $rows = $wpdb->get_results($sql, ARRAY_A);

        $items = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $norm = mg_quote_normalize_store_row($r);
                if ($norm) $items[] = $norm;
            }
        }

        wp_send_json_success(['items' => $items], 200);
    }
}

add_action('wp_ajax_mg_quote_get_stores', 'mg_quote_ajax_get_stores');
add_action('wp_ajax_nopriv_mg_quote_get_stores', 'mg_quote_ajax_get_stores');


/** =========================
 * 2) Tienda más cercana (1 sola)
 * Action: mg_quote_get_nearest_store
 * Input: lat, lng
 * Output: item {id, name, regionId, distanceKm}
 * ========================= */
if (!function_exists('mg_quote_ajax_get_nearest_store')) {
    function mg_quote_ajax_get_nearest_store()
    {
        mg_quote_ajax_check();
        global $wpdb;

        $lat = isset($_POST['lat']) ? mg_quote_float($_POST['lat']) : null;
        $lng = isset($_POST['lng']) ? mg_quote_float($_POST['lng']) : null;

        if ($lat === null || $lng === null) {
            wp_send_json_error(['message' => 'Faltan lat/lng'], 400);
        }

        $sql = $wpdb->prepare("
      SELECT
        t.TiendaId,
        t.Tienda,
        t.RegionId,
        t.latitud,
        t.longitud,
        (
          6371 * ACOS(
            COS(RADIANS(%f)) * COS(RADIANS(t.latitud)) *
            COS(RADIANS(t.longitud) - RADIANS(%f)) +
            SIN(RADIANS(%f)) * SIN(RADIANS(t.latitud))
          )
        ) AS distanceKm
      FROM geely.bp_tiendas t
      WHERE t.latitud IS NOT NULL
        AND t.longitud IS NOT NULL
        AND (t.Activo = 1 OR t.Activo IS NULL)
      ORDER BY distanceKm ASC
      LIMIT 1
    ", $lat, $lng, $lat);

        $row = $wpdb->get_row($sql, ARRAY_A);

        if (!$row) {
            wp_send_json_success(['item' => null], 200);
        }

        $item = [
            'id' => (int)($row['TiendaId'] ?? 0),
            'name' => (string)($row['Tienda'] ?? ''),
            'regionId' => (int)($row['RegionId'] ?? 0),
            'distanceKm' => isset($row['distanceKm']) ? round((float)$row['distanceKm'], 2) : null,
        ];

        if (!$item['id'] || !$item['regionId']) {
            wp_send_json_success(['item' => null], 200);
        }

        wp_send_json_success(['item' => $item], 200);
    }
}

add_action('wp_ajax_mg_quote_get_nearest_store', 'mg_quote_ajax_get_nearest_store');
add_action('wp_ajax_nopriv_mg_quote_get_nearest_store', 'mg_quote_ajax_get_nearest_store');


/** =========================
 * 3) Recomendaciones por tienda principal (tabla intermedia)
 * Action: mg_quote_get_store_recommendations
 * Input: regionId, tiendaMainId
 * Output: items [{id, name, label}]
 * ========================= */
if (!function_exists('mg_quote_ajax_get_store_recommendations')) {
    function mg_quote_ajax_get_store_recommendations()
    {
        mg_quote_ajax_check();
        global $wpdb;

        $regionId = isset($_POST['regionId']) ? mg_quote_int($_POST['regionId'], 0) : 0;

        // Acepta ambos nombres (JS manda mainStoreId)
        $mainId = 0;
        if (isset($_POST['mainStoreId'])) {
            $mainId = mg_quote_int($_POST['mainStoreId'], 0);
        } elseif (isset($_POST['tiendaMainId'])) {
            $mainId = mg_quote_int($_POST['tiendaMainId'], 0);
        }

        if (!$regionId || !$mainId) {
            wp_send_json_success(['items' => []], 200);
        }

        // Solo aplica para LIMA (RegionId = 16)
        if ((int)$regionId !== 16) {
            wp_send_json_success(['items' => []], 200);
        }

        // OJO: tu query correcta usa NombreComercial
        $sql = $wpdb->prepare("
      SELECT
        tsub.TiendaId AS id,
        tsub.NombreComercial AS name
      FROM geely.bp_tiendas_recomendaciones r
      INNER JOIN geely.bp_tiendas tsub ON tsub.TiendaId = r.TiendaSubId
      WHERE r.RegionId = %d
        AND r.TiendaMainId = %d
        AND r.Activo = 1
      ORDER BY tsub.NombreComercial
    ", $regionId, $mainId);

        $rows = $wpdb->get_results($sql, ARRAY_A);

        $items = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $id = (int)($r['id'] ?? 0);
                $name = trim((string)($r['name'] ?? ''));
                if ($id > 0 && $name !== '') {
                    $items[] = ['id' => $id, 'name' => $name, 'label' => $name];
                }
            }
        }

        wp_send_json_success(['items' => $items], 200);
    }
}

add_action('wp_ajax_mg_quote_get_store_recommendations', 'mg_quote_ajax_get_store_recommendations');
add_action('wp_ajax_nopriv_mg_quote_get_store_recommendations', 'mg_quote_ajax_get_store_recommendations');
