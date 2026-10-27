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
    }
    ?>
    <main id="main" class="pk-main">
        <?php if ($native) : ?>
            <?php echo $content; // already escaped by the editor pipeline ?>
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
