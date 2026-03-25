<?php
if (!defined('ABSPATH'))
    exit;

add_action('wp_enqueue_scripts', function () {
    $ver = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'mg-footer',
        get_theme_file_uri('/assets/css/footer.css'),
        [],
        $ver
    );

    wp_enqueue_script(
        'mg-footer',
        get_theme_file_uri('/assets/js/footer.js'),
        [],
        $ver,
        true
    );
});


// Imprimir variables JS y script para rellenar campos ocultos en promociones
add_action('wp_footer', function () {
        if (!is_singular('promocion')) return;
        $post_id = get_the_ID();
        $co_articulo = esc_js(get_field('promocion_codigo_producto', $post_id));
        $co_configuracion = esc_js(get_field('promocion_codigo_configuracion', $post_id));
        $nid_punto_venta = esc_js(get_field('promocion_codigo_punto_venta', $post_id));
        ?>
        <script>
        window.geelyPromoCodes = {
            co_articulo: "<?php echo $co_articulo; ?>",
            co_configuracion: "<?php echo $co_configuracion; ?>",
            nid_punto_venta: "<?php echo $nid_punto_venta; ?>"
        };
        document.addEventListener('DOMContentLoaded', function() {
            if (window.geelyPromoCodes) {
                var setVal = function(name, value) {
                    var els = document.getElementsByName(name);
                    for (var i = 0; i < els.length; i++) {
                        els[i].value = value || '';
                    }
                };
                setVal('co_articulo', window.geelyPromoCodes.co_articulo);
                setVal('co_configuracion', window.geelyPromoCodes.co_configuracion);
                setVal('nid_punto_venta', window.geelyPromoCodes.nid_punto_venta);
            }
        });
        </script>
        <?php
});
