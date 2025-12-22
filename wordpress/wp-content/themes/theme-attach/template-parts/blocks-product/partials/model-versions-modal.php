<?php if (!defined('ABSPATH')) exit; ?>

<div class="mf-modal" id="mfModal" aria-hidden="true">
    <div class="mf-modal__overlay js-mf-close"></div>

    <div class="mf-modal__dialog" role="dialog" aria-modal="true">
        <button class="mf-modal__close js-mf-close" aria-label="Cerrar">×</button>

        <h2 class="mf-modal__title">Versiones</h2>
        <h3 class="mf-modal__product" id="mfModalTitle"></h3>

        <div class="mf-modal__content">
            <!-- Imagen + precio -->
            <div class="mf-modal__left">
                <img id="mfModalImg" class="mf-modal__img" alt="">
                <div class="mf-modal__price">
                    <span>Precio desde</span>
                    <strong id="mfModalPrice"></strong>
                </div>

                <select id="mfModalSelect" class="mf-modal__select"></select>
            </div>

            <!-- Specs -->
            <div class="mf-modal__specs">
                <h4>Especificaciones</h4>
                <ul>
                    <li><strong>Potencia máxima</strong><span id="mfSpecPower"></span></li>
                    <li><strong>Transmisión</strong><span id="mfSpecTransmission"></span></li>
                    <li><strong>Seguridad</strong><span id="mfSpecSecurity"></span></li>
                    <li><strong>Asientos</strong><span id="mfSpecSeating"></span></li>
                    <li><strong>Encendido</strong><span id="mfSpecPush"></span></li>
                </ul>
            </div>
        </div>
    </div>
</div>