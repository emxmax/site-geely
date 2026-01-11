<?php
if (!defined('ABSPATH'))
  exit;

/**
 * Register About Us Blocks
 * 
 * @since 1.0.0
 */
function theme_attach_register_about_us_blocks()
{
  if (!function_exists('acf_register_block_type')) {
    return;
  }

  // About Us Hero
  acf_register_block_type([
    'name' => 'about-us-hero',
    'title' => __('Nosotros - Hero', 'theme-attach'),
    'description' => __('Sección hero principal con mensaje de innovación y tecnología', 'theme-attach'),
    'render_template' => 'template-parts/blocks-about-us/about-us-hero.php',
    'category' => 'layout',
    'icon' => 'awards',
    'keywords' => ['about', 'hero', 'innovation', 'geely'],
    'mode' => 'preview',
    'supports' => [
      'align' => false,
      'mode' => false,
      'jsx' => true,
    ],
  ]);

  // About Us Evolution
  acf_register_block_type([
    'name' => 'about-us-evolution',
    'title' => __('Nosotros - Evolución', 'theme-attach'),
    'description' => __('Línea de tiempo histórica de la evolución de la empresa', 'theme-attach'),
    'render_template' => 'template-parts/blocks-about-us/about-us-evolution.php',
    'category' => 'layout',
    'icon' => 'clock',
    'keywords' => ['about', 'timeline', 'history', 'evolution'],
    'mode' => 'preview',
    'supports' => [
      'align' => false,
      'mode' => false,
      'jsx' => true,
    ],
  ]);

  // About Us Journey
  acf_register_block_type([
    'name' => 'about-us-journey',
    'title' => __('Nosotros - Trayectoria', 'theme-attach'),
    'description' => __('Sección de misión y visión de la empresa', 'theme-attach'),
    'render_template' => 'template-parts/blocks-about-us/about-us-journey.php',
    'category' => 'layout',
    'icon' => 'flag',
    'keywords' => ['about', 'mission', 'vision', 'journey'],
    'mode' => 'preview',
    'supports' => [
      'align' => false,
      'mode' => false,
      'jsx' => true,
    ],
  ]);

  // About Us Values
  acf_register_block_type([
    'name' => 'about-us-values',
    'title' => __('Nosotros - Valores', 'theme-attach'),
    'description' => __('Grid de valores corporativos con 4 columnas', 'theme-attach'),
    'render_template' => 'template-parts/blocks-about-us/about-us-values.php',
    'category' => 'layout',
    'icon' => 'star-filled',
    'keywords' => ['about', 'values', 'corporate'],
    'mode' => 'preview',
    'supports' => [
      'align' => false,
      'mode' => false,
      'jsx' => true,
    ],
  ]);

  // About Us Social Responsibility
  acf_register_block_type([
    'name' => 'about-us-social',
    'title' => __('Nosotros - Responsabilidad Social', 'theme-attach'),
    'description' => __('Sección de responsabilidad social corporativa y Geely Hope', 'theme-attach'),
    'render_template' => 'template-parts/blocks-about-us/about-us-social.php',
    'category' => 'layout',
    'icon' => 'heart',
    'keywords' => ['about', 'social', 'responsibility', 'hope'],
    'mode' => 'preview',
    'supports' => [
      'align' => false,
      'mode' => false,
      'jsx' => true,
    ],
  ]);

  
  // 
  acf_register_block_type([
    'name' => 'about-us-social-impact',
    'title' => __('Nosotros - Bienestar Social', 'theme-attach'),
    'description' => __('Sección de bienestar social', 'theme-attach'),
    'render_template' => 'template-parts/blocks-about-us/about-us-social-impact.php',
    'category' => 'layout',
    'icon' => 'heart',
    'keywords' => [
      'about',
      'social',
      'responsibility',
      'hope'
    ],
    'mode' => 'preview',
    'supports' => [
      'align' => false,
      'mode' => false,
      'jsx' => true,
    ],
  ]);


  // About Us Tech
  acf_register_block_type([
    'name' => 'about-us-tech',
    'title' => __('Nosotros - Tecnología', 'theme-attach'),
    'description' => __('Sección de tecnología e innovación TEC', 'theme-attach'),
    'render_template' => 'template-parts/blocks-about-us/about-us-tech.php',
    'category' => 'layout',
    'icon' => 'laptop',
    'keywords' => ['about', 'technology', 'tec', 'innovation'],
    'mode' => 'preview',
    'supports' => [
      'align' => false,
      'mode' => false,
      'jsx' => true,
    ],
  ]);
}

add_action('acf/init', 'theme_attach_register_about_us_blocks');
