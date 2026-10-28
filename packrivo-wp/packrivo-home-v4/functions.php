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
    wp_enqueue_script('pkv4-quote-targets', get_stylesheet_directory_uri() . '/assets/cta-28l.js', [], PKV4_VERSION, true);
    $post_id = is_singular('page') ? get_queried_object_id() : 0;
    $slug = $post_id ? (string) get_post_field('post_name', $post_id) : '';
    // Nine existing product pages use legacy slugs without the product- prefix.
    $is_product = strpos($slug, 'product-') === 0
        || ($post_id && get_post_meta($post_id, '_packrivo_remediation_product', true) !== '');
    $context = [
        'base' => home_url('/'),
        'product' => $is_product ? $slug : '',
        'style' => $is_product ? (pk_engine_style_for($post_id) ?: '') : '',
    ];
    wp_add_inline_script('pkv4-quote-targets', 'window.pkv4QuoteTargets=' . wp_json_encode($context) . ';', 'before');
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

/** Metadata-only projection for the eight approved hub pages; never changes H1/body. */
function pkv4_hub_metadata_id()
{
    if (is_front_page()) return (int) get_option('page_on_front');
    if (is_home()) return (int) get_option('page_for_posts');
    if (!is_singular('page')) return 0;
    $id = get_queried_object_id();
    return in_array(get_page_uri($id), ['products','jewelry-boxes','mailer-boxes','samples','blog','about','inquiry'], true) ? $id : 0;
}

function pkv4_hub_document_title($title)
{
    $id = pkv4_hub_metadata_id();
    $value = $id ? get_post_meta($id, '_packrivo_document_title', true) : '';
    return is_string($value) && $value !== '' ? html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $title;
}
add_filter('pre_get_document_title', 'pkv4_hub_document_title', 1100);
add_filter('wpseo_title', 'pkv4_hub_document_title', 1100);
add_filter('wpseo_metadesc', function ($description) {
    $id = pkv4_hub_metadata_id();
    $value = $id ? get_post_meta($id, '_packrivo_description', true) : '';
    return is_string($value) && $value !== '' ? $value : $description;
}, 1100);
add_action('wp_head', function () {
    $id = pkv4_hub_metadata_id();
    if (!$id || defined('WPSEO_VERSION')) return;
    $parent_owns_description = is_singular('page')
        && get_post_meta($id, '_packrivo_native_page', true) === '1'
        && has_action('wp_head', 'packrivo_output_page_metadata') !== false;
    if ($parent_owns_description) return;
    $value = get_post_meta($id, '_packrivo_description', true);
    if (is_string($value) && $value !== '') echo '<meta name="description" content="' . esc_attr($value) . '" />' . "\n";
}, 3);

// Keep one title owner after the parent and product workspace finish routing.
add_action('wp', function () {
    $parent_renders_title = is_singular('page')
        && get_post_meta(get_queried_object_id(), '_packrivo_native_page', true) === '1'
        && !defined('WPSEO_VERSION')
        && has_action('wp_head', 'packrivo_output_page_metadata') !== false;
    if ($parent_renders_title) {
        remove_action('wp_head', '_wp_render_title_tag', 1);
        return;
    }
    // The workspace may remove both renderers on a directory or product page.
    if (is_singular('page') && !defined('WPSEO_VERSION') && current_theme_supports('title-tag') && has_action('wp_head', '_wp_render_title_tag') === false && has_action('wp_head', ['PKPW_Product_Renderer', 'render_title']) === false) {
        add_action('wp_head', '_wp_render_title_tag', 1);
    }
}, 99);

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
    $explicit = [
        'product-jewelry-box-with-insert'=>'rigid', 'product-foldable-lid-base-box'=>'rigid',
        'product-finished-folding-gift-box'=>'carton', 'product-folding-gift-box'=>'carton',
        'product-tuck-end-gift-box'=>'carton', 'product-pillow-jewelry-box'=>'carton',
        'product-mailer-with-insert'=>'mailer', 'product-lightweight-mailer'=>'mailer',
    ];
    if (isset($explicit[$slug])) return isset($settings['styles'][$explicit[$slug]]) ? $explicit[$slug] : null;
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

/**
 * Keep the configurator on the same reference spec the cards quote.
 * Without this the card says "from $2.83" and the builder opens at its own
 * 30x22x12 default, which prices the same box at $7.45.
 */
function pk_quote_reference_spec($spec, $product, $style)
{
    if (!is_array($spec)) $spec = [];
    if ($product === '') return $spec;
    if ($style === '') {
        $page = get_page_by_path($product);
        if (!$page) return $spec;
        $style = (string) pk_engine_style_for($page->ID);
    }
    $reference = pk_card_reference($style);
    if (!$reference) return $spec;
    return [
        'length' => $reference[0],
        'width' => $reference[1],
        'height' => $reference[2],
        'material' => $reference[3],
        'print' => 'full-color',
        'finish' => 'matte',
    ];
}
add_filter('pkq_reference_spec', 'pk_quote_reference_spec', 10, 3);

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

/** Search-filter URLs should never be indexed as separate pages. */
add_filter('wp_robots', function ($robots) {
    if (isset($_GET['q'])) { $robots['noindex'] = true; unset($robots['index']); $robots['follow'] = true; }
    return $robots;
}, 100);
add_filter('wpseo_robots', function ($robots) {
    return isset($_GET['q']) ? 'noindex, follow' : $robots;
}, 100);

/* Journal archive owns the posts-page template when the child theme is active. */
add_filter('template_include', function ($template) {
    if (is_home()) { $candidate = get_stylesheet_directory() . '/template-journal-v4.php'; if (is_readable($candidate)) return $candidate; }
    return $template;
}, 99);

function pk_related_structures($post_id)
{
    $groups = [
      'jewelry'=>['jewelry','ring','watch','necklace'], 'gift'=>['gift','folding','carton','rigid','magnetic'],
      'mailer'=>['mailer','corrugated','shipping','tray'], 'bag'=>['bag','pouch','tote'],
      'label'=>['label','sticker'], 'insert'=>['insert','inlay','divider','pulp'],
    ];
    $terms=wp_get_post_terms($post_id,['category','post_tag'],['fields'=>'names']);
    if (is_wp_error($terms)) $terms=[];
    $text=strtolower(get_the_title($post_id).' '.implode(' ',$terms)); $selected='jewelry';
    foreach ($groups as $group=>$words) {
        foreach ($words as $word) if (preg_match('~\b'.preg_quote($word,'~').'(?:s|es)?\b~i',$text)) { $selected=$group; break 2; }
    }
    $ids=get_posts(['post_type'=>'page','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_packrivo_remediation_product','orderby'=>'title','order'=>'ASC']);
    $out=[];
    foreach($ids as $id) {
        $slug=get_post_field('post_name',$id); $hit=false;
        foreach($groups[$selected] as $word) if(preg_match('~\b'.preg_quote($word,'~').'(?:s|es)?\b~i',$slug)) { $hit=true; break; }
        if($hit && pk_from_price($id)!==null) $out[]=$id;
        if(count($out)===3) break;
    }
    return ['group'=>$selected,'ids'=>$out];
}

/** Parameterised journal views share the unfiltered archive canonical. */
add_filter('wpseo_canonical', function ($canonical) {
    return is_home() ? get_permalink((int)get_option('page_for_posts')) : $canonical;
}, 9999);
add_action('wp_head', function () {
    if (is_home() && !defined('WPSEO_VERSION')) {
        $url=get_permalink((int)get_option('page_for_posts')) ?: home_url('/blog/');
        echo '<link rel="canonical" href="'.esc_url($url).'" />' . "\n";
    }
}, 5);
