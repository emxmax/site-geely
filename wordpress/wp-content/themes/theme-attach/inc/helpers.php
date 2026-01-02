<?php
/**
 * Helpers Globales del Tema
 * 
 * Funciones reutilizables en todos los dominios del tema.
 * Para helpers específicos de un dominio, usar inc/{domain}/helpers.php
 */

if (!defined('ABSPATH'))
  exit;

/**
 * Obtener términos de una taxonomía que tienen posts publicados
 * 
 * @param string $taxonomy Nombre de la taxonomía
 * @param array $args Argumentos adicionales para get_terms()
 * @return WP_Term[] Array de términos o array vacío si hay error
 */
function theme_attach_get_terms_with_posts(string $taxonomy, array $args = []): array
{
  $defaults = [
    'taxonomy' => $taxonomy,
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC',
  ];

  $terms = get_terms(array_merge($defaults, $args));

  return is_wp_error($terms) ? [] : $terms;
}

/**
 * Sanitizar número de teléfono (solo dígitos y +)
 * 
 * @param string|array $phone Número de teléfono sin sanitizar (string o array)
 * @return string Teléfono sanitizado
 */
function theme_attach_sanitize_phone($phone): string
{
  // Manejar arrays (tomar primer elemento)
  if (is_array($phone)) {
    $phone = !empty($phone) ? $phone[0] : '';
  }

  // Convertir a string si no lo es
  $phone = (string) $phone;

  return preg_replace('/[^0-9+]/', '', $phone);
}

/**
 * Formatear precio en formato peruano (S/)
 * 
 * @param float $amount Monto a formatear
 * @param bool $include_symbol Incluir símbolo de moneda
 * @return string Precio formateado
 */
function theme_attach_format_price(float $amount, bool $include_symbol = true): string
{
  $formatted = number_format($amount, 2, '.', ',');
  return $include_symbol ? 'S/ ' . $formatted : $formatted;
}

/**
 * Obtener URL de imagen de un post con fallback
 * 
 * @param int $post_id ID del post
 * @param string $size Tamaño de imagen
 * @param string $fallback_url URL de fallback si no hay imagen
 * @return string URL de la imagen o fallback
 */
function theme_attach_get_post_image_url(int $post_id, string $size = 'large', string $fallback_url = ''): string
{
  $image_id = get_post_thumbnail_id($post_id);

  if (!$image_id) {
    return $fallback_url;
  }

  $image_url = wp_get_attachment_image_url($image_id, $size);

  return $image_url ?: $fallback_url;
}

/**
 * Obtener alt text de imagen featured con fallback al título del post
 * 
 * @param int $post_id ID del post
 * @return string Alt text de la imagen
 */
function theme_attach_get_post_image_alt(int $post_id): string
{
  $image_id = get_post_thumbnail_id($post_id);

  if (!$image_id) {
    return get_the_title($post_id);
  }

  $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);

  return $alt ?: get_the_title($post_id);
}

/**
 * Verificar si ACF está activo
 * 
 * @return bool True si ACF está activo
 */
function theme_attach_is_acf_active(): bool
{
  return function_exists('get_field');
}

/**
 * Truncar texto a un número de palabras
 * 
 * @param string $text Texto a truncar
 * @param int $words Número de palabras
 * @param string $more Texto al final si se trunca
 * @return string Texto truncado
 */
function theme_attach_truncate_words(string $text, int $words = 20, string $more = '...'): string
{
  $text = wp_strip_all_tags($text);
  $words_array = explode(' ', $text);

  if (count($words_array) <= $words) {
    return $text;
  }

  return implode(' ', array_slice($words_array, 0, $words)) . $more;
}

/**
 * Parsear coordenadas desde string
 * 
 * Acepta formatos:
 * - "-9.485725658859815, -77.53700189768016"
 * - "-9.485725658859815,-77.53700189768016"
 * - "lat: -9.485725658859815, lng: -77.53700189768016"
 * 
 * @param string $coordinates String con coordenadas
 * @return array|null ['lat' => float, 'lng' => float] o null si inválido
 */
function theme_attach_parse_coordinates(
  string $coordinates
): ?array {
  if (empty($coordinates)) {
    return null;
  }

  // Limpiar espacios extras y texto descriptivo
  $coordinates = trim($coordinates);
  $coordinates = preg_replace(
    '/\s+/',
    ' ',
    $coordinates
  ); // Normalizar espacios
  $coordinates = str_replace(
    ['lat:', 'lng:', 'latitude:', 'longitude:'],
    '',
    strtolower($coordinates)
  );
  $coordinates = trim($coordinates);

  // Extraer números (incluyendo decimales negativos)
  preg_match_all('/-?\d+\.?\d*/', $coordinates, $matches);

  if (empty($matches[0]) || count($matches[0]) < 2) {
    if (WP_DEBUG) {
      error_log("[Theme Attach] Coordenadas inválidas: {$coordinates}");
    }
    return null;
  }

  $lat = floatval($matches[0][0]);
  $lng = floatval($matches[0][1]);

  // Validar rangos válidos
  // Latitud: -90 a 90
  // Longitud: -180 a 180
  if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    if (WP_DEBUG) {
      error_log("[Theme Attach] Coordenadas fuera de rango: lat={$lat}, lng={$lng}");
    }
    return null;
  }

  return [
    'lat' => $lat,
    'lng' => $lng
  ];
}