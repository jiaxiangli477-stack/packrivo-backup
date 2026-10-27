<?php
/**
 * Single post template: new header, article shell (breadcrumb, cover, byline,
 * related reading, closing CTA), parent footer.
 */
get_header();

while (have_posts()) :
    the_post();
    $pk_id = get_the_ID();
    $pk_cat = get_the_category();
    $pk_blog = (int) get_option('page_for_posts');
    $pk_blog_url = $pk_blog ? get_permalink($pk_blog) : home_url('/blog/');
    ?>
    <main id="main" class="pk-main">
        <div class="pk-wrap" style="padding:40px 0 72px">
            <nav class="pk-breadcrumb pk-mono" aria-label="Breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url($pk_blog_url); ?>">Journal</a>
                <?php if ($pk_cat) : ?>
                    <span aria-hidden="true">/</span>
                    <a href="<?php echo esc_url(add_query_arg('topic', $pk_cat[0]->slug, $pk_blog_url)); ?>"><?php echo esc_html($pk_cat[0]->name); ?></a>
                <?php endif; ?>
            </nav>

            <article class="article" style="margin-top:22px">
                <p class="pk-eyebrow"><?php echo esc_html(get_the_date('M j, Y')); ?></p>
                <h1 class="pk-h2" style="font-size:clamp(2rem,3.6vw,2.8rem);max-width:24ch"><?php the_title(); ?></h1>
                <?php
                // The parent's Article schema still points at this meta image, so it
                // must stay on the page or the structured data describes a ghost.
                $pk_cover = get_post_meta($pk_id, '_packrivo_article_cover_url', true);
                if ($pk_cover) :
                    $pk_alt = get_post_meta($pk_id, '_packrivo_article_cover_alt', true);
                    ?>
                    <figure class="pk-post-cover" style="margin:26px 0 0">
                        <img src="<?php echo esc_url($pk_cover); ?>" alt="<?php echo esc_attr($pk_alt ?: get_the_title()); ?>" loading="eager" decoding="async" />
                    </figure>
                <?php endif; ?>
                <div class="pk-post-body" style="margin-top:28px;max-width:70ch"><?php the_content(); ?></div>
            </article>

            <?php
            $pk_related = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 3,
                'post__not_in' => [$pk_id],
                'category__in' => $pk_cat ? wp_list_pluck($pk_cat, 'term_id') : [],
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            if ($pk_related) :
                ?>
                <section class="pk-related" style="margin-top:56px;padding-top:28px;border-top:1px solid var(--pk-line)">
                    <p class="pk-eyebrow">Keep reading</p>
                    <div class="pk-posts">
                        <?php foreach ($pk_related as $pk_post) : ?>
                            <a class="pk-post" href="<?php echo esc_url(get_permalink($pk_post)); ?>">
                                <span class="pk-post-meta pk-mono"><?php echo esc_html(get_the_date('M j, Y', $pk_post)); ?></span>
                                <span class="pk-post-title"><?php echo esc_html(get_the_title($pk_post)); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <section class="pk-cta-band">
            <div class="pk-wrap pk-cta-inner">
                <div>
                    <h2 class="pk-h2 pk-on-dark">Have a spec you need priced?</h2>
                    <p class="pk-lede pk-on-dark-muted">Five steps, live pricing, one business day reply.</p>
                </div>
                <a class="pk-btn pk-btn-light" href="<?php echo esc_url(home_url('/inquiry/')); ?>">Build a quote</a>
            </div>
        </section>
    </main>
    <?php
endwhile;

get_footer();
