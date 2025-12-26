<?php
/**
 * Bloque: Formulario Promoción
 * 
 * Muestra el formulario de Contact Form 7 para promociones
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

if (!is_singular('promocion'))
  return;

$post_id = get_the_ID();

// Campos ACF
$form_object = get_field('promocion_form_contact_form', $post_id) ?: null;
$form_title = get_field('promocion_form_title', $post_id) ?: 'COTIZA TU GEELY';


// Generar shortcode
$form_shortcode = '';
if (!empty($form_object) && !empty($form_object->ID)) {
  $form_id = $form_object->ID;
  $form_shortcode = "[contact-form-7 id=\"{$form_id}\"]";
}
// Fallback a formulario
if (empty($form_shortcode)) {
  $form_shortcode = '[contact-form-7 title="Cotiza tu Geely"]';
} ?>