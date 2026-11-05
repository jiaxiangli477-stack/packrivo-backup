<?php
/** The locale router exits before Yoast runs for untranslated paths. */
if (!defined('ABSPATH')) exit;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($path) && preg_match('~^/(de|fr|es|it|nl|pt|ar|ja|ko|zh-CN|zh-TW)/~', $path, $match)) {
    $language = $match[1];
    ob_start(static function ($html) use ($language) {
        if (http_response_code() !== 404) return $html;
        $old = '<title>404 — page not translated</title>';
        if (strpos($html, $old) === false) return $html;
        $title = 'Page not found | Packrivo';
        $uploads = wp_get_upload_dir();
        $file = trailingslashit((string) ($uploads['basedir'] ?? '')) . 'packrivo-i18n/' . $language . '-dict.json';
        $dict = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
        $translation = is_array($dict) ? ($dict['title'][$title] ?? null) : null;
        if (is_string($translation) && trim($translation) !== '') $title = $translation;
        return str_replace($old, '<title>' . esc_html($title) . '</title>', $html);
    });
}
