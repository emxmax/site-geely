<?php
if (!defined('ABSPATH'))
  exit;

// Registros separados
require_once __DIR__ . '/product-blocks.php';
require_once __DIR__ . '/blog-blocks.php';
require_once __DIR__ . '/page-blocks.php';
require_once __DIR__ . '/new-blocks.php';
require_once __DIR__ . '/promotions-blocks.php';
require_once __DIR__ . '/stores-locator-blocks.php';
require_once __DIR__ . '/after-sales-blocks.php';
require_once __DIR__ . '/about-us-blocks.php';

// Hooks de ACF
add_action('acf/init', 'theme_attach_register_blog_blocks');
add_action('acf/init', 'theme_attach_register_product_blocks');
add_action('acf/init', 'theme_attach_register_page_blocks');
add_action('acf/init', 'theme_attach_register_page_new_blocks');
add_action('acf/init', 'theme_attach_register_promotions_blocks');
add_action(
  'acf/init',
  'theme_attach_register_page_stores_locator_blocks'
);
add_action(
  'acf/init',
  'theme_attach_register_after_sales_blocks'
);