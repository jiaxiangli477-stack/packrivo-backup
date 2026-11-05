<?php
/** Topic information comes only from the category and its published articles. */
$term = $args['term'] ?? null;
if (!$term || is_wp_error($term)) return;
$posts = get_posts(['post_type'=>'post', 'post_status'=>'publish', 'numberposts'=>1, 'category'=>$term->term_id, 'orderby'=>'date', 'order'=>'DESC']);
$featured = $posts ? $posts[0] : null;
$cover = $featured ? pk33_journal_cover($featured, 'medium_large') : '';
$description = trim(wp_strip_all_tags((string) $term->description));
if ($description === '' && $featured) $description = trim(wp_strip_all_tags(get_the_excerpt($featured)));
if ($description !== '') $description = wp_trim_words($description, 24, '…');
$tags = $featured ? get_the_tags($featured->ID) : [];
if (!is_array($tags)) $tags = [];
if (count($tags) < 3) {
    // Related topic names come from actual shared posts, not invented tags.
    $ids = get_posts(['post_type'=>'post', 'post_status'=>'publish', 'numberposts'=>-1, 'fields'=>'ids', 'category'=>$term->term_id, 'no_found_rows'=>true]);
    $related = $ids ? wp_get_object_terms($ids, 'category', ['fields'=>'all']) : [];
    if (is_array($related)) foreach ($related as $category) {
        if ((int) $category->term_id === (int) $term->term_id || $category->slug === 'uncategorized') continue;
        $exists = false;
        foreach ($tags as $tag) if ((string)$tag->name === (string)$category->name) { $exists = true; break; }
        if (!$exists) $tags[] = $category;
        if (count($tags) >= 3) break;
    }
}
$url = pk31b_topic_url($term);
$name = html_entity_decode($term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$article_count = sprintf(pkv4_i18n_t('%s articles', ['text', 'block']), number_format_i18n((int) $term->count));
?>
<article class="pk33-topic-card">
  <a href="<?php echo esc_url($url); ?>" class="pk33-topic-image" tabindex="-1" aria-hidden="true">
    <?php if ($cover) : ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy" width="640" height="400">
    <?php else : ?><span class="pk-jcard-media-blank"></span><?php endif; ?>
  </a>
  <div class="pk33-topic-body">
    <p class="pk33-topic-count"><?php echo esc_html($article_count); ?></p>
    <h3><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($name); ?></a></h3>
    <?php if ($description !== '') : ?><p class="pk33-topic-description"><?php echo esc_html($description); ?></p><?php endif; ?>
    <?php if ($tags) : ?><div class="pk33-topic-tags"><?php foreach (array_slice($tags, 0, 3) as $tag) : ?><span><?php echo esc_html(html_entity_decode($tag->name, ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></span><?php endforeach; ?></div><?php endif; ?>
  </div>
</article>
