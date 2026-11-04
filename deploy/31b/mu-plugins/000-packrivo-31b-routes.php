<?php
/** Normalize locale routes before the i18n router rewrites REQUEST_URI. */
if (!defined('ABSPATH')) exit;
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$parts = explode('?', $uri, 2);
$path = $parts[0];
// The existing multilingual privacy page is noindex. Keep it out of locale
// sitemaps without changing the English page or privacy robots policy.
if (preg_match('~^/(?:de|fr|es|it|nl|pt|ar|ja|ko|zh-CN|zh-TW)-sitemap\.xml$~', $path)) {
    add_filter('wpseo_sitemap_entry', static function ($entry) {
        if (is_array($entry) && isset($entry['loc']) && parse_url($entry['loc'], PHP_URL_PATH) === '/privacy/') return false;
        return $entry;
    }, 100);
}
if (preg_match('~^/(?:de|fr|es|it|nl|pt|ar|ja|ko|zh-CN|zh-TW)(?:/|$)~', $path)
    && !preg_match('~/(?:wp-admin|wp-json)(?:/|$)~i', $path)
    && !preg_match('~\.[^/]+$~', $path)
    && substr($path, -1) !== '/') {
    header('Location: ' . $path . '/' . (isset($parts[1]) ? '?' . $parts[1] : ''), true, 301);
    exit;
}
