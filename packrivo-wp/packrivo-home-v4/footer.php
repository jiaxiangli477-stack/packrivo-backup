<?php
/**
 * Child footer: reuse the parent's footer markup, then close the document.
 * The opening <main> lives in each template (header.php no longer opens one),
 * so nothing here needs to close it.
 */
if (function_exists('packrivo_shared_footer')) {
    echo packrivo_shared_footer();
}
wp_footer();
?>
</body>
</html>
