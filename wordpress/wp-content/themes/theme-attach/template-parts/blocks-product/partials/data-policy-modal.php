<?php
if (!defined('ABSPATH')) exit;

$policy_id = !empty($policy_id) ? $policy_id : 'mg-data-policy-modal';

?>

<div class="mg-dataModal" data-data-policy-modal id="<?php echo esc_attr($policy_id); ?>" aria-hidden="true">
    <div class="mg-dataModal__overlay" data-policy-close></div>

    <div class="mg-dataModal__card"  style="background-image: url('<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/bg-modal.png'); ?>');"  role="dialog" aria-modal="true" aria-label="Política de protección de datos personales">
        <!-- <div class="mg-dataModal__head">
            <div class="mg-dataModal__kicker">Política de datos</div>
            <button class="mg-dataModal__x" type="button" aria-label="Cerrar" data-policy-close>×</button>
        </div> -->

        <div class="mg-dataModal__body">
            <h3 class="mg-dataModal__title">Política de protección de datos personales</h3>

            <div class="mg-dataModal__content">
                <p><strong>Política de protección de datos personales</strong></p>
                <p>Los datos personales que nos facilite serán incorporados a nuestro Banco de Datos “Clientes y Contactos” (Registro N° 4227) de titularidad de la empresa Maquinaria Nacional S.A.C. Perú, con domicilio en Av. Cristóbal de Peralta Norte N° 968, Santiago de Surco, Lima, cuyo encargado es nuestro Ejecutivo de Marketing Digital y CRM, debidamente registrada de acuerdo a la Ley N° 29733, Ley de Protección de Datos Personales, con la finalidad de gestionar su solicitud de cotización, compra de vehículo y/o realizar la encuesta de satisfacción al cliente. Para dicho efecto los datos se almacenarán, recopilarán, registrarán, organizarán, conservarán, usarán y transferirán a nivel nacional (Concesionario Autorizados de la Red - https://geely.pe/red-de-atencion/, SUM S.A.C. y Socios Estratégicos - https://geely.pe/socios-estrategicos/ ), e internacional (Maquinaria Nacional S.A.C. Perú contrata su infraestructura virtual según un modelo de “computación en nube” a través de un servidor web, el cual se encuentra ubicado en la ciudad de San Francisco /California – USA y almacenamiento de datos en el datacenter de Microsoft Azure ubicado en Sao Paulo - Brasil).</p></br>
                <p>Adicionalmente, en cumplimiento de la Ley N° 29571, Código de Protección y Defensa del Consumidor, y de la Ley N° 29733, Ley de Protección de Datos Personales, Ud. autoriza, salvo marque la casilla “No autorizo” ( ), a las empresas del Grupo Gildemeister Perú (Automotores Gildemeister Perú SAC, Maquinaria Nacional SAC Perú, Motor Mundo SAC) de manera previa, expresa, inequívoca, libre, informada, por el plazo de diez años luego de registrados sus datos, a tratar sus datos personales para el envío de cualquier tipo de información, incluyendo la referida a promociones, comunicaciones comerciales de sus productos, servicios y cualquier otra de su interés; así como para la realización estudios de mercado sobre los productos o servicios Geely, a través de comunicaciones a su domicilio, correo electrónico, mensaje de texto, whatsapp, llamadas telefónicas realizadas por TELEATENTO DEL PERÚ S.A.C. identificada con RUC N° 20414989277 (en un horario de lunes a viernes de 08:30 am hasta las 06:00 pm, sábados de 08:00 am hasta las 06:00 pm y domingos y feriados de 09:00 am hasta las 06:00 pm) o cualquier otro medio de difusión; así como a transferir sus datos a nivel nacional (Concesionario Autorizados de la Red - https://geely.pe/red-de-atencion/, SUM S.A.C. y Socios Estratégicos - https://geely.pe/socios-estrategicos/ ), e internacional a Automotores Gildemeister S.A. (Chile), GEELY (Beijing - China), Facebook y Google (Atlanta - USA), Ingeniería de Software Fidelizador y Compañía Limitada, (Santiago de Chile - Chile), para los fines indicados en este párrafo.</p>
                <div class="mg-dataModal__box-info">
                    <img
                        src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/icon-info.png'); ?>"
                        alt="icon-search"
                        class="">
                    <p>Usted tiene los derechos de información, acceso, rectificación, cancelación, oposición y tratamiento objetivo de Datos Personales. Para hacer uso de estos derechos deberá comunicarse al siguiente correo electrónico <a href="mailto:protecciondedatospersonales@gildemeister.pe">protecciondedatospersonales@gildemeister.pe</a> </p>
                </div>
            </div>

            <div class="mg-dataModal__actions">
                <button class="mg-dataModal__btn" type="button" data-policy-close>Cerrar</button>
            </div>
        </div>
    </div>
</div>