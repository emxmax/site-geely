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
