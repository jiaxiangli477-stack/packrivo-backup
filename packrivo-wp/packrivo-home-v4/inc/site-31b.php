<?php
/** 31b English site navigation and topic routes. */
if (!defined('ABSPATH')) exit;

function pk31b_is_english() { return !function_exists('packrivo_lang') || packrivo_lang() === null; }
function pk31b_topic_url($term) { return home_url('/blog/topic/' . $term->slug . '/'); }
function pk31b_topics() { return get_categories(['hide_empty'=>true,'orderby'=>'count','order'=>'DESC']); }

add_action('template_redirect', static function () {
    if (!pk31b_is_english()) return;
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (preg_match('~^/category/uncategorized/?$~', $path)) {
        wp_safe_redirect(home_url('/category/guides/'), 301); exit;
    }
    if (!preg_match('~^/blog/topic/([a-z0-9-]+)/$~', $path, $m)) return;
    $term = get_term_by('slug', $m[1], 'category');
    if (!$term || is_wp_error($term)) return;
    global $wp_query;
    $wp_query->is_404 = false; $wp_query->is_singular = false;
    status_header(200);
    add_filter('wpseo_canonical', static function () use ($term) { return pk31b_topic_url($term); }, 20000);
    add_filter('wpseo_robots', static function () { return 'index, follow'; }, 20000);
    $title=html_entity_decode($term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' | ' . get_bloginfo('name');
    add_filter('wpseo_title', static function () use ($title) { return $title; }, 20000);
    add_filter('pre_get_document_title', static function () use ($title) { return $title; }, 20000);
    get_header();
    echo '<main id="main" class="pk-main"><section class="pk-section"><div class="pk-wrap"><h1 class="pk-h2">' . esc_html(html_entity_decode($term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</h1><ul class="pk-list">';
    $posts = get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'category'=>$term->term_id,'orderby'=>'date','order'=>'DESC']);
    foreach ($posts as $post) echo '<li><a href="' . esc_url(get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a></li>';
    echo '</ul></div></section></main>';
    get_footer(); exit;
}, -10);

function pk31b_language_links() {
    $path=function_exists('packrivo_current_en_path') ? packrivo_current_en_path() : null;
    $path=is_string($path) ? $path : '/';
    $langs=function_exists('packrivo_i18n_ready') ? packrivo_i18n_ready() : [];
    $html=''; foreach($langs as $code=>$meta) $html.='<a href="'.esc_url(home_url('/'.$code.$path)).'" hreflang="'.esc_attr($code).'" lang="'.esc_attr($code).'">'.esc_html($meta['label']).'</a> ';
    return $html;
}

function pk31b_orphan_links($content) {
    if (!pk31b_is_english()) return $content;
    $groups=[
        'jewelry-boxes'=>['drawer-box-with-ribbon','catalog-velvet','catalog-velvet-lined-box-916436'],
        'mailer-boxes'=>['corrugated-wine-shipper','subscription-mailer-with-insert'],
        'foldable-gift-boxes'=>['eco-kraft-cosmetic-box','magnetic-book-style-box','soap-box-with-window'],
        'bags-pouches'=>['jewelry-pouch-and-box-set','kraft-stand-up-pouch','catalog-pouch','catalog-jewelry-pouch-164909','catalog-jewelry-pouch-996892'],
        'inserts'=>['printed-tissue-paper'],
    ];
    foreach($groups as $hub=>$slugs) if(is_page($hub)) {
        $items='';foreach($slugs as $slug){$p=get_page_by_path($slug,OBJECT,'page');if($p && $p->post_status==='publish') $items.='<li><a href="'.esc_url(get_permalink($p)).'">'.esc_html(get_the_title($p)).'</a></li>';}
        if($items) $content.='<section class="pk-wrap pk-related"><h2 class="pk-h2">More in this category</h2><ul class="pk-list">'.$items.'</ul></section>';
    }
    if (is_page('about')) {
        $needle='Our illustrative walkthroughs';
        $content=str_replace($needle,'Our <a href="'.esc_url(home_url('/cases/')).'">illustrative walkthroughs</a>',$content);
    }
    return $content;
}
