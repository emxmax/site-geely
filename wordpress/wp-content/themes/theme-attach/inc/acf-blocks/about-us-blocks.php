<?php
if (!defined('ABSPATH')) exit;

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
        'name'            => 'about-us-hero',
        'title'           => __('About Us - Hero', 'theme-attach'),
        'description'     => __('Hero section with innovation and technology header', 'theme-attach'),
        'render_template' => 'template-parts/blocks-about-us/about-us-hero.php',
        'category'        => 'layout',
        'icon'            => 'awards',
        'keywords'        => ['about', 'hero', 'innovation', 'geely'],
        'mode'            => 'preview',
        'supports'        => [
            'align' => false,
            'mode'  => false,
            'jsx'   => true,
        ],
    ]);

    // About Us Evolution
    acf_register_block_type([
        'name'            => 'about-us-evolution',
        'title'           => __('About Us - Evolution', 'theme-attach'),
        'description'     => __('Historical timeline of company evolution', 'theme-attach'),
        'render_template' => 'template-parts/blocks-about-us/about-us-evolution.php',
        'category'        => 'layout',
        'icon'            => 'clock',
        'keywords'        => ['about', 'timeline', 'history', 'evolution'],
        'mode'            => 'preview',
        'supports'        => [
            'align' => false,
            'mode'  => false,
            'jsx'   => true,
        ],
    ]);

    // About Us Journey
    acf_register_block_type([
        'name'            => 'about-us-journey',
        'title'           => __('About Us - Journey', 'theme-attach'),
        'description'     => __('Mission and vision section', 'theme-attach'),
        'render_template' => 'template-parts/blocks-about-us/about-us-journey.php',
        'category'        => 'layout',
        'icon'            => 'flag',
        'keywords'        => ['about', 'mission', 'vision', 'journey'],
        'mode'            => 'preview',
        'supports'        => [
            'align' => false,
            'mode'  => false,
            'jsx'   => true,
        ],
    ]);

    // About Us Values
    acf_register_block_type([
        'name'            => 'about-us-values',
        'title'           => __('About Us - Values', 'theme-attach'),
        'description'     => __('Corporate values grid with 4 columns', 'theme-attach'),
        'render_template' => 'template-parts/blocks-about-us/about-us-values.php',
        'category'        => 'layout',
        'icon'            => 'star-filled',
        'keywords'        => ['about', 'values', 'corporate'],
        'mode'            => 'preview',
        'supports'        => [
            'align' => false,
            'mode'  => false,
            'jsx'   => true,
        ],
    ]);

    // About Us Social Responsibility
    acf_register_block_type([
        'name'            => 'about-us-social',
        'title'           => __('About Us - Social Responsibility', 'theme-attach'),
        'description'     => __('Social responsibility and Geely Hope section', 'theme-attach'),
        'render_template' => 'template-parts/blocks-about-us/about-us-social.php',
        'category'        => 'layout',
        'icon'            => 'heart',
        'keywords'        => ['about', 'social', 'responsibility', 'hope'],
        'mode'            => 'preview',
        'supports'        => [
            'align' => false,
            'mode'  => false,
            'jsx'   => true,
        ],
    ]);

    // About Us Tech
    acf_register_block_type([
        'name'            => 'about-us-tech',
        'title'           => __('About Us - Technology', 'theme-attach'),
        'description'     => __('Technology TEC section', 'theme-attach'),
        'render_template' => 'template-parts/blocks-about-us/about-us-tech.php',
        'category'        => 'layout',
        'icon'            => 'laptop',
        'keywords'        => ['about', 'technology', 'tec', 'innovation'],
        'mode'            => 'preview',
        'supports'        => [
            'align' => false,
            'mode'  => false,
            'jsx'   => true,
        ],
    ]);
}

add_action('acf/init', 'theme_attach_register_about_us_blocks');
