<?php
/** Template Name: Journal index (v4) */
get_header();
$topic=isset($_GET['topic']) && is_scalar($_GET['topic']) ? sanitize_title(wp_unslash($_GET['topic'])) : '';
$sort=isset($_GET['sort']) && is_scalar($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'newest';
$paged=max(1,(int)get_query_var('paged'),isset($_GET['pg']) && is_scalar($_GET['pg']) ? (int)$_GET['pg'] : 1);
$featured=null;
if (!$topic) {
 $sticky=array_filter(array_map('intval',(array)get_option('sticky_posts',[])));
 if($sticky) { $found=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>1,'post__in'=>$sticky,'orderby'=>'date','order'=>'DESC','ignore_sticky_posts'=>true]); $featured=$found[0] ?? null; }
 if(!$featured) { $found=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>1,'orderby'=>'date','order'=>'DESC','ignore_sticky_posts'=>true]); $featured=$found[0] ?? null; }
}
$args=['post_type'=>'post','post_status'=>'publish','posts_per_page'=>12,'paged'=>$paged,'orderby'=>'date','order'=>$sort==='oldest'?'ASC':'DESC','ignore_sticky_posts'=>true];
if($topic) $args['category_name']=$topic;
if($featured) $args['post__not_in']=[$featured->ID];
$q=new WP_Query($args);
$cats=get_categories(['hide_empty'=>true,'orderby'=>'count','order'=>'DESC']);
$posts_page=(int)get_option('page_for_posts'); $base=$posts_page ? get_permalink($posts_page) : home_url('/blog/');
$read_time=static function($post){return max(1,(int)ceil(str_word_count(wp_strip_all_tags(strip_shortcodes($post->post_content)))/230));};
$cat_label=static function($post){$cats=get_the_category($post->ID);return $cats ? $cats[0]->name : '';};
?>
<main id="main" class="pk-main"><section class="pk-hero" style="padding:56px 0 36px"><div class="pk-wrap"><p class="pk-eyebrow">Journal · <?php echo (int)wp_count_posts('post')->publish; ?> articles</p><h1 class="pk-display" style="font-size:clamp(2.2rem,4.4vw,3.4rem)">Packaging notes from the floor</h1><p class="pk-lede">Supplier briefs, material notes and pricing explainers for small brands sourcing custom packaging.</p></div></section>
<section class="pk-section" style="padding-top:0"><div class="pk-wrap"><div class="pk-filterbar"><div class="pk-chips"><a class="pk-chip <?php echo !$topic?'is-active':''; ?>" href="<?php echo esc_url(add_query_arg(['sort'=>$sort],$base)); ?>">All <span><?php echo (int)wp_count_posts('post')->publish; ?></span></a><?php foreach($cats as $c): ?><a class="pk-chip <?php echo $topic===$c->slug?'is-active':''; ?>" href="<?php echo esc_url(add_query_arg(['topic'=>$c->slug,'sort'=>$sort],$base)); ?>"><?php echo esc_html($c->name); ?> <span><?php echo (int)$c->count; ?></span></a><?php endforeach; ?></div><form class="pk-sort" method="get" action="<?php echo esc_url($base); ?>"><?php if($topic): ?><input type="hidden" name="topic" value="<?php echo esc_attr($topic); ?>"><?php endif; ?><label for="journal-sort">Sort</label><select id="journal-sort" name="sort"><option value="newest" <?php selected($sort,'newest'); ?>>Newest first</option><option value="oldest" <?php selected($sort,'oldest'); ?>>Oldest first</option></select><button type="submit">Sort</button></form></div>
<?php if($featured && $paged===1 && !$topic):
 $cover=get_the_post_thumbnail_url($featured,'large') ?: get_post_meta($featured->ID,'_packrivo_article_cover_url',true);
 preg_match_all('~<h2\b[^>]*>(.*?)</h2>~is',$featured->post_content,$matches); $headings=array_slice($matches[1],0,5);
?><article class="pk-featured"><div><p class="pk-eyebrow">Featured · <?php echo esc_html($cat_label($featured)); ?> · <?php echo $read_time($featured); ?> min read</p><h2 class="pk-h2"><?php echo esc_html(get_the_title($featured)); ?></h2><p><?php echo esc_html(wp_trim_words(get_the_excerpt($featured),40)); ?></p><a class="pk-more" href="<?php echo esc_url(get_permalink($featured)); ?>">Read the guide →</a></div><div><?php if($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(get_the_title($featured)); ?>" loading="eager"><?php endif; ?><div class="pk-guide"><strong>In this guide</strong><ul><?php foreach($headings as $heading): ?><li><?php echo esc_html(wp_strip_all_tags($heading)); ?></li><?php endforeach; ?></ul></div></div></article><?php endif; ?>
<div class="pk-posts pk-journal-grid"><?php if($q->have_posts()): while($q->have_posts()): $q->the_post(); $post_obj=get_post(); $cover=get_the_post_thumbnail_url($post_obj,'medium_large') ?: get_post_meta($post_obj->ID,'_packrivo_article_cover_url',true); ?><a class="pk-post" href="<?php the_permalink(); ?>"><?php if($cover): ?><span class="pk-post-thumb"><img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy"></span><?php endif; ?><span class="pk-post-meta pk-mono"><?php echo esc_html($cat_label($post_obj).' · '.$read_time($post_obj).' min · '.get_the_date('M j, Y')); ?></span><span class="pk-post-title"><?php the_title(); ?></span><span class="pk-card-text pk-journal-excerpt"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></span></a><?php endwhile; else: ?><p class="pk-lede">No articles in this topic yet.</p><?php endif; wp_reset_postdata(); ?></div>
<div class="pk-pager"><?php if($q->max_num_pages>1) echo wp_kses_post(paginate_links(['base'=>esc_url_raw(add_query_arg(['pg'=>'%#%','topic'=>$topic,'sort'=>$sort],$base)),'format'=>'','current'=>$paged,'total'=>$q->max_num_pages,'end_size'=>1,'mid_size'=>2])); ?></div></div></section></main><?php get_footer(); ?>
