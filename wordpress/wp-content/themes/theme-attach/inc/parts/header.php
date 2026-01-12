<?php
if (!defined('ABSPATH'))
    exit;

add_action('wp_enqueue_scripts', function () {
    $ver = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'mg-header',
        get_theme_file_uri('/assets/css/header.css'),
        [],
        $ver
    );

    wp_enqueue_script(
        'mg-header',
        get_theme_file_uri('/assets/js/header.js'),
        [],
        $ver,
        true
    );
});
