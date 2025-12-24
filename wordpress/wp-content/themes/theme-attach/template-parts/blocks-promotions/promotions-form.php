<?php
/**
 * Bloque: Formulario Promoción
 * 
 * Muestra el formulario de Contact Form 7 para promociones
 */

if (!defined('ABSPATH'))
  exit;

// Campo ACF del bloque
$form_shortcode = function_exists('get_field') ? get_field('form_shortcode') : '';
$form_title = function_exists('get_field') ? get_field('form_title') : 'COTIZA TU GEELY';

// Si no hay shortcode en el bloque, intentar obtenerlo de ACF del post
if (empty($form_shortcode)) {
  $post_id = get_the_ID();
  $form_shortcode = function_exists('get_field') ? get_field('promocion_form_shortcode', $post_id) : '';
}

// Si aún no hay shortcode, usar uno por defecto (puedes cambiarlo)
if (empty($form_shortcode)) {
  // Ejemplo: [contact-form-7 id="123" title="Formulario Promociones"]
  $form_shortcode = '[contact-form-7 id="1" title="Contact form 1"]';
}
?>

<section class="promotions-form">
  <div class="promotions-form__inner">
    
    <div class="geely-form">
      
      <?php if ($form_title): ?>
        <h2 class="geely-form__title">
          <?php echo esc_html($form_title); ?>
        </h2>
      <?php endif; ?>

      <?php
      // Renderizar el shortcode de Contact Form 7
      echo do_shortcode($form_shortcode);
      ?>

    </div>

  </div>
</section>
