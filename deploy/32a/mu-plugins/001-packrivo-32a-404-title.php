<?php
/** The locale router exits before Yoast runs for untranslated paths. */
if (!defined('ABSPATH')) exit;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($path) && preg_match('~^/(?:de|fr|es|it|nl|pt|ar|ja|ko|zh-CN|zh-TW)/~', $path)) {
    ob_start(static function ($html) {
        if (http_response_code() !== 404) return $html;
        return str_replace('<title>404 — page not translated</title>', '<title>Page not found | Packrivo</title>', $html);
    });
}
