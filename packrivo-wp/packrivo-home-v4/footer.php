<?php
/**
 * Child footer: reuse the parent's footer markup, then close the document.
 * The opening <main> lives in each template (header.php no longer opens one),
 * so nothing here needs to close it.
 */
if (function_exists('packrivo_shared_footer')) {
    $footer=packrivo_shared_footer();
    if (function_exists('pk31b_is_english') && pk31b_is_english()) $footer=str_replace('</footer>', '<a href="'.esc_url(home_url('/cases/')).'">Packaging examples</a></footer>', $footer);
    if (function_exists('pk31b_language_links')) $footer=str_replace('</footer>', '<div class="pk-wrap pk-languages notranslate" translate="no">'.pk31b_language_links().'</div></footer>', $footer);
    echo $footer;
}
wp_footer();
?>
</body>
</html>
