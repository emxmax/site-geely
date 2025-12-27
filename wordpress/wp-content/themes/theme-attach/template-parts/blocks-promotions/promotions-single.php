<?php
/**
 * Bloque: Contenido Single Promoción
 * 
 * Muestra imagen principal y contenido de la promoción
 */

if (!defined('ABSPATH'))
  exit;

if (!function_exists('get_field'))
  return;

if (!is_singular('promocion'))
  return;

$post_id = get_the_ID();

// Campos ACF opcionales del bloque
$image = get_field('promocion_content_image', $post_id) ?: null;
$content = get_field('promocion_content_text', $post_id) ?: null;
$image_url = '';
$image_alt = '';
if (empty($image)) {
  $image_id = get_post_thumbnail_id($post_id);
  $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
  $image_alt = $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : get_the_title($post_id);
} else {
  $image_url = isset($image['url']) ? $image['url'] : '';
  $image_alt = isset($image['alt']) ? $image['alt'] : get_the_title($post_id);
} ?>

<section class="promotions-single-section">
  <div class="promotions-single-section__inner">
    <div class="promotions-single">
      <div class="promotions-single__inner">
        <?php if ($image_url): ?>
          <div class="promotions-single__image">
            <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr($image_alt); ?>" loading="lazy">
          </div>
        <?php endif; ?>
        <?php if ($content): ?>
          <div class="promotions-single__content paragraph-4 paragraph-sm-5">
            <?= wp_kses_post($content); ?>
          </div>
          <span id="ver-mas" class="promotions-single__ver-mas paragraph-4">Ver más</span>
        <?php endif; ?>
      </div>
    </div>

    <?php
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
    <div class="promotions-form">
      <div class="promotions-form__inner">
        <div class="geely-form">

          <!-- <?php if ($form_title): ?>
            <h2 class="geely-form__title title-5">
              <?= esc_html($form_title); ?>
            </h2>
          <?php endif; ?> -->

          <?php echo do_shortcode($form_shortcode); ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Modal de Términos y Condiciones -->
<div id="geely-terms-modal" class="geely-modal" aria-hidden="true">
  <div class="geely-modal__overlay" data-modal-close></div>
  <div class="geely-modal__container">
    <div class="geely-modal__header">
      <h2 class="geely-modal__title title-3">TÉRMINOS Y CONDICIONES</h2>
      <button type="button" class="geely-modal__close" data-modal-close aria-label="Cerrar">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>
    </div>
    <div class="geely-modal__content">
      <?php
      // Obtener términos y condiciones desde ACF o texto por defecto
      $terms_content = function_exists('get_field') ? get_field('global_terms_and_conditions', 'option') : '';

      if (empty($terms_content)) {
        $terms_content = '<p>Para participar en el sorteo, los interesados deberán separar un vehículo durante los eventos Geely Go, que se llevarán a cabo el sábado 18 de octubre de 2025 en la sucursal de GEELY SURQUILLO (Av. República de Panamá 4380, Surquillo) y el sábado 25 de octubre de 2025 en la sucursal de GEELY LA MOLINA (Av. Javier Prado Este 5446, La Molina), de 10:00 a.m. a 7:00 p.m. De forma automática entrarán en el sorteo todas las personas que realicen la separación de un vehículo GEELY en dichas fechas y tiendas. Stock máximo: 02 consolas portátiles Nintendo Switch 2. El ganador podrá elegir el día y la hora (de lunes a viernes de 8:30 am a 06:00 pm) que desea recibir su premio, sujeto a disponibilidad y confirmación, previa coordinación.</p>
<p>El sorteo se realizará entre el 28 de octubre al 14 de noviembre de 2025, a través de la plataforma Microsoft Teams, y será grabado. Un representante de la marca Geely Perú se comunicará directamente con el ganador utilizando los datos de contacto registrados al momento de la separación del vehículo durante el evento. La entrega de premios se realizará únicamente en nuestras oficinas ubicadas en Av. Cristóbal de Peralta N° 968, distrito de Santiago de Surco – Lima, previa coordinación. La entrega del premio no incluye pago de movilidad y/o traslados (gasolina, taxis ni otro concepto). Es indispensable que los ganadores se acerquen con su documento de identidad vigente (DNI, Carnet de Extranjería, Pasaporte) los cuales deben coincidir con los datos registrados. El premio podrá ser recogido en nuestras oficinas, máximo hasta 30 días calendario después de anunciado al ganador, es decir, 25 de noviembre de 2025, pasada la fecha de entrega del premio quedará sin efecto. El premio no podrá ser transferido a terceros, no es monetizable, acumulable ni canjeable por bienes, servicios o beneficios distintos a los indicados. El premio no puede ser acumulado con otras promociones ni transferidos a otras personas y/o familiares.</p>
<p>No participan del sorteo personas jurídicas ni colaboradores del Grupo Gildemeister, así como colaboradores de tiendas de concesionarios parte de la Red. Asimismo, Motor Mundo S.A.C. se reserva el derecho de descartar a los participantes que pueda considerar fraudulentos, así como a los que no reúnan los requisitos y condiciones establecidas en los presentes términos y condiciones, sin asumir ningún tipo de responsabilidad. Motor Mundo S.A.C. podrá suspender el sorteo, así como introducir las modificaciones que considere convenientes, sin previo aviso, en cuanto a las condiciones y características del sorteo, sin que ello genere reclamo alguno por parte de los participantes y/o los eventuales ganadores. Motor Mundo S.A.C. no se responsabilizará por cualquier daño o perjuicio que afecte a los ganadores por caso fortuito, fuerza mayor, o por actos realizados por los participantes o ganadores antes, durante o después de la ejecución del sorteo. Publicidad e imágenes referenciales.</p>';
      }

      echo wp_kses_post(wpautop($terms_content));
      ?>
    </div>
    <div class="geely-modal__footer paragraph-5">
      <button type="button" class="geely-modal__button title-7" data-modal-close>Aceptar</button>
    </div>
  </div>
</div>

<style>
  #geely-geo-btn {
    background-image: url('<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/icon-location.svg');
    background-repeat: no-repeat;
    background-position: left center;
    background-size: contain;
    display: inline-block;
    font-size: var(--fs-p-4);
    line-height: var(--lh-p-4);
    color: var(--c-greely-blue);
    font-weight: 500;
    padding-left: 28px;
  }
</style>