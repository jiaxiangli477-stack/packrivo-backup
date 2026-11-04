<?php
/** English routing and catalogue URL normalization. */
if (!defined('ABSPATH')) exit;

function pkv4_31a_catalog_url($url) {
    if (function_exists('packrivo_lang') && packrivo_lang() !== null) return $url;
    $parts = wp_parse_url($url);
    if (!is_array($parts)) return $url;
    $host = strtolower($parts['host'] ?? '');
    $local = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    if ($host !== '' && !in_array($host, [$local, 'www.packrivo.com', 'packrivo.com'], true)) return $url;
    $path = $parts['path'] ?? '';
    if (!preg_match('~(?:^|/)catalog-[^/]+/?$~i', $path)) return $url;
    $lower = preg_replace_callback('~(^|/)catalog-[^/]+~i', static function ($m) { return strtolower($m[0]); }, $path);
    $pos = strpos($url, $path);
    return $pos === false ? $url : substr_replace($url, $lower, $pos, strlen($path));
}

function pkv4_31a_catalog_links($html) {
    $result = preg_replace_callback('~(<a\b[^>]*?\shref\s*=\s*["\'])([^"\']+)(["\'])~i', static function ($m) {
        return $m[1] . pkv4_31a_catalog_url($m[2]) . $m[3];
    }, $html);
    return $result === null ? $html : $result;
}
add_filter('the_content', 'pkv4_31a_catalog_links', 20);
add_filter('page_link', 'pkv4_31a_catalog_url', 20);

/** Link only existing, semantically matching English card text. */
function pkv4_31a_existing_orphan_links($html) {
    if (function_exists('packrivo_lang') && packrivo_lang() !== null) return $html;
    $rules = [
        'foldable-gift-boxes' => ['A paperboard direction for candles and small retail gifts.', 'candles', '/candle-gift-set-box/'],
        'inserts' => ['A printed layer for instructions, care or a thank-you message.', 'thank-you message', '/paper-insert-card/'],
    ];
    foreach ($rules as $page => $rule) {
        if (!is_page($page)) continue;
        [$sentence, $words, $path] = $rule;
        $linked = str_replace($words, '<a href="' . esc_url(home_url($path)) . '">' . $words . '</a>', $sentence);
        $original = '<p>' . $sentence . '</p>';
        $pos = strpos($html, $original);
        if ($pos !== false) $html = substr_replace($html, '<p>' . $linked . '</p>', $pos, strlen($original));
    }
    return $html;
}

function pkv4_31a_journal_canonical() {
    $url = get_permalink((int) get_option('page_for_posts')) ?: home_url('/blog/');
    $lang = function_exists('packrivo_lang') ? packrivo_lang() : null;
    $pg = isset($_GET['pg']) && is_scalar($_GET['pg']) ? (string) wp_unslash($_GET['pg']) : '';
    if ($lang === null && preg_match('/^[0-9]+$/', $pg) && (int) $pg > 1) return add_query_arg('pg', (int) $pg, $url);
    return $url;
}

/** The journal template uses ?pg and a 12-post WP_Query, not WP's main paged query. */
function pkv4_31a_journal_adjacent_urls() {
    static $urls = null;
    if ($urls !== null) return $urls;
    $page = max(1, (int) get_query_var('paged'), isset($_GET['pg']) && is_scalar($_GET['pg']) ? (int) $_GET['pg'] : 1);
    $args = [
        'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 12,
        'paged' => 1, 'fields' => 'ids', 'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false, 'update_post_term_cache' => false,
    ];
    $topic = isset($_GET['topic']) && is_scalar($_GET['topic']) ? sanitize_title(wp_unslash($_GET['topic'])) : '';
    if ($topic) $args['category_name'] = $topic;
    $query = new WP_Query($args);
    $base = get_permalink((int) get_option('page_for_posts')) ?: home_url('/blog/');
    if ($topic) $base = add_query_arg('topic', $topic, $base);
    $urls = [
        'prev' => $page > 1 ? ($page === 2 ? $base : add_query_arg('pg', $page - 1, $base)) : '',
        'next' => $page < (int) $query->max_num_pages ? add_query_arg('pg', $page + 1, $base) : '',
    ];
    return $urls;
}

function pkv4_31a_is_english_journal() {
    return is_home() && (!function_exists('packrivo_lang') || packrivo_lang() === null);
}

// Yoast only invokes its rel-link filters when its own adjacent URL is nonempty.
// Seed a missing URL so its presenters can render the custom ?pg pagination.
add_filter('wpseo_adjacent_rel_url', static function ($url, $rel) {
    if (!pkv4_31a_is_english_journal() || !in_array($rel, ['next', 'prev'], true)) return $url;
    $target = pkv4_31a_journal_adjacent_urls()[$rel];
    return $target === '' ? '' : ($url !== '' ? $url : $target);
}, 10, 2);

foreach (['next', 'prev'] as $rel) {
    add_filter('wpseo_' . $rel . '_rel_link', static function ($output) use ($rel) {
        if (!pkv4_31a_is_english_journal()) return $output;
        $target = pkv4_31a_journal_adjacent_urls()[$rel];
        return $target === '' ? '' : '<link rel="' . $rel . '" href="' . esc_url($target) . '" />';
    });
}

add_action('template_redirect', static function () {
    if (function_exists('packrivo_lang') && packrivo_lang() !== null) return;
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $pieces = explode('?', $uri, 2);
    $path = $pieces[0];
    if (!is_string($path) || $path === '' || $path[0] !== '/') return;
    if (preg_match('~^/(?:de|fr|es|it|nl|pt|ar|ja|ko|zh-cn|zh-tw)(?:/|$)~i', $path)) return;
    if (preg_match('~^/(?:wp-admin|wp-json|wp-content)(?:/|$)~i', $path)) return;
    if (preg_match('~\.[^/]+$~', $path)) return;
    if (preg_match('/[A-Z]/', $path)) {
        $lower = strtolower($path);
        if (substr($lower, -1) !== '/' && (is_singular() || is_home())) $lower = user_trailingslashit($lower);
        wp_safe_redirect(home_url($lower) . (isset($pieces[1]) ? '?' . $pieces[1] : ''), 301);
        exit;
    }
    if (preg_match('~^/collapsible-magnetic-gift-box/?$~', $path)) {
        wp_safe_redirect(home_url('/product-collapsible-magnetic-gift-box/') . (isset($pieces[1]) ? '?' . $pieces[1] : ''), 301);
        exit;
    }
    if (preg_match('~^/blog/page/([1-9][0-9]*)/?$~', $path, $m)) {
        $query = isset($pieces[1]) && $pieces[1] !== '' ? explode('&', $pieces[1]) : [];
        $query = array_values(array_filter($query, static function ($part) { return !preg_match('/^pg(?:=|$)/', $part); }));
        array_unshift($query, 'pg=' . (int) $m[1]);
        wp_safe_redirect(home_url('/blog/') . '?' . implode('&', $query), 301);
        exit;
    }
}, -1);

add_filter('wpseo_exclude_from_sitemap_by_post_ids', static function ($ids) {
    $page = get_page_by_path('collapsible-magnetic-gift-box', OBJECT, 'page');
    if ($page) $ids[] = (int) $page->ID;
    return array_values(array_unique($ids));
});
