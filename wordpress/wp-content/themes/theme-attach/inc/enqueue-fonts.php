<?php
if ( ! defined('ABSPATH') ) exit;

function attach_enqueue_fonts() {
    $css_rel_path = '/assets/css/fonts.css';
    $css_abs_path = get_template_directory() . $css_rel_path;

    wp_enqueue_style(
        'attach-fonts',
        get_template_directory_uri() . $css_rel_path,
        [],
        file_exists($css_abs_path) ? filemtime($css_abs_path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'attach_enqueue_fonts');
