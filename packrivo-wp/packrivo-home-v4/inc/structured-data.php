<?php
/**
 * 29y 第 4 项：列表页结构化数据（ItemList），按 INTEGRATION-PLAN 思路补齐。
 *
 * - /products/（template-products-v4.php 渲染）：列出当前页产品卡片（WP_Query 结果）。
 * - 分类页（jewelry-boxes / foldable-gift-boxes / mailer-boxes / labels / inserts /
 *   bags-pouches，native 快照渲染）：从正文里真实可见的 <h3><a href="...product-...">
 *   抽取名称与 URL，只列页面里实际出现的产品，不列数据库里的全集。
 * - FAQPage：仅当页面上已有真实可见的 FAQ 问答时才输出（当前分类页没有可见 FAQ，不输出）。
 * - 产品页维持 Service 类型（Yoast 已输出），这里不动。
 *
 * JSON-LD 字段按 schema.org 离线规则自查：ItemList 需要 @type/@id/itemListElement
 * （position+item），item 用 Thing（name+url）即可；不需要 headline。
 */
if (!defined('ABSPATH')) {
    exit;
}

/** 分类页集合：这些页面按 native 内容渲染产品列表。 */
function pkv4_itemlist_category_slugs()
{
    return ['jewelry-boxes', 'foldable-gift-boxes', 'mailer-boxes', 'labels', 'inserts', 'bags-pouches'];
}

/**
 * JSON-LD 里的文字也必须跟页面可见文字一致。
 * 外语页的正文是被输出缓冲翻译的，而缓冲会跳过 <script>，所以 schema 里的英文名
 * 会跟德语/日语页面上的译名对不上 —— 这里主动走一次词典。
 */
function pkv4_schema_text($text)
{
    if (!is_string($text) || $text === '') {
        return $text;
    }
    if (!function_exists('pkv4_i18n_lookup')) {
        return $text;
    }
    $hit = pkv4_i18n_lookup($text, ['block', 'text', 'title', 'attr']);
    if ($hit === null || $hit === '') {
        return $text;
    }
    return html_entity_decode($hit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function pkv4_is_itemlist_category()
{
    if (!is_page()) {
        return false;
    }
    return in_array(get_page_uri(get_queried_object_id()), pkv4_itemlist_category_slugs(), true);
}

/** 从 native 正文抽取真实可见的产品链接（h3 包裹的链接，去重保序）。 */
function pkv4_product_links_from_html($html)
{
    $out = [];
    if (!is_string($html) || $html === '') {
        return $out;
    }
    if (!preg_match_all(
        '~<h3\b[^>]*>\s*<a\b[^>]*href=["\']((?:https?:)?//www\.packrivo\.com/(?:product-[a-z0-9-]+|textile-jewelry-pouch)/)["\'][^>]*>(.*?)</a>~is',
        $html,
        $m,
        PREG_SET_ORDER
    )) {
        return $out;
    }
    $seen = [];
    foreach ($m as $link) {
        $url = esc_url_raw($link[1]);
        if ($url === '' || isset($seen[$url])) {
            continue;
        }
        $name = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(html_entity_decode($link[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        if ($name === '') {
            continue;
        }
        $seen[$url] = true;
        $out[] = ['url' => $url, 'name' => $name];
    }
    return $out;
}

/**
 * 从正文抽取真实可见的 FAQ 问答。只在有可见 FAQ 标题的页面调用。
 * 返回 [['question'=>, 'answer'=>], ...]；抽取不到完整问答时返回 []。
 */
function pkv4_visible_faq_from_html($html)
{
    $out = [];
    if (!is_string($html) || !preg_match('/<h[2-4][^>]*>[^<]{0,120}(?:\bfaq\b|frequently\s+asked)/i', $html)) {
        return $out;
    }
    if (!preg_match_all(
        '~<(?:h[2-4]|p)\b[^>]*>(?:(?!</(?:h[2-4]|p)>).)*?</(?:h[2-4]|p)>~is',
        $html,
        $m
    )) {
        return $out;
    }
    $blocks = array_map(static function ($b) {
        return trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(html_entity_decode($b, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    }, $m[0]);
    $i = 0;
    $n = count($blocks);
    while ($i < $n) {
        $q = $blocks[$i];
        $is_q = (bool) preg_match('/^.*\?$/', $q) && strlen($q) > 4 && strlen($q) < 200;
        if (!$is_q) {
            $i++;
            continue;
        }
        $answers = [];
        $j = $i + 1;
        while ($j < $n && $j < $i + 4 && !preg_match('/^.*\?$/', $blocks[$j])) {
            $answers[] = $blocks[$j];
            $j++;
        }
        $answer = trim(implode(' ', $answers));
        if ($answer !== '' && strlen($answer) < 500) {
            $out[] = ['question' => $q, 'answer' => $answer];
        }
        $i = $j;
    }
    return $out;
}

/** 把产品链接数组编码成 ItemList JSON-LD 字符串。 */
function pkv4_itemlist_jsonld(array $items, $page_url = '')
{
    if (count($items) === 0) {
        return '';
    }
    $elements = [];
    $pos = 1;
    foreach ($items as $item) {
        $elements[] = [
            '@type' => 'ListItem',
            'position' => $pos,
            'item' => [
                '@type' => 'Thing',
                'name' => pkv4_schema_text($item['name']),
                'url' => $item['url'],
            ],
        ];
        $pos++;
    }
    $graph = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Products on this page',
        'url' => $page_url !== '' ? $page_url : home_url(add_query_arg([], $GLOBALS['wp']->request ?? '')),
        'numberOfItems' => count($elements),
        'itemListElement' => $elements,
    ];
    return '<script type="application/ld+json" class="pkv4-itemlist">' . wp_json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

/** FAQPage JSON-LD（仅真实可见 FAQ）。 */
function pkv4_faqpage_jsonld(array $qa, $page_url = '')
{
    if (count($qa) === 0) {
        return '';
    }
    $mainEntity = [];
    foreach ($qa as $row) {
        $mainEntity[] = [
            '@type' => 'Question',
            'name' => pkv4_schema_text($row['question']),
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => pkv4_schema_text($row['answer'])],
        ];
    }
    $graph = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'url' => $page_url !== '' ? $page_url : home_url(add_query_arg([], $GLOBALS['wp']->request ?? '')),
        'mainEntity' => $mainEntity,
    ];
    return '<script type="application/ld+json" class="pkv4-faqpage">' . wp_json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
