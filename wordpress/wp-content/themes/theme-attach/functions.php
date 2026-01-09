<?php
/**
 * Theme Attach - functions.php
 */
if (!defined('ABSPATH')) {
    exit;
}

// === Constants ===
define('URL', get_stylesheet_directory_uri());
define('IMG', URL . '/assets/img');
define('JS', URL . '/assets/js');
define('CSS', URL . '/assets/css');

// Global
require_once get_stylesheet_directory() . '/inc/helpers.php';
require_once get_stylesheet_directory() . '/inc/enqueue-assets.php';
require_once get_stylesheet_directory() . '/inc/enqueue-fonts.php';

// ACF blocks
require_once get_stylesheet_directory() . '/inc/acf-blocks/index.php';

// Product domain
require_once get_stylesheet_directory() . '/inc/product/assets.php';
require_once get_stylesheet_directory() . '/inc/product/zip-360.php';
require_once get_stylesheet_directory() . '/inc/product/quote-api.php';

// Page domain
require_once get_stylesheet_directory() . '/inc/page/assets.php';
// News Page 
require_once get_stylesheet_directory() . '/inc/new/assets.php';
// Stores Locator
require_once get_stylesheet_directory() . '/inc/stores-locator/assets.php';

// Blog domain
require_once get_stylesheet_directory() . '/inc/blog/assets.php';

// Promotions domain
require_once get_stylesheet_directory() . '/inc/promotions/assets.php';
require_once get_stylesheet_directory() . '/inc/promotions/helpers.php';

// After Sales | Postventa
require_once get_stylesheet_directory() . '/inc/after-sales/assets.php';

// About Us | Nosotros
require_once get_stylesheet_directory() . '/inc/about-us/assets.php';

// Pasaporte de servicios
require_once get_stylesheet_directory() . '/inc/passport/assets.php';

// Utilities
require_once get_stylesheet_directory() . '/inc/enqueue-utilities.php';

//Entity
require_once get_stylesheet_directory() . '/inc/entity/quote.php';