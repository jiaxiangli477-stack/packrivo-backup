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
        'jewelry-boxes' => ['Plan a custom drawer jewelry box around the complete jewelry set. Choose the tray layout, usable interior and opening detail, then compare supplier proposals against the same requirements.', 'drawer jewelry box', '/drawer-box-with-ribbon/'],
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
        wp_safe_redirect(home_url(strtolower($path)) . (isset($pieces[1]) ? '?' . $pieces[1] : ''), 301);
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
