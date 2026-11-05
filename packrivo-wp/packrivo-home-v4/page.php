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
    $content = pk31b_orphan_links(pkv4_31a_existing_orphan_links(pkv4_31a_catalog_links(get_the_content())));

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

    // 宽表在窄屏会顶宽整页（.comparison-table 自带 min-width:650px），套一层横滚容器，
    // 表格自己滚，页面不再横向溢出。原生快照里 .packrivo-table-wrap 的 CSS 泄漏成了纯文本
    // （未包 <style>），所以那些 wrap 形同虚设，必须由 site.css 里的 .pk-table-scroll 兜底。
    $content = preg_replace(
        '~<table\b(?=[^>]*\bclass=["\'][^"\']*\bcomparison-table\b)[^>]*>[\s\S]*?</table>~i',
        '<div class="pk-table-scroll">$0</div>',
        $content
    );
    ?>
    <main id="main" class="pk-main">
        <?php if ($native) : ?>
            <?php
            // 29y 第 1 项：产品页原本一个价格都不显示，首页卡片却写 "from $X"。
            // 这一条与卡片同源（pk_from_price + pk_card_reference，250 件）。
            $pk_prod_slug = (string) get_post_field('post_name', get_the_ID());
            $pk_prod_qty = 250;
            $pk_prod_unit = null;
            $pk_prod_ref = null;
            $pk_prod_style = null;
            if (strpos($pk_prod_slug, 'product-') === 0 && function_exists('pk_from_price') && function_exists('pk_engine_style_for')) {
                $pk_prod_style = pk_engine_style_for(get_the_ID());
                $pk_prod_unit = pk_from_price(get_the_ID(), $pk_prod_qty);
                if ($pk_prod_style) $pk_prod_ref = pk_card_reference($pk_prod_style);
            }
            ?>
            <?php if ($pk_prod_unit !== null && $pk_prod_ref) : ?>
                <?php // 29y 第 3 项：价格条按片段查词典（pkv4_i18n_t，英文页原样输出）。
                      // 词典键：'from $%s / unit' 'at %s pcs · %s cm · full-colour print, matte' 'Configure →' ?>
                <div class="pk-wrap">
                    <p class="pk-price-bar">
                        <strong><?php echo esc_html(sprintf(pkv4_i18n_t('from $%s / unit', array('text', 'block')), number_format((float) $pk_prod_unit, 2))); ?></strong>
                        <span><?php
                        $pk_price_template=pkv4_i18n_t('at %s pcs · %s cm · full-colour print, matte', array('text', 'block'));
                        $pk_ref_dimensions=implode('×', array_slice($pk_prod_ref, 0, 3));
                        $pk_33b_rtl=function_exists('packrivo_lang') && packrivo_lang()==='ar' && function_exists('pk33b_get_product') && pk33b_get_product(get_the_ID());
                        if ($pk_33b_rtl && substr_count($pk_price_template, '%s') === 2) {
                            echo sprintf(esc_html($pk_price_template), '<bdi dir="ltr">'.esc_html((string)$pk_prod_qty).'</bdi>', '<bdi dir="ltr">'.esc_html($pk_ref_dimensions).'</bdi>');
                        } else {
                            echo esc_html(sprintf($pk_price_template, (string)$pk_prod_qty, $pk_ref_dimensions));
                        }
                        ?></span>
                        <a href="<?php echo esc_url(add_query_arg(['style' => $pk_prod_style, 'qty' => $pk_prod_qty, 'product' => $pk_prod_slug], home_url('/get-a-quote/'))); ?>"><?php echo esc_html(pkv4_i18n_t('Configure →', array('text', 'block'))); ?></a>
                    </p>
                </div>
            <?php endif; ?>
            <?php // Native pages keep their own section markup, which was built for the old
                  // full-width <main>. Constrain it with the same box the header uses so text
                  // no longer starts at x=0; sections that are meant to bleed carry
                  // .pk-bleed / [data-full-bleed] and escape via CSS (see site.css). ?>
            <div class="pk-page-body">
                <?php echo $content; // already escaped by the editor pipeline ?>
                <?php // 29y 第 4 项：分类页 ItemList（只列正文里真实可见的产品）；FAQPage 仅在页面有可见 FAQ 时输出。 ?>
                <?php if (function_exists('pkv4_is_itemlist_category') && pkv4_is_itemlist_category()) : ?>
                    <?php
                    $pk_items = pkv4_product_links_from_html($content);
                    echo pkv4_itemlist_jsonld($pk_items, get_permalink());
                    $pk_faq = pkv4_visible_faq_from_html($content);
                    echo pkv4_faqpage_jsonld($pk_faq, get_permalink());
                    ?>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="pk-wrap" style="padding:56px 24px 72px">
                <?php // 29y：报价页原本只有一行 [packrivo_quote]，整页没有 H1。 ?>
                <?php if (function_exists('pkv4_is_quote_page') && pkv4_is_quote_page()) : ?>
                    <h1 class="pk-h1"><?php echo esc_html(pkv4_quote_text('h1', ['text', 'block'])); ?></h1>
                    <p class="pk-lede"><?php echo esc_html(pkv4_quote_text('desc', ['text', 'block'])); ?></p>
                <?php endif; ?>
                <?php // Must run the content filters, otherwise shortcodes such as [packrivo_quote] stay literal. ?>
                <article class="article"><?php echo apply_filters('the_content', $content); ?></article>
                <?php if (pkv4_is_quote_page() && pk31b_is_english()) : $pk_email=function_exists('packrivo_master_contact_email') ? packrivo_master_contact_email() : ''; ?>
                  <noscript><p>For a quote, email us or contact us on WhatsApp.</p><p><?php if ($pk_email) : ?><a href="<?php echo esc_url('mailto:'.$pk_email); ?>"><?php echo esc_html($pk_email); ?></a><?php endif; ?> <a href="<?php echo esc_url(packrivo_whatsapp_url()); ?>">WhatsApp</a></p></noscript>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php
endwhile;

get_footer();
