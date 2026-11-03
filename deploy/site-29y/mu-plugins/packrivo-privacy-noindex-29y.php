<?php
/**
 * Plugin Name: Packrivo Privacy Noindex + Lang Note (29y)
 * Description: 只对外语前缀的 privacy 页生效：加 noindex，并在正文顶部显示「本政策仅以英文提供」。
 *              英文 /privacy/ 不加 noindex、不显示说明。
 * Version: 2.1.0
 *
 * 生效方式：装到 wp-content/mu-plugins/ ；删除本文件即完全回滚（页面内容不动）。
 * 语言判定用 i18n mu-plugin 的 packrivo_lang()（它把 /it/... 重写成英文路径前先 define 了语言）。
 */
if (!defined('ABSPATH')) { exit; }

const PKQ29Y_PRIVACY_NOTE_EN = 'This privacy policy is provided in English only. The English version is the authoritative one.';

/** 是否「外语前缀 + privacy 页」；英文 /privacy/ 返回 false。 */
function pkq29y_is_foreign_privacy()
{
    if (is_admin()) {
        return false;
    }
    if (!function_exists('packrivo_lang') || packrivo_lang() === null) {
        return false;
    }
    return (bool) is_page('privacy');
}

add_filter('wp_robots', function ($robots) {
    if (pkq29y_is_foreign_privacy()) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        unset($robots['index'], $robots['follow']);
    }
    return $robots;
}, 99);

add_action('template_redirect', function () {
    if (pkq29y_is_foreign_privacy() && !headers_sent()) {
        header('X-Robots-Tag: noindex, nofollow', true);
    }
}, 1);

add_action('wp_head', function () {
    if (pkq29y_is_foreign_privacy()) {
        echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
    }
}, 1);

/*
 * 说明句：i18n mu-plugin 在 template_redirect(10) 上开缓冲区做整串替换。
 * 这里的缓冲区必须开在它之后（11），才是内层、先于它执行，
 * 这样插进去的英文原串才会被同一个缓冲回调翻译成对应语种。
 */
add_action('template_redirect', function () {
    if (!pkq29y_is_foreign_privacy()) {
        return;
    }
    ob_start(static function ($html) {
        if (strpos($html, 'privacy-lang-note') !== false) {
            return $html;
        }
        $note = '<p class="privacy-lang-note">' . PKQ29Y_PRIVACY_NOTE_EN . '</p>';
        // 主题输出的开场标签是 <main id="main" class="pk-main">，按正则插到它后面
        $out = preg_replace('~(<main\b[^>]*>)~i', '$1' . $note, $html, 1);
        if (!is_string($out) || strpos($out, 'privacy-lang-note') === false) {
            return $html;
        }
        return $out;
    });
}, 11);
