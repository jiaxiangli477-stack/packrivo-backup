<?php
/** Existing article content rendered in a reusable journal card. */
$article = get_post($args['post'] ?? 0);
if (!$article) return;
$cover = pk33_journal_cover($article, 'large');
$categories = get_the_category($article->ID);
$default_category = (int) get_option('default_category');
$named = array_values(array_filter($categories, static function ($category) use ($default_category) {
    return (int) $category->term_id !== $default_category && $category->slug !== 'uncategorized';
}));
$primary = $named ? $named[0] : ($categories ? $categories[0] : null);
$minutes = max(1, (int) ceil(str_word_count(wp_strip_all_tags(strip_shortcodes($article->post_content))) / 230));
$read_label = isset($args['min_read_label']) && is_callable($args['min_read_label'])
    ? $args['min_read_label']($minutes) : pkv4_i18n_t($minutes . ' min read', ['text', 'block']);
$date_label = isset($args['date_label']) && is_callable($args['date_label'])
    ? $args['date_label']($article) : get_the_date('M j, Y', $article);
$article_url = get_permalink($article);
$title = get_the_title($article);
$excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($article)), 26, '…');
?>
<article class="pk-jcard">
  <a class="pk-jcard-media" href="<?php echo esc_url($article_url); ?>" tabindex="-1" aria-hidden="true">
    <?php if ($cover) : ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy" width="640" height="400">
    <?php else : ?><span class="pk-jcard-media-blank"></span><?php endif; ?>
  </a>
  <div class="pk-jcard-body">
    <p class="pk-jcard-meta"><?php if ($primary) : ?><span class="pk-jcard-kicker"><?php echo esc_html(html_entity_decode($primary->name, ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></span><span aria-hidden="true"> · </span><?php endif; ?><?php echo esc_html($read_label . ' · ' . $date_label); ?></p>
    <h2 class="pk-jcard-title"><a href="<?php echo esc_url($article_url); ?>"><?php echo esc_html($title); ?></a></h2>
    <?php if ($excerpt !== '') : ?><p class="pk-jcard-excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
    <a class="pk33-journal-read" href="<?php echo esc_url($article_url); ?>"><?php echo esc_html(pkv4_i18n_t('Read', ['text', 'block'])); ?><span class="screen-reader-text"> <?php echo esc_html($title); ?></span> <span aria-hidden="true">→</span></a>
  </div>
</article>
