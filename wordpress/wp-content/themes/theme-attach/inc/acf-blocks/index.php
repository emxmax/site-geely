<?php
if ( ! defined('ABSPATH') ) exit;

// Registros separados
require_once __DIR__ . '/product-blocks.php';
require_once __DIR__ . '/page-blocks.php';

// Hooks de ACF
add_action('acf/init', 'theme_attach_register_product_blocks');
add_action('acf/init', 'theme_attach_register_page_blocks');
