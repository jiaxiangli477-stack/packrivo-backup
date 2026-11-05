<?php
/** Shared catalogue pricing and non-destructive native category-card enhancement. */
if (!defined('ABSPATH')) exit;

/** Only use site-owned product images; a missing local rehearsal asset is blank. */
function pk33a_product_image($url) {
    if (!is_string($url) || $url === '') return '';
    $parts = wp_parse_url($url);
    if (!is_array($parts) || isset($parts['user']) || isset($parts['pass']) || (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http','https'], true))) return '';
    $host = strtolower((string)($parts['host'] ?? ''));
    $home_host = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
    if ($host && !in_array($host, array_unique([$home_host,'www.packrivo.com','packrivo.com']), true)) return '';
    $path = $parts['path'] ?? '';
    if (!str_starts_with($path, '/')) return '';
    if (in_array($home_host, ['localhost','127.0.0.1','::1'], true)) {
        $path = rawurldecode($path);
        if (strpos($path, '..') !== false) return '';
        $file = ABSPATH . ltrim($path, '/');
        if (!is_file($file)) return '';
        if ($host && in_array($host, ['www.packrivo.com','packrivo.com'], true)) return home_url($path . (isset($parts['query']) ? '?'.$parts['query'] : ''));
    }
    return $url;
}

/** Only public, confirmed page-specific measurements/materials are shown. */
function pk33a_product_specs($post_id) {
    if (!function_exists('pkpw_store')) return [];
    $store = pkpw_store();
    $record = is_object($store) && method_exists($store, 'read_public') ? $store->read_public((int)$post_id) : null;
    if (!is_array($record) || empty($record['specs']) || !is_array($record['specs'])) return [];
    $out=[];
    foreach (['formed_box_dimensions','materials','usable_inside_space'] as $key) {
        $spec=$record['specs'][$key] ?? null;
        if (!is_array($spec) || ($spec['status'] ?? '') !== 'confirmed' || !is_string($spec['value'] ?? null) || trim($spec['value']) === '') continue;
        if ($key !== 'materials' && !in_array($spec['measurement_state'] ?? '', ['measured','supplier_confirmed'], true)) continue;
        $label=trim(wp_strip_all_tags((string)($spec['label'] ?? '')));
        if ($label === '') continue;
        $value=trim(wp_strip_all_tags($spec['value']));
        if (!empty($spec['unit'])) $value.=' '.trim(wp_strip_all_tags((string)$spec['unit']));
        $out[]=[$label,$value];
    }
    return $out;
}

function pk33a_product_spec_html($post_id) {
    $specs=pk33a_product_specs($post_id);
    if (!$specs) return '';
    $html='<dl class="pk33a-product-specs">';
    foreach ($specs as [$label,$value]) $html.='<div><dt>'.esc_html($label).'</dt><dd>'.esc_html($value).'</dd></div>';
    return $html.'</dl>';
}

/** The same 250-unit engine reference and quote link for every card surface. */
function pk33a_product_footer($post) {
    $post=get_post($post); if (!$post) return '';
    $quantity=250; $style=pk_engine_style_for($post->ID); $price=pk_from_price($post->ID,$quantity);
    $structured=function_exists('pk33b_get_product') && (!function_exists('packrivo_lang') || packrivo_lang() === null) ? pk33b_get_product($post->ID) : null;
    if ($structured && function_exists('pk33b_card_price')) {
        $style=$structured['engine_style'] ?: $style;
        $price=pk33b_card_price($post->ID,$quantity);
    }
    $query=['product'=>$post->post_name];
    if ($price !== null && $style) $query=['style'=>$style,'qty'=>$quantity,'product'=>$post->post_name];
    elseif ($structured && $style) $query=['style'=>$style,'qty'=>$quantity,'product'=>$post->post_name];
    $translate=static function ($s) { return function_exists('pkv4_i18n_t') ? pkv4_i18n_t($s,['text','block','attr']) : $s; };
    $html='<div class="pk-product-card-footer"><span>';
    if ($price !== null && $style) {
        $html.='<small>'.esc_html(sprintf($translate('From %s units'), number_format_i18n($quantity))).'</small>';
        $html.='<strong><bdi dir="ltr">$'.esc_html(number_format((float)$price,2)).'</bdi> '.esc_html($translate('/ unit')).'</strong>';
        $html.='<small>'.esc_html($translate('Reference estimate')).'</small>';
    }
    $html.='</span><a class="pk33a-configure" href="'.esc_url(add_query_arg($query,home_url('/get-a-quote/'))).'">'.esc_html($translate($price !== null && $style ? 'Configure →' : 'Get a price →')).'</a></div>';
    return $html;
}

/** Add price UI to already-published category cards without replacing their text or links. */
function pk33a_enhance_category_content($content) {
    if (!is_string($content) || !function_exists('pkv4_itemlist_category_slugs')) return $content;
    $categories=pkv4_itemlist_category_slugs();
    $current=(string)get_page_uri(get_queried_object_id());
    if (!in_array($current,$categories,true)) return $content;
    $pills='<nav class="pk-chips pk33a-category-chips" aria-label="'.esc_attr('Product categories').'">';
    $all=get_page_by_path('products');
    if ($all && $all->post_status==='publish') $pills.='<a class="pk-chip" href="'.esc_url(get_permalink($all)).'">'.esc_html('All').'</a>';
    foreach ($categories as $slug) {
        $page=get_page_by_path($slug); if (!$page || $page->post_status!=='publish') continue;
        $pills.='<a class="pk-chip'.($slug===$current?' is-active':'').'"'.($slug===$current?' aria-current="page"':'').' href="'.esc_url(get_permalink($page)).'">'.esc_html(get_the_title($page)).'</a>';
    }
    $pills.='</nav>';
    // Insert only before the first known product grid, never inside arbitrary content.
    $with_pills=preg_replace_callback('~(<div\b[^>]*class=["\'][^"\']*\bformat-grid\b[^"\']*["\'][^>]*>)~i', static function ($match) use ($pills) { return $pills.$match[1]; }, $content, 1);
    if (is_string($with_pills)) $content=$with_pills;
    $changed=preg_replace_callback('~<article\b(?=[^>]*\bclass=["\'][^"\']*\btext-card\b[^"\']*["\'])[^>]*>(?:(?!<article\b|</article>)[\s\S])*?</article>~i', static function ($match) {
        $html=$match[0]; $slugs=[];
        if (!preg_match_all('~\bhref\s*=\s*(["\'])(.*?)\1~is',$html,$links)) return $html;
        $home_host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
        foreach ($links[2] as $link) {
            $url=wp_parse_url(html_entity_decode($link,ENT_QUOTES|ENT_HTML5,'UTF-8')); if (!is_array($url)) continue;
            $host=strtolower((string)($url['host'] ?? ''));
            if ($host && !in_array($host,[$home_host,'www.packrivo.com','packrivo.com'],true)) continue;
            if (!preg_match('~^/([a-z0-9-]+)/?$~', $url['path'] ?? '', $m)) continue;
            $candidate=get_page_by_path($m[1]);
            if ($candidate && $candidate->post_status==='publish' && (str_starts_with($m[1],'product-') || get_post_meta($candidate->ID,'_packrivo_remediation_product',true))) $slugs[$m[1]]=true;
        }
        if (count($slugs)!==1) return $html;
        $post=get_page_by_path((string)array_key_first($slugs));
        if (!$post || $post->post_status!=='publish') return $html;
        $html=preg_replace('~^(<article\b[^>]*\bclass=["\'])([^"\']*)~i','$1$2 pk-product-card pk33a-category-card',$html,1);
        if (in_array($home_host,['localhost','127.0.0.1','::1'],true)) {
            // Media files are not part of some local snapshots; never point a
            // rehearsal browser at the real site to fill those holes.
            $html=preg_replace_callback('~<img\b[^>]*>~i', static function ($image) {
                if (!preg_match('~\bsrc\s*=\s*(["\'])(.*?)\1~is',$image[0],$src)) return $image[0];
                $local=pk33a_product_image(html_entity_decode($src[2],ENT_QUOTES|ENT_HTML5,'UTF-8'));
                if (!$local) return '<span class="pk33a-product-placeholder" aria-hidden="true"></span>';
                $tag=preg_replace('~\s+srcset\s*=\s*(["\']).*?\1~is','',$image[0]);
                return preg_replace_callback('~\bsrc\s*=\s*(["\'])(.*?)\1~is',static function ($attr) use ($local) { return 'src='.$attr[1].esc_url($local).$attr[1]; },$tag,1);
            },$html);
        }
        $badge='<span class="pk-product-moq">'.esc_html(function_exists('pkv4_i18n_t') ? pkv4_i18n_t('MOQ 250',['text','block']) : 'MOQ 250').'</span>';
        $html=preg_replace('~^(<article\b[^>]*>)~i','$1'.$badge,$html,1);
        $addition=pk33a_product_spec_html($post->ID).pk33a_product_footer($post);
        return substr($html,0,-strlen('</article>')).$addition.'</article>';
    }, $content);
    if (!is_string($changed)) return $content;
    $home_host = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
    if (in_array($home_host, ['localhost','127.0.0.1','::1'], true)) {
        // Other category imagery can also be absent from an isolated snapshot.
        // Preserve its accessible description without making a production
        // request or substituting an unrelated product image.
        $changed = preg_replace_callback('~<img\b[^>]*>~i', static function ($image) {
            if (!preg_match('~\bsrc\s*=\s*(["\'])(.*?)\1~is', $image[0], $src)) return $image[0];
            $local = pk33a_product_image(html_entity_decode($src[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!$local) {
                $alt = preg_match('~\balt\s*=\s*(["\'])(.*?)\1~is', $image[0], $m) ? html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                return '<span class="pk33a-local-image-placeholder"' . ($alt !== '' ? ' role="img" aria-label="'.esc_attr($alt).'"' : ' aria-hidden="true"') . '></span>';
            }
            $tag = preg_replace('~\s+srcset\s*=\s*(["\']).*?\1~is', '', $image[0]);
            return preg_replace_callback('~\bsrc\s*=\s*(["\'])(.*?)\1~is', static function ($attr) use ($local) { return 'src='.$attr[1].esc_url($local).$attr[1]; }, $tag, 1);
        }, $changed);
    }
    return is_string($changed) ? $changed : $content;
}

// Native category templates call get_the_content directly. WordPress has
// already split the main post into the request-local $pages at this hook.
// Change only those rendered segments, never the cached post or database.
add_action('the_post', static function ($post, $query) {
    if (!$query || !$query->is_main_query() || !$query->is_page() || !in_the_loop() || !pkv4_is_itemlist_category()) return;
    global $pages;
    if (is_array($pages)) foreach ($pages as &$segment) $segment=pk33a_enhance_category_content($segment);
    unset($segment);
}, 10, 2);
