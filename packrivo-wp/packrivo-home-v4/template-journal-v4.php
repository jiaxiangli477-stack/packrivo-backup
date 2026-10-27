<?php
/**
 * Template Name: Journal index (v4)
 * Lists all posts with topic filter, sort and pagination.
 */

get_header();

$topic = isset($_GET['topic']) ? sanitize_title($_GET['topic']) : '';
$sort = isset($_GET['sort']) ? sanitize_key($_GET['sort']) : 'newest';
$paged = max(1, (int) get_query_var('paged'), (int) ($_GET['pg'] ?? 1));

$args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => $sort === 'oldest' ? 'date' : 'date',
    'order' => $sort === 'oldest' ? 'ASC' : 'DESC',
];
if ($topic) {
    $args['category_name'] = $topic;
}
$q = new WP_Query($args);

$cats = get_categories(['hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC']);
$posts_page = (int) get_option('page_for_posts');
$base = $posts_page ? get_permalink($posts_page) : get_permalink();
?>
<main id="main" class="pk-main">
    <section class="pk-hero" style="padding:56px 0 36px">
        <div class="pk-wrap">
            <p class="pk-eyebrow">Journal · <?php echo (int) wp_count_posts('post')->publish; ?> articles</p>
            <h1 class="pk-display" style="font-size:clamp(2.2rem,4.4vw,3.4rem)">Packaging notes from the floor</h1>
            <p class="pk-lede">Supplier briefs, material tests and pricing notes — written from real runs, not from a keyword list.</p>
        </div>
    </section>

    <section class="pk-section" style="padding-top:0">
        <div class="pk-wrap">
            <div class="pk-filterbar">
                <div class="pk-chips">
                    <a class="pk-chip <?php echo !$topic ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['sort' => $sort], $base)); ?>">All <span><?php echo (int) wp_count_posts('post')->publish; ?></span></a>
                    <?php foreach ($cats as $c) : ?>
                        <a class="pk-chip <?php echo $topic === $c->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['topic' => $c->slug, 'sort' => $sort], $base)); ?>">
                            <?php echo esc_html($c->name); ?> <span><?php echo (int) $c->count; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <form class="pk-sort" method="get" action="<?php echo esc_url($base); ?>">
                    <?php if ($topic) : ?><input type="hidden" name="topic" value="<?php echo esc_attr($topic); ?>" /><?php endif; ?>
                    <label class="pk-label" for="sort">Sort</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php selected($sort, 'newest'); ?>>Newest first</option>
                        <option value="oldest" <?php selected($sort, 'oldest'); ?>>Oldest first</option>
                    </select>
                </form>
            </div>

            <div class="pk-posts" style="grid-template-columns:repeat(3,minmax(0,1fr))">
                <?php if ($q->have_posts()) : while ($q->have_posts()) : $q->the_post(); ?>
                    <a class="pk-post" href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <span class="pk-post-thumb"><?php the_post_thumbnail('medium', ['loading' => 'lazy']); ?></span>
                        <?php endif; ?>
                        <span class="pk-post-meta pk-mono">
                            <?php
                            $cat = get_the_category();
                            echo esc_html(($cat ? $cat[0]->name . ' · ' : '') . get_the_date('M j, Y'));
                            ?>
                        </span>
                        <span class="pk-post-title"><?php the_title(); ?></span>
                        <span class="pk-card-text"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></span>
                    </a>
                <?php endwhile; else : ?>
                    <p class="pk-lede">No articles in this topic yet.</p>
                <?php endif; wp_reset_postdata(); ?>
            </div>

            <div class="pk-pager">
                <?php
                for ($i = 1; $i <= min((int) $q->max_num_pages, 20); $i++) {
                    $url = add_query_arg(['topic' => $topic, 'sort' => $sort, 'pg' => $i], $base);
                    printf('<a class="pk-chip %s" href="%s">%d</a>', $i === $paged ? 'is-active' : '', esc_url($url), $i);
                }
                ?>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
