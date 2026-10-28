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

/** Engine styles are structural approximations only; unsupported products have no price. */
function pk_engine_style_for($post_id)
{
    if (!class_exists('PKQ_Plugin')) return null;
    $settings = PKQ_Plugin::settings();
    $slug = strtolower((string) get_post_field('post_name', $post_id));
    if (preg_match('/(?:velvet|fabric|textile|wooden|leather|polyurethane|foam|metal|pouch)/', $slug)) return null;
    $meta = get_post_meta($post_id, '_packrivo_engine_style', true);
    if ($meta !== '') return isset($settings['styles'][$meta]) ? $meta : null;
    $rules = [
        'rigid' => '/(?:rigid-(?:box|gift|presentation)|magnetic-(?:jewelry|gift)-box|drawer-jewelry-box|lid-and-base-jewelry-box)/',
        'mailer' => '/(?:mailer-box|printed-mailer|front-tuck-mailer|corrugated-roll-end-hinged-lid)/',
        'shipping' => '/(?:regular-slotted-shipping-carton|auto-bottom-slotted-outer-carton|full-overlap-slotted-outer-carton)/',
        'carton' => '/(?:folding-carton|reverse-tuck-retail-carton|straight-tuck-retail-carton)/',
        'bag' => '/(?:paper-gift-bag|paper-carrier|paper-mailer-bag)/',
        'sticker' => '/(?:sheet-labels)/',
        'poly' => '/(?:poly-mailer-bag)/',
    ];
    foreach ($rules as $style => $pattern) {
        if (preg_match($pattern, $slug) && isset($settings['styles'][$style])) return $style;
    }
    return null;
}

/** Dimensions are the specified 500-piece reference, not product measurements. */
function pk_card_reference($style)
{
    $rows = [
        'rigid'=>[9.5,9.5,4,'rigidboard'], 'mailer'=>[30,22,12,'kraft'],
        'shipping'=>[30,25,20,'kraft'], 'poly'=>[25,1,35,'kraftpoly'],
        'carton'=>[12,4,16,'white'], 'pouch'=>[16,6,23,'kraftpoly'],
        'bag'=>[18,8,22,'kraft'], 'sticker'=>[8,8,0.1,'white'],
    ];
    return $rows[$style] ?? null;
}

function pk_pricing_tables_ready()
{
    if (!class_exists('PKQ_Plugin') || !class_exists('PKQ_Pricing') || !defined('PKQ_DIR')) return false;
    $file = PKQ_DIR . 'includes/pricing.json';
    if (!is_readable($file)) return false;
    $raw = file_get_contents($file);
    $tables = json_decode($raw, true);
    if (!is_array($tables) || empty($tables['materials']) || empty($tables['prints']) || empty($tables['finishes']) || !isset($tables['tiers']) || !is_array($tables['tiers']) || count($tables['tiers']) < 2) return false;
    foreach (['materials'=>'factor','prints'=>'add','finishes'=>'add'] as $group=>$field) {
        if (!is_array($tables[$group])) return false;
        foreach ($tables[$group] as $row) {
            if (!is_array($row) || !isset($row[$field]) || !is_numeric($row[$field]) || !is_finite((float) $row[$field])) return false;
        }
    }
    return true;
}

function pk_from_price($post_id)
{
    if (!pk_pricing_tables_ready()) return null;
    $style = pk_engine_style_for($post_id);
    $reference = pk_card_reference($style);
    if (!$style || !$reference) return null;
    $raw = file_get_contents(PKQ_DIR . 'includes/pricing.json');
    $settings = PKQ_Plugin::settings();
    $input = ['style'=>$style,'material'=>$reference[3],'print'=>'full-color','finish'=>'matte','quantity'=>500,'length'=>$reference[0],'width'=>$reference[1],'height'=>$reference[2],'rush'=>false];
    $key = 'pk_card_' . md5($raw . wp_json_encode([$input,$settings]));
    $cached = get_transient($key);
    if ($cached !== false) return (float) $cached;
    $result = PKQ_Pricing::price($input, $settings);
    if (is_wp_error($result) || !isset($result['unit']) || !is_numeric($result['unit'])) return null;
    set_transient($key, (float) $result['unit'], 12 * HOUR_IN_SECONDS);
    return (float) $result['unit'];
}
