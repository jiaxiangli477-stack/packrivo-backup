<?php
/** Template Name: Journal index (v4) */
get_header();
$topic=isset($_GET['topic']) && is_scalar($_GET['topic']) ? sanitize_title(wp_unslash($_GET['topic'])) : '';
$sort=isset($_GET['sort']) && is_scalar($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'newest';
$paged=max(1,(int)get_query_var('paged'),isset($_GET['pg']) && is_scalar($_GET['pg']) ? (int)$_GET['pg'] : 1);
$args=['post_type'=>'post','post_status'=>'publish','posts_per_page'=>12,'paged'=>$paged,'orderby'=>'date','order'=>$sort==='oldest'?'ASC':'DESC','ignore_sticky_posts'=>true];
if($topic) $args['category_name']=$topic;
$q=new WP_Query($args);
// 分类按钮：默认分类（未分类/uncategorized）即使还有文章也不显示（29x 任务单第 4 条）。
$default_cat=(int)get_option('default_category',1);
$cats=array_values(array_filter(get_categories(['hide_empty'=>true,'orderby'=>'count','order'=>'DESC']),static function($c)use($default_cat){return (int)$c->term_id!==$default_cat && $c->slug!=='uncategorized';}));
$posts_page=(int)get_option('page_for_posts'); $base=$posts_page ? get_permalink($posts_page) : home_url('/blog/');
$read_time=static function($post){return max(1,(int)ceil(str_word_count(wp_strip_all_tags(strip_shortcodes($post->post_content)))/230));};
$cat_label=static function($post){$cats=get_the_category($post->ID);return $cats ? $cats[0]->name : '';};
// "min read" 跟着翻译词典走；词典没有这个键就回退英文。
$min_read='min read';
if(function_exists('packrivo_lang') && packrivo_lang()!==null && function_exists('packrivo_i18n_lookup')){
 $tr=packrivo_i18n_lookup('min read',['text','block']);
 if(is_string($tr) && $tr!=='') $min_read=$tr;
}
$cover_fallback=get_stylesheet_directory_uri().'/assets/img/gift.webp';
?>
<main id="main" class="pk-main"><section class="pk-hero" style="padding:56px 0 36px"><div class="pk-wrap"><p class="pk-eyebrow">Journal · <?php echo (int)wp_count_posts('post')->publish; ?> articles</p><h1 class="pk-display" style="font-size:clamp(2.2rem,4.4vw,3.4rem)">Packaging notes from the floor</h1><p class="pk-lede">Supplier briefs, material notes and pricing explainers for small brands sourcing custom packaging.</p></div></section>
<section class="pk-section" style="padding-top:0"><div class="pk-wrap"><div class="pk-filterbar"><div class="pk-chips"><a class="pk-chip <?php echo !$topic?'is-active':''; ?>" href="<?php echo esc_url(add_query_arg(['sort'=>$sort],$base)); ?>">All <span><?php echo (int)wp_count_posts('post')->publish; ?></span></a><?php foreach($cats as $c): ?><a class="pk-chip <?php echo $topic===$c->slug?'is-active':''; ?>" href="<?php echo esc_url(add_query_arg(['topic'=>$c->slug,'sort'=>$sort],$base)); ?>"><?php echo esc_html($c->name); ?> <span><?php echo (int)$c->count; ?></span></a><?php endforeach; ?></div><form class="pk-sort" method="get" action="<?php echo esc_url($base); ?>"><?php if($topic): ?><input type="hidden" name="topic" value="<?php echo esc_attr($topic); ?>"><?php endif; ?><label for="journal-sort">Sort</label><select id="journal-sort" name="sort"><option value="newest" <?php selected($sort,'newest'); ?>>Newest first</option><option value="oldest" <?php selected($sort,'oldest'); ?>>Oldest first</option></select><button type="submit">Sort</button></form></div>
<?php /* 29x：去掉顶部置顶大文章，所有文章统一用横向案例卡片。 */ ?>
<div class="pk-case-list"><?php if($q->have_posts()): while($q->have_posts()): $q->the_post(); $post_obj=get_post(); $cover=get_the_post_thumbnail_url($post_obj,'medium_large') ?: get_post_meta($post_obj->ID,'_packrivo_article_cover_url',true); if(!$cover) $cover=$cover_fallback; $cat=$cat_label($post_obj); ?><article class="pk-case-card"><a class="pk-case-cover" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php if($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy"><?php endif; ?><?php if($cat): ?><span class="pk-case-tag"><?php echo esc_html(strtoupper($cat).' · '.get_the_date('Y')); ?></span><?php endif; ?></a><div class="pk-case-body"><?php if($cat): ?><p class="pk-case-cat"><?php echo esc_html($cat); ?></p><?php endif; ?><h2 class="pk-case-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p class="pk-case-excerpt"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p><p class="pk-case-meta pk-mono"><?php echo esc_html($read_time($post_obj).' '.$min_read.' · '.get_the_date('M j, Y')); ?></p></div></article><?php endwhile; else: ?><p class="pk-lede">No articles in this topic yet.</p><?php endif; wp_reset_postdata(); ?></div>
<div class="pk-pager"><?php if($q->max_num_pages>1) echo wp_kses_post(paginate_links(['base'=>esc_url_raw(add_query_arg(['pg'=>'%#%','topic'=>$topic,'sort'=>$sort],$base)),'format'=>'','current'=>$paged,'total'=>$q->max_num_pages,'end_size'=>1,'mid_size'=>2])); ?></div></div></section></main><?php get_footer(); ?>
