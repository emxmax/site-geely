<?php
/**
 * Theme Attach - functions.php
 */
if (!defined('ABSPATH')) {
    exit;
}

// Global
require_once get_stylesheet_directory() . '/inc/enqueue-assets.php';
require_once get_stylesheet_directory() . '/inc/enqueue-fonts.php';

// ACF blocks
require_once get_stylesheet_directory() . '/inc/acf-blocks/index.php';

// Product domain
require_once get_stylesheet_directory() . '/inc/product/assets.php';
require_once get_stylesheet_directory() . '/inc/product/zip-360.php';

// Page domain
require_once get_stylesheet_directory() . '/inc/page/assets.php';

// News Page 
require_once get_stylesheet_directory() . '/inc/new/assets.php';

// Blog domain
require_once get_stylesheet_directory() . '/inc/blog/assets.php';
