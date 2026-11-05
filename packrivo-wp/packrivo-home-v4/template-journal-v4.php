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

$cats = get_categories(['hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC']);
// A renamed default category can be a real topic (for example, Guides).
$cats = array_values(array_filter($cats, static function ($c) {
    return $c->slug !== 'uncategorized';
}));

$posts_page = (int) get_option('page_for_posts');
$base = $posts_page ? get_permalink($posts_page) : home_url('/blog/');
$total_published = (int) wp_count_posts('post')->publish;

/* ---- min read / 日期：先查字典，再走语言表 ---- */
$lang = function_exists('packrivo_lang') ? packrivo_lang() : null;
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
<?php if (pk31b_is_english() && $cats) : ?>
<section class="pk33-topic-section" aria-labelledby="pk33-topics-heading">
  <div class="pk-wrap">
    <h2 id="pk33-topics-heading" class="pk-h2"><?php echo esc_html(pkv4_i18n_t('Topics', ['text', 'block'])); ?></h2>
    <div class="pk33-topic-grid"><?php foreach ($cats as $category) get_template_part('template-parts/journal-topic-card', null, ['term'=>$category]); ?></div>
  </div>
</section>
<?php endif; ?>
<section class="pk-section" style="padding-top:0">
  <div class="pk-wrap">
    <div class="pk-filterbar">
      <div class="pk-chips">
        <a class="pk-chip <?php echo !$topic ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['sort' => $sort], $base)); ?>">All <span><?php echo (int) $total_published; ?></span></a>
        <?php foreach ($cats as $c) : ?>
          <a class="pk-chip <?php echo $topic === $c->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(pk31b_is_english() ? pk31b_topic_url($c) : add_query_arg(['topic' => $c->slug, 'sort' => $sort], $base)); ?>"><?php echo esc_html(html_entity_decode($c->name, ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?> <span><?php echo (int) $c->count; ?></span></a>
        <?php endforeach; ?>
      </div>
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
        <?php while ($q->have_posts()) : $q->the_post(); get_template_part('template-parts/journal-card', null, ['post'=>get_post(), 'min_read_label'=>$min_read_label, 'date_label'=>$date_label]); endwhile; ?>
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
