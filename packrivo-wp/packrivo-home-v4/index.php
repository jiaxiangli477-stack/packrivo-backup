<?php
/**
 * Fallback archive/index: new header + a plain list of posts.
 */
get_header();
?>
<main id="main" class="pk-main">
    <div class="pk-wrap" style="padding:56px 0 72px">
        <h1 class="pk-h2"><?php echo esc_html(wp_get_document_title()); ?></h1>
        <div class="pk-posts" style="margin-top:28px">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <a class="pk-post" href="<?php the_permalink(); ?>">
                    <span class="pk-post-title"><?php the_title(); ?></span>
                    <span class="pk-post-meta pk-mono"><?php echo esc_html(get_the_date('M j, Y')); ?></span>
                </a>
            <?php endwhile; else : ?>
                <p class="pk-lede">Nothing here yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php get_footer(); ?>
