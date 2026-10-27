<?php
/**
 * Template Name: Products index (v4)
 * Lists every product page, filterable by group and sortable.
 */

get_header();

$sort = isset($_GET['sort']) ? sanitize_key($_GET['sort']) : 'title';
$group = isset($_GET['group']) ? sanitize_key($_GET['group']) : 'all';
$paged = max(1, (int) get_query_var('paged'), (int) ($_GET['pg'] ?? 1));

$groups = [
    'jewelry' => ['label' => 'Jewelry boxes', 'match' => ['jewelry', 'ring', 'watch', 'necklace']],
    'gift' => ['label' => 'Gift & folding boxes', 'match' => ['gift', 'folding', 'carton', 'rigid', 'magnetic']],
    'mailer' => ['label' => 'Mailers & shippers', 'match' => ['mailer', 'corrugated', 'shipping', 'tray']],
    'bag' => ['label' => 'Bags & pouches', 'match' => ['bag', 'pouch', 'tote']],
    'label' => ['label' => 'Labels', 'match' => ['label', 'sticker']],
    'insert' => ['label' => 'Inserts', 'match' => ['insert', 'inlay', 'divider', 'pulp']],
];

$orderby = 'title';
$order = 'ASC';
if ($sort === 'newest') {
    $orderby = 'date';
    $order = 'DESC';
} elseif ($sort === 'oldest') {
    $orderby = 'date';
    $order = 'ASC';
} elseif ($sort === 'updated') {
    $orderby = 'modified';
    $order = 'DESC';
}

$per_page = 24;

// Resolve the group to a slug whitelist *before* the query — filtering the page
// of 24 results afterwards produced empty pages for every group but "all".
$ids = get_posts([
    'post_type' => 'page',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_key' => '_packrivo_remediation_product',
    'no_found_rows' => true,
]);

if ($group !== 'all' && isset($groups[$group])) {
    $needles = $groups[$group]['match'];
    $ids = array_values(array_filter($ids, function ($id) use ($needles) {
        $slug = get_post_field('post_name', $id);
        foreach ($needles as $needle) {
            // Word boundaries matter: "drawstring" ends in "ring" and was being
            // counted as a jewelry box. Plurals need -s and -es ("watches", "pouches").
            if (preg_match('~\b' . preg_quote($needle, '~') . '(?:s|es)?\b~i', $slug)) {
                return true;
            }
        }
        return false;
    }));
}

$q = new WP_Query([
    'post_type' => 'page',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => $orderby,
    'order' => $order,
    'post__in' => $ids ?: [0],
]);
$products = $q->posts;

$base = get_permalink(get_queried_object_id());
?>
<main id="main" class="pk-main">
    <section class="pk-hero" style="padding:56px 0 40px">
        <div class="pk-wrap">
            <p class="pk-eyebrow">Catalogue · <?php echo count($ids); ?> structures</p>
            <h1 class="pk-display" style="font-size:clamp(2.2rem,4.4vw,3.4rem)">Every structure we can source</h1>
            <p class="pk-lede">Filter by family, sort by newest or name, and open any structure for the specification, price range and dieline notes.</p>
        </div>
    </section>

    <section class="pk-section" style="padding-top:0">
        <div class="pk-wrap">
            <div class="pk-filterbar">
                <div class="pk-chips">
                    <a class="pk-chip <?php echo $group === 'all' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['group' => 'all', 'sort' => $sort], $base)); ?>">All</a>
                    <?php foreach ($groups as $key => $g) : ?>
                        <a class="pk-chip <?php echo $group === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['group' => $key, 'sort' => $sort], $base)); ?>"><?php echo esc_html($g['label']); ?></a>
                    <?php endforeach; ?>
                </div>
                <form class="pk-sort" method="get" action="<?php echo esc_url($base); ?>">
                    <?php if ($group !== 'all') : ?><input type="hidden" name="group" value="<?php echo esc_attr($group); ?>" /><?php endif; ?>
                    <label class="pk-label" for="sort">Sort</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        <option value="title" <?php selected($sort, 'title'); ?>>Name A–Z</option>
                        <option value="newest" <?php selected($sort, 'newest'); ?>>Newest first</option>
                        <option value="oldest" <?php selected($sort, 'oldest'); ?>>Oldest first</option>
                        <option value="updated" <?php selected($sort, 'updated'); ?>>Recently updated</option>
                    </select>
                </form>
            </div>

            <p class="pk-fine"><?php echo count($products); ?> structures on this page</p>

            <div class="pk-cards pk-cards-4" style="margin-top:18px">
                <?php foreach ($products as $p) :
                    $desc = get_post_meta($p->ID, '_packrivo_description', true);
                    $cover = get_post_meta($p->ID, '_packrivo_article_cover_url', true);
                    $thumb = get_the_post_thumbnail_url($p, 'medium');
                    $img = $thumb ?: $cover;
                    ?>
                    <a class="pk-card" href="<?php echo esc_url(get_permalink($p)); ?>">
                        <?php if ($img) : ?>
                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($p->post_title); ?>" loading="lazy" />
                        <?php else : ?>
                            <span class="pk-card-placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span class="pk-card-body">
                            <span class="pk-card-title"><?php echo esc_html(wp_strip_all_tags($p->post_title)); ?></span>
                            <span class="pk-card-text"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($desc ?: $p->post_content), 18)); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="pk-pager">
                <?php
                $pages = (int) $q->max_num_pages;
                for ($i = 1; $i <= min($pages, 12); $i++) {
                    $url = add_query_arg(['group' => $group, 'sort' => $sort, 'pg' => $i], $base);
                    printf('<a class="pk-chip %s" href="%s">%d</a>', $i === $paged ? 'is-active' : '', esc_url($url), $i);
                }
                ?>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
