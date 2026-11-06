<?php
/**
 * Child theme bootstrap: assets, menus, small helpers.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PKV4_VERSION', '4.0.7');

require_once get_stylesheet_directory() . '/inc/seo-31a.php';
require_once get_stylesheet_directory() . '/inc/site-31b.php';

// 29y：/get-a-quote/ 的 H1 / title / meta description（11 语种走词典）。
require_once get_stylesheet_directory() . '/inc/quote-page.php';

// 29y：列表页 ItemList / 可见 FAQ 的 FAQPage（第 4 项结构化数据）。
require_once get_stylesheet_directory() . '/inc/structured-data.php';

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
        // List pages carry no engine style in the link, so ship the map once and
        // let the CTA script send configured products to the builder.
        'styles' => pkv4_style_map(),
    ];
    wp_add_inline_script('pkv4-quote-targets', 'window.pkv4QuoteTargets=' . wp_json_encode($context) . ';', 'before');
}

/** slug => engine style for every page that has one. Cached; slugs, not IDs. */
function pkv4_style_map()
{
    $cached = get_transient('pkv4_style_map');
    if (is_array($cached)) return $cached;
    $map = [];
    $ids = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_key' => '_packrivo_engine_style',
    ]);
    foreach ($ids as $id) {
        $style = pk_engine_style_for($id);
        if (!$style) continue;
        $map[(string) get_post_field('post_name', $id)] = $style;
    }
    set_transient('pkv4_style_map', $map, HOUR_IN_SECONDS);
    return $map;
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
    $value = is_string($value) && $value !== '' ? html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $title;
    // 29x：博客页文档标题在翻译页按 title 桶翻译（无键时原样，由插件缓冲兜底）。
    return pkv4_i18n_t($value, array('title'));
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

function pk_from_price($post_id, $quantity = 500)
{
    if (!pk_pricing_tables_ready()) return null;
    $style = pk_engine_style_for($post_id);
    $reference = pk_card_reference($style);
    if (!$style || !$reference) return null;
    $raw = file_get_contents(PKQ_DIR . 'includes/pricing.json');
    $settings = PKQ_Plugin::settings();
    $input = ['style'=>$style,'material'=>$reference[3],'print'=>'full-color','finish'=>'matte','quantity'=>(int)$quantity,'length'=>$reference[0],'width'=>$reference[1],'height'=>$reference[2],'rush'=>false];
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

/** The journal's numbered pages have distinct canonical URLs. */
add_filter('wpseo_canonical', function ($canonical) {
    return is_home() ? pkv4_31a_journal_canonical() : $canonical;
}, 9999);
add_action('wp_head', function () {
    if (is_home() && !defined('WPSEO_VERSION')) {
        $url=pkv4_31a_journal_canonical();
        echo '<link rel="canonical" href="'.esc_url($url).'" />' . "\n";
    }
}, 5);

/* ------------------------------------------------------------------ *
 * 29x i18n：博客页动态串（文档标题/日期/min read/空态）跟着翻译词典走。
 * mu 插件 packrivo-i18n 的输出缓冲只做整串精确替换；带数字的动态串
 * （"Journal · 333 articles"、"7 min read"、日期）匹配不到，由主题在这里
 * 查同一份词典（uploads/packrivo-i18n/{lang}-dict.json）回填。
 * 词典键约定：
 *   title 桶：博客页文档标题整串（'Packaging Sourcing Blog for Small Brands | Packrivo'）
 *   text  桶：'{N} min read'、空态 'No articles in this topic yet.'、Jan–Dec 12 个月名
 * 无键时逐级回退：{N} 数字归一 → 模板内语言表 → 英文原串。
 * ------------------------------------------------------------------ */

/** 查词典：只在翻译页（PACKRIVO_LANG 已定义）查；命中返回字符串，否则 null。 */
function pkv4_i18n_lookup($text, $kinds = array('text', 'block'))
{
    if (!function_exists('packrivo_lang') || !function_exists('packrivo_i18n_lookup')) return null;
    if (packrivo_lang() === null) return null;
    $hit = packrivo_i18n_lookup((string) $text, (array) $kinds);
    return (is_string($hit) && $hit !== '') ? $hit : null;
}

/**
 * 精确 + {N} 数字归一查找：
 * 先按原串查；查不到就把数字归一成 {N} 再查（词典里有 '{N} min read' 这类模板键），
 * 命中后用原串里的数字按序回填。仍无键返回原串。
 */
function pkv4_i18n_t($text, $kinds = array('text', 'block'))
{
    $text = (string) $text;
    $hit = pkv4_i18n_lookup($text, $kinds);
    if ($hit !== null) return $hit;
    $pattern = preg_replace('/\d+/u', '{N}', $text);
    if (is_string($pattern) && $pattern !== $text) {
        $tpl = pkv4_i18n_lookup($pattern, $kinds);
        if ($tpl !== null) {
            preg_match_all('/\d+/u', $text, $m);
            $nums = $m[0];
            $i = 0;
            return preg_replace_callback('/\{N\}/u', function () use (&$i, $nums) {
                return $nums[$i++] ?? '';
            }, $tpl);
        }
    }
    return $text;
}

/**
 * 日期：整串（'Oct 12, 2025'）先查词典；查不到就按词典里的月名键替换英文月份，
 * 版式沿用语言表 $pattern（如 de 的 '{d}. {M} {Y}'）；词典与语言表都没有则回英文。
 */
function pkv4_i18n_date($ts, $pattern = null, $months = null)
{
    $ts = (int) $ts;
    $en = wp_date('M j, Y', $ts);
    $hit = pkv4_i18n_lookup($en, array('text', 'block'));
    if ($hit !== null) return $hit;
    $m = wp_date('M', $ts);
    $tr = pkv4_i18n_lookup($m, array('text', 'block'));
    if ($tr !== null && $tr !== $m) {
        $tokens = array('{d}' => (string) (int) wp_date('j', $ts), '{M}' => $tr, '{Y}' => wp_date('Y', $ts));
        if (is_string($pattern) && $pattern !== '') return strtr($pattern, $tokens);
        return str_replace($m, $tr, $en);
    }
    if (is_array($months) && is_string($pattern) && $pattern !== '') {
        $month = $months[(int) wp_date('n', $ts) - 1] ?? null;
        if (is_string($month) && $month !== '') {
            return strtr($pattern, array(
                '{d}' => (string) (int) wp_date('j', $ts),
                '{M}' => $month,
                '{Y}' => wp_date('Y', $ts),
            ));
        }
    }
    return $en;
}
