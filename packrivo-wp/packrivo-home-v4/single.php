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

            <?php if (pk31b_is_english()) :
                $pk_related = get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>3,'post__not_in'=>[$pk_id], 'category__in'=>$pk_cat ? wp_list_pluck($pk_cat,'term_id') : [], 'orderby'=>'date','order'=>'DESC']);
                if (count($pk_related)<3) {
                    $excluded=array_merge([$pk_id],wp_list_pluck($pk_related,'ID'));
                    $pk_related=array_merge($pk_related,get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>3-count($pk_related),'post__not_in'=>$excluded,'orderby'=>'date','order'=>'DESC']));
                }
                $pk_structures=pk_related_structures($pk_id);
                $pk_product_ids=array_slice($pk_structures['ids'],0,2);
                if (!$pk_product_ids) {
                    $fallback=['jewelry'=>'product-drawer-jewelry-box','gift'=>'product-collapsible-magnetic-gift-box','mailer'=>'product-front-tuck-mailer','bag'=>'product-euro-tote-paper-carrier','label'=>'product-sheet-labels','insert'=>'product-die-cut-insert'];
                    $product=get_page_by_path($fallback[$pk_structures['group']] ?? $fallback['jewelry']);
                    if ($product && $product->post_status==='publish') $pk_product_ids[]=$product->ID;
                }
            ?>
                <section class="pk-related" data-pk31b-related style="margin-top:56px;padding-top:28px;border-top:1px solid var(--pk-line)">
                  <h2 class="pk-h2">Related reading</h2><ul class="pk-list">
                    <?php foreach($pk_related as $pk_post) : ?><li><a href="<?php echo esc_url(get_permalink($pk_post)); ?>"><?php echo esc_html(get_the_title($pk_post)); ?></a></li><?php endforeach; ?>
                    <?php foreach($pk_product_ids as $pk_product_id) : ?><li><a href="<?php echo esc_url(get_permalink($pk_product_id)); ?>"><?php echo esc_html(get_the_title($pk_product_id)); ?></a></li><?php endforeach; ?>
                  </ul>
                </section>
            <?php else : $pk_structures = pk_related_structures($pk_id); if ($pk_structures['ids']) : ?>
                <section class="pk-related" style="margin-top:56px" data-product-group="<?php echo esc_attr($pk_structures['group']); ?>">
                    <h2 class="pk-h2">Related structures</h2>
                    <div class="pk-cards pk-cards-4"><?php foreach (array_slice($pk_structures['ids'], 0, 2) as $pk_product) get_template_part('template-parts/product-card', null, ['product'=>$pk_product,'group'=>$pk_structures['group']]); ?></div>
                </section>
            <?php endif; ?>
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
            <?php endif; ?>
        </div>

        <section class="pk-cta-band">
            <div class="pk-wrap pk-cta-inner">
                <div>
                    <h2 class="pk-h2 pk-on-dark">Put it to work on a real pack</h2>
                    <p class="pk-lede pk-on-dark-muted">The quote builder applies these numbers to your size and quantity. We confirm the dieline before anything is produced.</p>
                </div>
                <div><a class="pk-btn pk-btn-light" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Build a quote</a> <a class="pk-btn pk-btn-light" href="<?php echo esc_url(home_url('/samples/')); ?>">Order a sample</a></div>
            </div>
        </section>
    </main>
    <?php
endwhile;

get_footer();
