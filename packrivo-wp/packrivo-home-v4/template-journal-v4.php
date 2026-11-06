<?php
/**
 * Template Name: Journal index (v4)
 *
 * 版式借自 v2 原型 Work 页：单列横向卡片，左图右字。卡片上的每个字段都来自文章本身
 * （分类、标题、摘要、阅读时长、日期、封面），没有额外编的字。
 * 11 个语种共用：min read 与日期先查 i18n 字典，查不到再用本文件里的语言表兜底。
 */
get_header();

$topic = isset($_GET['topic']) && is_scalar($_GET['topic']) ? sanitize_title(wp_unslash($_GET['topic'])) : '';
$sort  = isset($_GET['sort']) && is_scalar($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'newest';
$paged = max(1, (int) get_query_var('paged'), isset($_GET['pg']) && is_scalar($_GET['pg']) ? (int) $_GET['pg'] : 1);

$args = [
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 12,
    'paged'               => $paged,
    'orderby'             => 'date',
    'order'               => $sort === 'oldest' ? 'ASC' : 'DESC',
    'ignore_sticky_posts' => true,
];
if ($topic) $args['category_name'] = $topic;
$q = new WP_Query($args);

$default_cat = (int) get_option('default_category');
$cats = get_categories(['hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC']);
// 默认分类（未分类）不进口径按钮：它只是文章还没归档的临时状态，不是一个品类。
$cats = array_values(array_filter($cats, static function ($c) use ($default_cat) {
    return (int) $c->term_id !== $default_cat && $c->slug !== 'uncategorized';
}));

$upper = static function ($s) {
    // 分类名在库里是 Structures &amp; fit 这种实体写法，先解码再大写，避免 esc_html 之后变成 &amp;AMP;
    $s = html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
};
$posts_page = (int) get_option('page_for_posts');
$base = $posts_page ? get_permalink($posts_page) : home_url('/blog/');
$total_published = (int) wp_count_posts('post')->publish;

$read_time = static function ($post) {
    return max(1, (int) ceil(str_word_count(wp_strip_all_tags(strip_shortcodes($post->post_content))) / 230));
};
$cover_of = static function ($post) {
    $cover = get_the_post_thumbnail_url($post, 'large');
    if (!$cover) $cover = (string) get_post_meta($post->ID, '_packrivo_article_cover_url', true);
    return $cover;
};
$cats_of = static function ($post) use ($default_cat) {
    $list = get_the_category($post->ID);
    $list = array_values(array_filter($list, static function ($c) use ($default_cat) {
        return (int) $c->term_id !== $default_cat && $c->slug !== 'uncategorized';
    }));
    if (!$list) {
        $list = get_the_category($post->ID);
    }
    return $list;
};

/* ---- min read / 日期：先查字典，再走语言表 ---- */
$lang = function_exists('packrivo_lang') ? packrivo_lang() : null;
$lookup = static function ($text) {
    if (function_exists('packrivo_i18n_lookup') && function_exists('packrivo_lang') && packrivo_lang() !== null) {
        $hit = packrivo_i18n_lookup($text, ['text', 'block', 'attr']);
        if ($hit !== null && $hit !== '') return $hit;
    }
    return null;
};
// 每种语言：阅读时长单位、月份缩写、日期版式
$journal_locale = [
    'ar'    => ['unit' => 'دقيقة قراءة', 'months' => ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'], 'pattern' => '{d} {M} {Y}'],
    'de'    => ['unit' => 'Min. Lesezeit', 'months' => ['Jan.','Feb.','März','Apr.','Mai','Juni','Juli','Aug.','Sept.','Okt.','Nov.','Dez.'], 'pattern' => '{d}. {M} {Y}'],
    'es'    => ['unit' => 'min de lectura', 'months' => ['ene','feb','mar','abr','may','jun','jul','ago','sept','oct','nov','dic'], 'pattern' => '{d} {M} {Y}'],
    'fr'    => ['unit' => 'min de lecture', 'months' => ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'], 'pattern' => '{d} {M} {Y}'],
    'it'    => ['unit' => 'min di lettura', 'months' => ['gen','feb','mar','apr','mag','giu','lug','ago','set','ott','nov','dic'], 'pattern' => '{d} {M} {Y}'],
    'ja'    => ['unit' => '分で読めます', 'months' => ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'], 'pattern' => '{Y}年{M}{d}日'],
    'ko'    => ['unit' => '분 소요', 'months' => ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'], 'pattern' => '{Y}년 {M} {d}일'],
    'nl'    => ['unit' => 'min leestijd', 'months' => ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'], 'pattern' => '{d} {M} {Y}'],
    'pt'    => ['unit' => 'min de leitura', 'months' => ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'], 'pattern' => '{d} {M} {Y}'],
    'zh-CN' => ['unit' => '分钟阅读', 'months' => ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'], 'pattern' => '{Y}年{M}{d}日'],
    'zh-TW' => ['unit' => '分鐘閱讀', 'months' => ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'], 'pattern' => '{Y}年{M}{d}日'],
];
$locale = ($lang && isset($journal_locale[$lang])) ? $journal_locale[$lang] : null;

$min_read_label = static function ($minutes) use ($locale) {
    // 词典优先：'{N} min read' 模板键 + 数字回填（functions.php: pkv4_i18n_t）。
    $raw = $minutes . ' min read';
    $out = pkv4_i18n_t($raw, ['text', 'block']);
    if ($out !== $raw) return $out;
    $unit = $locale ? $locale['unit'] : 'min read';
    return $minutes . ' ' . $unit;
};
$date_label = static function ($post) use ($locale) {
    $ts = (int) get_post_time('U', true, $post);
    // 词典优先：整串 → 月名键替换（functions.php: pkv4_i18n_date），语言表兜底版式。
    return pkv4_i18n_date($ts, $locale ? $locale['pattern'] : null, $locale ? $locale['months'] : null);
};
?>
<main id="main" class="pk-main">
<section class="pk-hero" style="padding:56px 0 36px">
  <div class="pk-wrap">
    <p class="pk-eyebrow"><?php echo esc_html(pkv4_i18n_t('Journal · ' . (int) $total_published . ' articles')); ?></p>
    <h1 class="pk-display" style="font-size:clamp(2.2rem,4.4vw,3.4rem)"><?php echo esc_html(pkv4_i18n_t('Packaging notes from the floor')); ?></h1>
    <p class="pk-lede"><?php echo esc_html(pkv4_i18n_t('Supplier briefs, material notes and pricing explainers for small brands sourcing custom packaging.')); ?></p>
  </div>
</section>
<section class="pk-section" style="padding-top:0">
  <div class="pk-wrap">
    <div class="pk-filterbar">
      <div class="pk-chips">
        <a class="pk-chip <?php echo !$topic ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['sort' => $sort], $base)); ?>">All <span><?php echo (int) $total_published; ?></span></a>
        <?php foreach ($cats as $c) : ?>
          <a class="pk-chip <?php echo $topic === $c->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['topic' => $c->slug, 'sort' => $sort], $base)); ?>"><?php echo esc_html($c->name); ?> <span><?php echo (int) $c->count; ?></span></a>
        <?php endforeach; ?>
      </div>
      <?php if (pk31b_is_english()) : ?><div class="pk-chips"><?php foreach (pk31b_topics() as $pk_topic) : ?><a class="pk-chip" href="<?php echo esc_url(pk31b_topic_url($pk_topic)); ?>"><?php echo esc_html($pk_topic->name); ?></a><?php endforeach; ?></div><?php endif; ?>
      <form class="pk-sort" method="get" action="<?php echo esc_url($base); ?>">
        <?php if ($topic) : ?><input type="hidden" name="topic" value="<?php echo esc_attr($topic); ?>"><?php endif; ?>
        <label for="journal-sort">Sort</label>
        <select id="journal-sort" name="sort">
          <option value="newest" <?php selected($sort, 'newest'); ?>>Newest first</option>
          <option value="oldest" <?php selected($sort, 'oldest'); ?>>Oldest first</option>
        </select>
        <button type="submit">Sort</button>
      </form>
    </div>

    <div class="pk-jcards">
      <?php if ($q->have_posts()) : ?>
        <?php while ($q->have_posts()) : $q->the_post(); $post_obj = get_post(); $cover = $cover_of($post_obj); $post_cats = $cats_of($post_obj); $primary = $post_cats ? $post_cats[0] : null; ?>
          <article class="pk-jcard">
            <a class="pk-jcard-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
              <?php if ($cover) : ?>
                <img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy" width="640" height="400">
              <?php else : ?>
                <span class="pk-jcard-media-blank"></span>
              <?php endif; ?>
              <?php if ($primary) : ?>
                <span class="pk-jcard-badge"><?php echo esc_html($upper($primary->name) . ' · ' . get_the_date('Y')); ?></span>
              <?php endif; ?>
            </a>
            <div class="pk-jcard-body">
              <?php if ($primary) : ?><p class="pk-jcard-kicker"><?php echo esc_html($upper($primary->name)); ?></p><?php endif; ?>
              <h2 class="pk-jcard-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
              <p class="pk-jcard-excerpt"><?php echo esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 26, '…')); ?></p>
              <p class="pk-jcard-meta"><?php echo esc_html($min_read_label($read_time($post_obj)) . ' · ' . $date_label($post_obj)); ?></p>
            </div>
          </article>
        <?php endwhile; ?>
      <?php else : ?>
        <p class="pk-lede"><?php echo esc_html(pkv4_i18n_t('No articles in this topic yet.')); ?></p>
      <?php endif; ?>
      <?php wp_reset_postdata(); ?>
    </div>

    <div class="pk-pager">
      <?php
      if ($q->max_num_pages > 1) {
          echo wp_kses_post(paginate_links([
              'base'    => esc_url_raw(add_query_arg(['pg' => '%#%', 'topic' => $topic, 'sort' => $sort], $base)),
              'format'  => '',
              'current' => $paged,
              'total'   => $q->max_num_pages,
              'end_size' => 1,
              'mid_size' => 2,
          ]));
      }
      ?>
    </div>
  </div>
</section>
</main>
<?php get_footer(); ?>
