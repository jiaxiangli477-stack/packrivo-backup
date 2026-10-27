<?php
/**
 * Child theme bootstrap: assets, menus, small helpers.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PKV4_VERSION', '4.0.0');

function pkv4_assets()
{
    wp_enqueue_style('pkv4-fonts', 'https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Inter+Tight:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap', [], null);
    wp_enqueue_style('pkv4-site', get_stylesheet_directory_uri() . '/assets/site.css', ['pkv4-fonts'], PKV4_VERSION);
    wp_enqueue_script('pkv4-nav', get_stylesheet_directory_uri() . '/assets/nav.js', [], PKV4_VERSION, true);
}
// Priority 20: the child functions.php loads before the parent, so the default 10
// put our stylesheet *before* the parent's and lost every equal-specificity fight.
add_action('wp_enqueue_scripts', 'pkv4_assets', 20);

function pkv4_menus()
{
    register_nav_menus([
        'primary_v4' => __('Primary (v4)', 'packrivo-home-v4'),
    ]);

    // Our templates print no <title> of their own; without this support WordPress
    // emits nothing and every page keeps the parent's stale document title.
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'pkv4_menus');

/** Fallback menu when no menu is assigned yet — mirrors the live IA. */
function pkv4_fallback_menu()
{
    $items = [
        ['Products', '/products/'],
        ['Samples', '/samples/'],
        ['Customization', '/customization/'],
        ['Services', '/services/'],
        ['Blog', '/blog/'],
        ['Resources', '/resources/'],
        ['About', '/about/'],
    ];
    echo '<ul class="pk-nav-list">';
    foreach ($items as [$label, $href]) {
        printf('<li><a href="%s">%s</a></li>', esc_url(home_url($href)), esc_html($label));
    }
    echo '</ul>';
}

/** Product groups for the mega panel. Filterable so the client can curate it. */
function pkv4_product_groups()
{
    $groups = [
        [
            'title' => 'Boxes',
            'links' => [
                ['Jewelry boxes', '/jewelry-boxes/'],
                ['Foldable gift boxes', '/foldable-gift-boxes/'],
                ['Mailer boxes', '/mailer-boxes/'],
                ['Rigid gift boxes', '/rigid-gift-boxes/'],
            ],
        ],
        [
            'title' => 'Print & finishing',
            'links' => [
                ['Labels', '/labels/'],
                ['Inserts', '/inserts/'],
                ['Printing & finishes', '/customization/'],
            ],
        ],
        [
            'title' => 'Start here',
            'links' => [
                ['All packaging products', '/products/'],
                ['Order a sample', '/samples/'],
                ['Get a quote', '/get-a-quote/'],
            ],
        ],
    ];
    return apply_filters('pkv4_product_groups', $groups);
}
