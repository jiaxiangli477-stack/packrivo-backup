<?php
/**
 * /get-a-quote/ 的页面级 SEO：H1、文档标题、meta description。
 *
 * 这一页（生产 ID 3383）的内容只有一行短代码 [packrivo_quote]，模板里既没有 H1
 * 也没有描述，Yoast 只能拼出默认标题 "Get a Quote - Packrivo"。
 * 三个串都先过词典（pkv4_i18n_t），11 个语种的译文与 29x 共用同一份 uploads/packrivo-i18n。
 */
if (!defined('ABSPATH')) {
    exit;
}

/** 英文原文：词典的键就是这三串，改这里要同步 deploy/i18n-merged/*-dict.json。 */
function pkv4_quote_strings()
{
    return [
        'title' => 'Get a Custom Packaging Quote | Packrivo',
        'h1'    => 'Get a packaging quote',
        'desc'  => 'Custom packaging quote. DDP or FOB, quoted both ways. Dieline before production. MOQ 250, 5-10 day sampling. Request a quote.',
    ];
}

function pkv4_is_quote_page()
{
    return is_page('get-a-quote');
}

function pkv4_quote_text($key, $kinds)
{
    $strings = pkv4_quote_strings();
    return pkv4_i18n_t($strings[$key], $kinds);
}

// 标题：Yoast 在时由 wpseo_title 决定，不在时由 pre_get_document_title 决定，两处都接管。
add_filter('wpseo_title', function ($title) {
    return pkv4_is_quote_page() ? pkv4_quote_text('title', ['title']) : $title;
}, 1200);
add_filter('pre_get_document_title', function ($title) {
    return pkv4_is_quote_page() ? pkv4_quote_text('title', ['title']) : $title;
}, 1200);

// 描述：Yoast 没有这一页的 metadesc 时由 wpseo_metadesc 兜底输出。
add_filter('wpseo_metadesc', function ($desc) {
    return pkv4_is_quote_page() ? pkv4_quote_text('desc', ['text', 'block']) : $desc;
}, 1200);

// Yoast 不在时自己打印一条，避免又回到「无描述」。
add_action('wp_head', function () {
    if (!pkv4_is_quote_page() || defined('WPSEO_VERSION')) return;
    echo '<meta name="description" content="' . esc_attr(pkv4_quote_text('desc', ['text', 'block'])) . '" />' . "\n";
}, 4);
