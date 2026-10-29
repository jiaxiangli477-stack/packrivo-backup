<?php
/**
 * Page template: new header, page content, parent footer.
 *
 * 392 published pages carry `_packrivo_native_page = 1`, and their stored HTML
 * still contains the old page shell (its own <header class="site-header">, skip
 * link and <main id="main">). Rendering that verbatim puts a second nav and a
 * second main landmark under the new header, so the shell is stripped here.
 */
get_header();

while (have_posts()) :
    the_post();
    $native = (string) get_post_meta(get_the_ID(), '_packrivo_native_page', true) === '1';
    $content = get_the_content();

    if ($native && is_page('inquiry')) {
        require_once get_stylesheet_directory() . '/inc/repair-inquiry.php';
        $reference = get_template_directory() . '/packrivo-static/assets/inquiry-controls.html';
        if (is_readable($reference)) $content = pk_repair_inquiry_controls($content, file_get_contents($reference));
    }

    if ($native) {
        $content = preg_replace(
            [
                // The stored pages each carry a full copy of the old shell.
                '~<header\b[^>]*class=["\'][^"\']*\bsite-header\b[^"\']*["\'][^>]*>[\s\S]*?</header>~i',
                // The old footer has no class on <footer> itself, so it is matched
                // by its inner <div class="footer-top"> and then closed at </footer>.
                '~<footer\b[^>]*>(?:(?!</footer>)[\s\S])*?<div\b[^>]*class=["\'][^"\']*\bfooter-top\b[^"\']*["\'][\s\S]*?</footer>~i',
                '~<a\b[^>]*class=["\'][^"\']*\bskip\b[^"\']*["\'][^>]*>[\s\S]*?</a>~i',
                '~</?main\b[^>]*>~i',
            ],
            '',
            $content
        );
        $content = str_replace('id="main"', '', $content);
        if (is_page('jewelry-boxes')) {
            // The local native snapshot lost style tags but retained the exact
            // stylesheet text. Rewrap the existing rules without rewriting them.
            $leaked = '#jewelry-supplier-references .supplier-reference-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.5rem}#jewelry-supplier-references figure{margin:0}#jewelry-supplier-references img{display:block;width:100%;height:auto}#jewelry-supplier-references figcaption{font-size:.9rem;line-height:1.5;overflow-wrap:anywhere;color:inherit}@media(max-width:767px){#jewelry-supplier-references .supplier-reference-grid{grid-template-columns:1fr}}';
            $position = strpos($content, $leaked);
            if ($position !== false) {
                $before = substr($content, 0, $position);
                $open = strripos($before, '<style');
                $close = strripos($before, '</style>');
                if ($open === false || ($close !== false && $close > $open)) {
                    $content = substr_replace($content, '<style>' . $leaked . '</style>', $position, strlen($leaked));
                }
            }
            $prefix = '</aside>.packrivo-table-wrap{';
            $start = strpos($content, $prefix);
            if ($start !== false) {
                $start += strlen('</aside>');
                $end = strpos($content, '<section class="packrivo-related-block"', $start);
                if ($end !== false && $end - $start <= 4096) {
                    $rules = substr($content, $start, $end - $start);
                    if (strpos($rules, '<') === false && substr(rtrim($rules), -1) === '}') {
                        $content = substr_replace($content, '<style>' . $rules . '</style>', $start, $end - $start);
                    }
                }
            }
            // The restored mobile-card CSS reads data-label. Reuse existing
            // translations because the MU attribute pass does not inspect it.
            if (function_exists('packrivo_lang') && packrivo_lang() !== null && function_exists('packrivo_i18n_lookup')) {
                $localized = preg_replace_callback(
                    '~<table\b(?=[^>]*\bclass=["\'][^"\']*\bcomparison-table\b)[^>]*>[\s\S]*?</table>~i',
                    static function ($table) {
                        return preg_replace_callback('~\bdata-label="([^"]*)"~i', static function ($m) {
                            $translated = packrivo_i18n_lookup($m[1], ['attr', 'text', 'block']);
                            return $translated === null ? $m[0] : 'data-label="' . esc_attr(html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '"';
                        }, $table[0]);
                    },
                    $content
                );
                if ($localized !== null) $content = $localized;
            }
        }
    }
    ?>
    <main id="main" class="pk-main">
        <?php if ($native) : ?>
            <?php // 29x：原生页面内容限宽——补一层与页头 .pk-wrap 同口径的容器（max-width 一致、左右内边距一致）。
            // 本来设计成通栏的区块走 CSS 白名单（.pr-remediation-hero 等）单独放开，其余都收进限宽。 ?>
            <div class="pk-wrap pk-native-wrap"><?php echo $content; // already escaped by the editor pipeline ?></div>
        <?php else : ?>
            <div class="pk-wrap" style="padding:56px 0 72px">
                <?php // Must run the content filters, otherwise shortcodes such as [packrivo_quote] stay literal. ?>
                <article class="article"><?php echo apply_filters('the_content', $content); ?></article>
            </div>
        <?php endif; ?>
    </main>
    <?php
endwhile;

get_footer();
