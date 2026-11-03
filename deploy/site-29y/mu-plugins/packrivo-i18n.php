<?php
/**
 * Plugin Name: Packrivo i18n pages
 * Description: Serves /{lang}/ versions of core pages by rendering the English page and translating it from a reviewed dictionary (uploads/packrivo-i18n/{lang}-dict.json). Adds hreflang, per-language sitemaps and language-switch routing. Delete this file to remove all translated pages.
 * Version: 2026-10-24.1
 *
 * Changes vs 2026-10-22:
 *   - 文字游程（>text<）回退查询同时命中 block 桶，修复"含内联 <a> 的段落整段留英文"
 *   - 字典查询做 HTML 实体归一（&amp; / &#8217; 等），避免键对不上
 *   - 路径表可由 uploads/packrivo-i18n/paths.json 覆盖，扩页面不必改代码
 */

if (!defined('ABSPATH')) exit;

const PACKRIVO_I18N_BASE = 'https://www.packrivo.com';

/** Pages that have a translated version (fallback when paths.json is absent). */
const PACKRIVO_I18N_PATHS = [
    '/', '/products/', '/jewelry-boxes/', '/foldable-gift-boxes/', '/mailer-boxes/', '/labels/', '/inserts/', '/bags-pouches/',
    '/product-drawer-jewelry-box/', '/product-magnetic-jewelry-box/', '/product-collapsible-magnetic-gift-box/', '/product-front-tuck-mailer/',
    '/services/', '/about/', '/inquiry/', '/compare/',
];

/** Active languages. Add an entry and drop {lang}-dict.json into the uploads folder. */
function packrivo_i18n_langs(): array {
    static $langs = null;
    if ($langs !== null) return $langs;
    $langs = [
        'de'    => ['locale' => 'de-DE', 'dir' => 'ltr', 'label' => 'Deutsch',  'english' => 'German'],
        'fr'    => ['locale' => 'fr-FR', 'dir' => 'ltr', 'label' => 'Français', 'english' => 'French'],
        'es'    => ['locale' => 'es-ES', 'dir' => 'ltr', 'label' => 'Español',  'english' => 'Spanish'],
        'it'    => ['locale' => 'it-IT', 'dir' => 'ltr', 'label' => 'Italiano', 'english' => 'Italian'],
        'nl'    => ['locale' => 'nl-NL', 'dir' => 'ltr', 'label' => 'Nederlands', 'english' => 'Dutch'],
        'pt'    => ['locale' => 'pt-PT', 'dir' => 'ltr', 'label' => 'Português', 'english' => 'Portuguese'],
        'ar'    => ['locale' => 'ar-SA', 'dir' => 'rtl', 'label' => 'العربية',  'english' => 'Arabic'],
        'ja'    => ['locale' => 'ja-JP', 'dir' => 'ltr', 'label' => '日本語',    'english' => 'Japanese'],
        'ko'    => ['locale' => 'ko-KR', 'dir' => 'ltr', 'label' => '한국어',    'english' => 'Korean'],
        'zh-CN' => ['locale' => 'zh-CN', 'dir' => 'ltr', 'label' => '简体中文',  'english' => 'Simplified Chinese'],
        'zh-TW' => ['locale' => 'zh-TW', 'dir' => 'ltr', 'label' => '繁體中文',  'english' => 'Traditional Chinese'],
    ];
    return $langs;
}

/** Languages that actually have a dictionary on disk. */
function packrivo_i18n_ready(): array {
    static $ready = null;
    if ($ready !== null) return $ready;
    $dir = packrivo_i18n_dir();
    $ready = [];
    foreach (packrivo_i18n_langs() as $code => $meta) {
        if (is_readable($dir . $code . '-dict.json')) $ready[$code] = $meta;
    }
    return $ready;
}

function packrivo_i18n_dir(): string {
    $uploads = wp_get_upload_dir();
    return trailingslashit($uploads['basedir']) . 'packrivo-i18n/';
}

/** Path table: paths.json overrides the constant so product pages can be added without code edits. */
function packrivo_i18n_paths(): array {
    static $paths = null;
    if ($paths !== null) return $paths;
    $file = packrivo_i18n_dir() . 'paths.json';
    if (is_readable($file)) {
        $data = json_decode((string) @file_get_contents($file), true);
        if (is_array($data) && $data) {
            $paths = array_values(array_filter(array_map('strval', $data)));
            return $paths;
        }
    }
    $paths = PACKRIVO_I18N_PATHS;
    return $paths;
}

/** Route availability and search indexability are separate: inherit the English Yoast sitemap. */
function packrivo_i18n_indexable_paths(): array {
    // Called after plugins and the theme initialize; do not duplicate Yoast's exclusion policy.
    if (!class_exists('WPSEO_Post_Type_Sitemap_Provider')) return [];
    $provider = new WPSEO_Post_Type_Sitemap_Provider();
    if (!$provider->is_valid_post_type('page')) return [];
    $urls = array_fill_keys(array_column($provider->get_sitemap_links('page', PHP_INT_MAX, 1), 'loc'), true);
    return array_values(array_filter(packrivo_i18n_paths(), function ($path) use ($urls) {
        return isset($urls[PACKRIVO_I18N_BASE . $path]);
    }));
}

// Wait for ordinary plugins and theme sitemap filters before generating XML.
function packrivo_i18n_render_sitemap() {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!preg_match('#^/([a-zA-Z]{2}(?:-[a-zA-Z]{2})?)-sitemap\.xml$#', $path, $m)) return;
    $code = $m[1];
    $ready = packrivo_i18n_ready();
    if (!isset($ready[$code])) return;
    if (!class_exists('WPSEO_Post_Type_Sitemap_Provider')) {
        wp_die('Sitemap temporarily unavailable.', 'Sitemap unavailable', ['response' => 503]);
    }
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex, follow');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
       . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    foreach (packrivo_i18n_indexable_paths() as $p) {
        $en = esc_url(PACKRIVO_I18N_BASE . $p);
        $loc = esc_url(PACKRIVO_I18N_BASE . '/' . $code . $p);
        echo "<url><loc>{$loc}</loc>";
        echo '<xhtml:link rel="alternate" hreflang="en" href="' . $en . '"/>';
        foreach ($ready as $c2 => $_) {
            echo '<xhtml:link rel="alternate" hreflang="' . esc_attr($c2) . '" href="'
               . esc_url(PACKRIVO_I18N_BASE . '/' . $c2 . $p) . '"/>';
        }
        echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $en . '"/></url>' . "\n";
    }
    echo '</urlset>';
    exit;
}
add_action('wp_loaded', 'packrivo_i18n_render_sitemap');

/* ------------------------------------------------------------------ *
 * Routing — runs before WordPress parses the request
 * ------------------------------------------------------------------ */

(function () {
    $uri   = $_SERVER['REQUEST_URI'] ?? '/';
    $path  = (string) parse_url($uri, PHP_URL_PATH);
    $query = (string) parse_url($uri, PHP_URL_QUERY);
    $langs = array_keys(packrivo_i18n_langs());

    // /de -> /de/
    foreach ($langs as $l) {
        if ($path === '/' . $l) {
            header('Location: /' . $l . '/', true, 301);
            exit;
        }
    }

    // /{lang}/... -> define language, rewrite REQUEST_URI to the English path
    $seg = explode('/', ltrim($path, '/'))[0] ?? '';
    if ($seg === '' || !in_array($seg, $langs, true)) return;

    $ready = packrivo_i18n_ready();

    // 未发布该语种（无字典）：一律 404，避免 WordPress 按 slug 匹配到同名页面
    if (!isset($ready[$seg])) {
        status_header(404);
        nocache_headers();
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
           . '<meta name="robots" content="noindex,follow">'
           . '<title>404 — language not available</title></head><body>'
           . '<p>This language version is not available yet.</p>'
           . '<p><a href="' . PACKRIVO_I18N_BASE . '/">Continue in English</a></p>'
           . '</body></html>';
        exit;
    }

    $en = substr($path, strlen($seg) + 1);          // strip "/{lang}"
    if ($en === '') $en = '/';
    if ($en[0] !== '/') $en = '/' . $en;
    if ($en !== '/' && substr($en, -1) !== '/') $en .= '/';

    // Path not translated -> 404 instead of falling through to a random English page
    if (!in_array($en, packrivo_i18n_paths(), true)) {
        status_header(404);
        nocache_headers();
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="' . esc_attr($seg) . '"><head><meta charset="utf-8">'
           . '<meta name="robots" content="noindex,follow">'
           . '<title>404 — page not translated</title></head><body>'
           . '<p>This page has no ' . esc_html($ready[$seg]['english']) . ' version yet.</p>'
           . '<p><a href="' . PACKRIVO_I18N_BASE . $en . '">Read it in English</a></p>'
           . '</body></html>';
        exit;
    }

    define('PACKRIVO_LANG', $seg);
    define('PACKRIVO_EN_PATH', $en);
    $_SERVER['REQUEST_URI'] = $en . ($query !== '' ? '?' . $query : '');
})();

function packrivo_lang(): ?string {
    return defined('PACKRIVO_LANG') ? PACKRIVO_LANG : null;
}
function packrivo_is_translated(): bool { return packrivo_lang() !== null; }

function packrivo_current_en_path(): ?string {
    if (packrivo_is_translated()) return PACKRIVO_EN_PATH;
    $p = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return in_array($p, packrivo_i18n_paths(), true) ? $p : null;
}

function packrivo_lang_meta(string $code): array {
    $l = packrivo_i18n_langs();
    return $l[$code] ?? ['locale' => $code, 'dir' => 'ltr', 'label' => $code, 'english' => $code];
}

add_filter('redirect_canonical', function ($url) {
    return packrivo_is_translated() ? false : $url;
}, 99);

// Register every translated sitemap in the Yoast index.
add_filter('wpseo_sitemap_index', function ($xml) {
    foreach (packrivo_i18n_ready() as $code => $_) {
        $xml .= '<sitemap><loc>' . PACKRIVO_I18N_BASE . '/' . $code . '-sitemap.xml</loc>'
              . '<lastmod>' . gmdate('c') . '</lastmod></sitemap>' . "\n";
    }
    return $xml;
});

/* ------------------------------------------------------------------ *
 * Head: hreflang matrix + client-side language routing
 * ------------------------------------------------------------------ */

add_action('wp_head', function () {
    $en = packrivo_current_en_path();
    if ($en === null) return;

    $ready = packrivo_i18n_ready();
    if (!$ready) return;

    $cur = packrivo_lang();
    $base = PACKRIVO_I18N_BASE;

    // Self-referencing first, then every other translation.
    echo '<link rel="alternate" hreflang="en" href="' . esc_url($base . $en) . '" />' . "\n";
    foreach ($ready as $code => $_) {
        echo '<link rel="alternate" hreflang="' . esc_attr($code) . '" href="'
           . esc_url($base . '/' . $code . $en) . '" />' . "\n";
    }
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($base . $en) . '" />' . "\n";

    $paths = wp_json_encode(packrivo_i18n_paths());
    $map   = [];
    foreach ($ready as $code => $meta) {
        $map[$code] = ['label' => $meta['label'], 'dir' => $meta['dir']];
    }
    $mapJson = wp_json_encode($map);
    $curJson = wp_json_encode($cur);
    $enJson  = wp_json_encode($en);

    echo "<script id=\"packrivo-i18n-route\">(function(){"
       . "var P={$paths},M={$mapJson},cur={$curJson},en={$enJson};"
       . "function saved(){var v=null;try{v=localStorage.getItem('site_lang');}catch(e){}if(!v){var m=document.cookie.match(/(?:^|; )site_lang=([^;]+)/);v=m&&decodeURIComponent(m[1]);}return v;}"
       . "function save(l){try{localStorage.setItem('site_lang',l);}catch(e){}document.cookie='site_lang='+encodeURIComponent(l)+'; Max-Age=31536000; Path=/; SameSite=Lax; Secure';}"
       . "function clearGT(){['',location.hostname,'.'+location.hostname,'.packrivo.com'].forEach(function(d){document.cookie='googtrans=; Max-Age=0; Path=/'+(d?'; Domain='+d:'')+'; SameSite=Lax; Secure';});}"
       . "function urlFor(l){return l==='en'?en:'/'+l+en;}"
       . "/* W1-4 2026-10-26: automatic language redirect removed. The header selector is the only switch;"
       . " hreflang + x-default already tell crawlers which document belongs to which locale. */"
       . "document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('#packrivo-language-list button[data-lang]');if(!b)return;var l=b.getAttribute('data-lang');"
       . "if(cur&&l===cur){e.preventDefault();e.stopImmediatePropagation();var dl=document.getElementById('packrivo-language-dialog');if(dl&&dl.open)dl.close();return;}"
       . "if(M[l]){e.preventDefault();e.stopImmediatePropagation();save(l);clearGT();location.href=urlFor(l)+location.search;return;}"
       . "if(cur){e.preventDefault();e.stopImmediatePropagation();save('en');clearGT();location.href=en+location.search;}"
       . "},true);})();</script>\n";
}, 2);

/* ------------------------------------------------------------------ *
 * Output translation
 * ------------------------------------------------------------------ */

add_action('template_redirect', function () {
    if (!packrivo_is_translated() || is_admin() || is_feed()) return;
    ob_start('packrivo_i18n_translate');
}, -1000);

function packrivo_i18n_dict(): array {
    static $cache = [];
    $code = packrivo_lang();
    if ($code === null) return ['title' => [], 'attr' => [], 'block' => [], 'text' => []];
    if (isset($cache[$code])) return $cache[$code];

    $file = packrivo_i18n_dir() . $code . '-dict.json';
    $json = @file_get_contents($file);
    $dict = $json ? (array) json_decode($json, true) : [];
    foreach (['title', 'attr', 'block', 'text'] as $k) {
        if (!isset($dict[$k]) || !is_array($dict[$k])) $dict[$k] = [];
    }
    $cache[$code] = $dict;
    return $dict;
}

function packrivo_i18n_norm($s) {
    return trim(preg_replace('/\s+/u', ' ', (string) $s));
}

function packrivo_i18n_lookup($s, array $kinds): ?string {
    $dict = packrivo_i18n_dict();
    $raw  = (string) $s;
    $k    = packrivo_i18n_norm($raw);
    foreach ($kinds as $kind) {
        if (isset($dict[$kind][$k])) return $dict[$kind][$k];
    }
    // 实体归一：页面里是 &amp; / &#8217;，字典里存的是解码后的字符
    $dec = packrivo_i18n_norm(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($dec !== $k) {
        foreach ($kinds as $kind) {
            if (isset($dict[$kind][$dec])) return $dict[$kind][$dec];
        }
    }
    return null;
}

function packrivo_i18n_url($url) {
    $base = PACKRIVO_I18N_BASE;
    $rel  = strpos($url, $base) === 0 ? substr($url, strlen($base)) : $url;
    if ($rel === '' || $rel[0] !== '/') return $url;
    $code = packrivo_lang();
    if ($code === null) return $url;
    if (preg_match('#^/([a-zA-Z]{2}(?:-[a-zA-Z]{2})?)/#', $rel, $m)) return $url; // already prefixed
    $path = (string) parse_url($rel, PHP_URL_PATH);
    if (!in_array($path, packrivo_i18n_paths(), true)) return $url;
    return $base . '/' . $code . $rel;
}

function packrivo_i18n_translate($html) {
    if (!is_string($html) || stripos($html, '</html>') === false) return $html;

    $code = packrivo_lang();
    if ($code === null) return $html;

    $en    = PACKRIVO_EN_PATH;
    $base  = PACKRIVO_I18N_BASE;
    $meta  = packrivo_lang_meta($code);
    $locale = $meta['locale'];
    $dir    = $meta['dir'];

    // --- Yoast / core schema graph: translate name and description ------
    $html = preg_replace_callback(
        '#(<script type="application/ld\+json"[^>]*class="[^"]*(?:yoast|schema)[^"]*"[^>]*>)(.*?)(</script>)#s',
        function ($m) use ($locale) {
            $data = json_decode($m[2], true);
            if (!is_array($data)) return $m[0];

            $dict = packrivo_i18n_dict();
            $lookup = function ($v) use ($dict) {
                $k = trim(preg_replace('/\s+/u', ' ', (string) $v));
                foreach (['title', 'block', 'text', 'attr'] as $kind) {
                    if (isset($dict[$kind][$k])) return $dict[$kind][$k];
                }
                return null;
            };

            // name / description / headline 是检索和富结果直接读取的字段
            $walk = function (&$node, $parentKey = null) use (&$walk, $lookup, $locale) {
                if (!is_array($node)) return;
                foreach ($node as $k => &$v) {
                    if (is_array($v)) {
                        $walk($v, $k);
                        continue;
                    }
                    if (!is_string($v)) continue;
                    if (in_array($k, ['name', 'description', 'headline'], true)) {
                        $t = $lookup($v);
                        if ($t !== null) $v = html_entity_decode(wp_strip_all_tags($t), ENT_QUOTES, 'UTF-8');
                    } elseif ($k === 'inLanguage') {
                        $v = $locale;
                    }
                }
            };
            $walk($data);

            return $m[1] . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $m[3];
        },
        $html
    );

    // --- Structured data -------------------------------------------------
    $html = preg_replace_callback(
        '#(<script type="application/ld\+json" class="packrivo-(?:faq|product)-schema">)(.*?)(</script>)#s',
        function ($m) {
            $data = json_decode($m[2], true);
            if (!is_array($data)) return $m[0];
            array_walk_recursive($data, function (&$v, $key) {
                if (is_string($v) && in_array($key, ['name', 'text', 'description', 'value'], true)) {
                    $t = packrivo_i18n_lookup($v, ['text', 'block', 'attr']);
                    if ($t !== null) $v = html_entity_decode(wp_strip_all_tags($t), ENT_QUOTES, 'UTF-8');
                }
            });
            return $m[1] . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $m[3];
        },
        $html
    );
    $html = str_replace(
        ['"inLanguage":"en-US"', '"inLanguage":"en"'],
        '"inLanguage":"' . $locale . '"',
        $html
    );

    // --- Buyer data consumed by discovery-core.js ------------------------
    // This JSON is vaulted below and cannot be translated as HTML text. Keep
    // the schema stable: only product title/category are user-visible labels;
    // codes, slugs, counts, property names, guides and articles stay untouched.
    $html = preg_replace_callback(
        '#(<script\b(?=[^>]*\bid="packrivo-buyer-data")[^>]*>)(.*?)(</script>)#si',
        function ($m) {
            $data = json_decode($m[2], true);
            if (!is_array($data) || !isset($data['products']) || !is_array($data['products'])) {
                return $m[0];
            }
            foreach ($data['products'] as &$product) {
                if (!is_array($product)) continue;
                foreach (['title', 'category'] as $field) {
                    if (!isset($product[$field]) || !is_string($product[$field]) || trim($product[$field]) === '') {
                        continue;
                    }
                    $translated = packrivo_i18n_lookup($product[$field], ['title', 'block']);
                    if ($translated !== null) {
                        $product[$field] = html_entity_decode(
                            wp_strip_all_tags($translated), ENT_QUOTES | ENT_HTML5, 'UTF-8'
                        );
                    }
                }
            }
            unset($product);
            $encoded = wp_json_encode(
                $data,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                    | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            return is_string($encoded) ? $m[1] . $encoded . $m[3] : $m[0];
        },
        $html
    );

    // --- Protect non-text nodes -----------------------------------------
    $vault = [];
    $html = preg_replace_callback('#<(script|style|svg|noscript)\b[^>]*>.*?</\1>#si', function ($m) use (&$vault) {
        $key = '%%PRI18N' . count($vault) . '%%';
        $vault[$key] = $m[0];
        return $key;
    }, $html);

    // --- <title> ---------------------------------------------------------
    $html = preg_replace_callback('#<title>(.*?)</title>#s', function ($m) {
        $t = packrivo_i18n_lookup($m[1], ['title']);
        return '<title>' . ($t ?? $m[1]) . '</title>';
    }, $html, 1);

    // --- meta description / og / twitter ---------------------------------
    $html = preg_replace_callback(
        '#(<meta\s+(?:name|property)="(?:description|og:title|og:description|twitter:title|twitter:description)"\s+content=")([^"]*)(")#i',
        function ($m) {
            $t = packrivo_i18n_lookup($m[2], ['attr']);
            return $m[1] . ($t !== null ? esc_attr(html_entity_decode($t, ENT_QUOTES, 'UTF-8')) : $m[2]) . $m[3];
        },
        $html
    );

    // --- Block-level text -------------------------------------------------
    // Include inline containers so button/link labels are covered too.
    $block = 'p|li|h[1-6]|dt|dd|td|th|figcaption|label|button|summary|option|legend|caption'
           . '|a|span|strong|em|b|i|small|div';
    $translated = preg_replace_callback(
        '#<(' . $block . ')\b([^>]*)>((?:(?!<(?:p|li|h[1-6]|div|ul|ol|table|section|article|button|a)\b).)*?)</\1>#si',
        function ($m) {
            $t = packrivo_i18n_lookup($m[3], ['block', 'text']);
            return $t === null ? $m[0] : '<' . $m[1] . $m[2] . '>' . $t . '</' . $m[1] . '>';
        },
        $html
    );
    // A PCRE limit leaves the block text in English rather than blanking the page.
    if (is_string($translated)) $html = $translated;

    // --- Attribute text ---------------------------------------------------
    $html = preg_replace_callback('#\b(alt|title|aria-label|placeholder)="([^"]*)"#i', function ($m) {
        $t = packrivo_i18n_lookup($m[2], ['attr']);
        return $t === null ? $m[0] : $m[1] . '="' . esc_attr(html_entity_decode($t, ENT_QUOTES, 'UTF-8')) . '"';
    }, $html);

    // --- Remaining loose text nodes --------------------------------------
    // 关键修复：同时查 block 桶。含内联 <a> 的段落不会被块级正则匹配，
    // 只有这里能救；之前只查 text 桶，导致整段留英文。
    $html = preg_replace_callback('#>([^<>]+)<#', function ($m) {
        if (trim($m[1]) === '' || strpos($m[1], '%%PRI18N') !== false) return $m[0];
        $t = packrivo_i18n_lookup($m[1], ['text', 'block']);
        if ($t === null) return $m[0];
        preg_match('/^\s*/u', $m[1], $lead);
        preg_match('/\s*$/u', $m[1], $trail);
        return '>' . $lead[0] . $t . $trail[0] . '<';
    }, $html);

    // --- Links point at the translated page ------------------------------
    $html = preg_replace_callback('#(<a\b[^>]*\bhref=")([^"]+)(")#i', function ($m) {
        return $m[1] . packrivo_i18n_url($m[2]) . $m[3];
    }, $html);

    // Blog articles are English-only. Mark the destination after translating its navigation label.
    $html = preg_replace_callback('#<a\b([^>]*\bhref=")([^"<>]+)("[^>]*)>(.*?)</a>#si', function ($m) use ($base) {
        $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = wp_parse_url($url);
        if (!is_array($parts) || ($parts['path'] ?? '') !== '/blog/' ||
            (isset($parts['host']) && strtolower($parts['host']) !== strtolower((string) wp_parse_url($base, PHP_URL_HOST)))) return $m[0];
        // Keep the existing href and translated label; do not invent a /{lang}/blog/ route.
        if (strpos($m[4], 'packrivo-blog-language') !== false) return $m[0];
        $attrs = preg_replace('/\s+hreflang="[^"]*"/i', '', $m[3]);
        return '<a' . $m[1] . $m[2] . $attrs . ' hreflang="en">' . $m[4]
             . ' <span class="packrivo-blog-language" lang="en" translate="no">(English)</span></a>';
    }, $html);

    $html = strtr($html, $vault);

    // --- html/og locale, canonical, dir ----------------------------------
    $html = preg_replace('#<html\b([^>]*)\blang="[^"]*"#i', '<html$1lang="' . esc_attr($code) . '"', $html, 1);
    if ($dir === 'rtl') {
        if (preg_match('#<html\b[^>]*\bdir="[^"]*"#i', $html)) {
            $html = preg_replace('#<html\b([^>]*?)\bdir="[^"]*"#i', '<html$1dir="rtl"', $html, 1);
        } else {
            $html = preg_replace('#<html\b#i', '<html dir="rtl"', $html, 1);
        }
    }
    $html = str_replace('content="en_US"', 'content="' . str_replace('-', '_', $locale) . '"', $html);
    $html = preg_replace('#(<link rel="canonical" href=")' . preg_quote($base . $en, '#') . '(")#',
                         '$1' . $base . '/' . $code . $en . '$2', $html, 1);
    $html = preg_replace('#(<meta property="og:url" content=")' . preg_quote($base . $en, '#') . '(")#',
                         '$1' . $base . '/' . $code . $en . '$2', $html, 1);

    // --- Runtime dictionary for text injected after load ------------------
    $jsFile = packrivo_i18n_dir() . $code . '-js.json';
    $jsDict = @file_get_contents($jsFile);
    if ($jsDict) {
        $labels = ['Packaging option','Printing / finish','Your name','Approximate dimensions',
                   'Expected quantity','Destination','Work email','Material','Target date',
                   'Notes','Company','Company website','Contact name'];
        $runtime = '<script id="packrivo-i18n-runtime">(function(){'
            . 'var X=' . $jsDict . ',D=X.exact||{},P=(X.patterns||[]).map(function(p){return[new RegExp(p[0]),p[1]];}),'
            . 'L=' . wp_json_encode($labels) . ',RTL=' . ($dir === 'rtl' ? 'true' : 'false') . ';'
            . 'function tr(s){var t=s.trim();if(!t)return null;if(Object.prototype.hasOwnProperty.call(D,t))return s.replace(t,D[t]);'
            . 'for(var i=0;i<P.length;i++)if(P[i][0].test(t))return s.replace(t,t.replace(P[i][0],P[i][1]));return null;}'
            . 'function brief(n){var v=n.textContent,o=v;'
            . 'L.forEach(function(l){if(!Object.prototype.hasOwnProperty.call(D,l))return;'
            . 'v=v.split("\n"+l+":").join("\n"+D[l]+":");if(v.indexOf(l+":")===0)v=D[l]+v.slice(l.length);});'
            . 'if(D["Not specified"])v=v.split(": Not specified").join(": "+D["Not specified"]);'
            . '["PACKRIVO PACKAGING BRIEF","Prepared for review. Subject to feasibility and sample review."].forEach(function(h){if(D[h])v=v.split(h).join(D[h]);});'
            . 'if(v!==o)n.textContent=v;}'
            . 'function attrs(n){["placeholder","aria-label","title","alt"].forEach(function(a){var v=n.getAttribute(a);if(v){var r=tr(v);if(r!==null&&r!==v)n.setAttribute(a,r);}});}'
            . 'function walk(n){if(n.nodeType===3){var r=tr(n.nodeValue);if(r!==null&&r!==n.nodeValue)n.nodeValue=r;return;}'
            . 'if(n.nodeType!==1)return;var g=n.tagName;if(g==="SCRIPT"||g==="STYLE"||g==="TEXTAREA"||g==="svg")return;'
            . 'if(n.id==="brief-text"){brief(n);return;}attrs(n);for(var c=n.firstChild;c;c=c.nextSibling)walk(c);}'
            . 'function run(){walk(document.body);new MutationObserver(function(ms){ms.forEach(function(m){'
            . 'if(m.type==="characterData")walk(m.target);else if(m.type==="attributes")attrs(m.target);'
            . 'else{m.addedNodes.forEach(walk);if(m.target.id==="brief-text")brief(m.target);}});})'
            . '.observe(document.body,{subtree:true,childList:true,characterData:true,attributes:true,attributeFilter:["placeholder","aria-label","title","alt"]});}'
            . 'if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",run);else run();})();</script>';
        $html = preg_replace_callback('#</body>#i', function ($m) use ($runtime) {
            return $runtime . $m[0];
        }, $html, 1);
    }

    // A translated page is final text: no browser machine translation on top.
    $html = preg_replace('#<script type="text/plain" id="pr-gt-lazy"[^>]*></script>#', '', $html);
    $html = preg_replace('#<script id="pr-gt-loader">.*?</script>#s', '', $html);

    return $html;
}
