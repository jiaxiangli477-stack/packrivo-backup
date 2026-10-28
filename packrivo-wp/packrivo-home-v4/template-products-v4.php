<?php
/** Template Name: Products index (v4) */
get_header();
$sort = isset($_GET['sort']) && is_scalar($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'title';
$group = isset($_GET['group']) && is_scalar($_GET['group']) ? sanitize_key(wp_unslash($_GET['group'])) : 'all';
$sub = isset($_GET['sub']) && is_scalar($_GET['sub']) ? sanitize_key(wp_unslash($_GET['sub'])) : '';
$search = isset($_GET['q']) && is_scalar($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$paged = max(1, (int)get_query_var('paged'), isset($_GET['pg']) && is_scalar($_GET['pg']) ? (int)$_GET['pg'] : 1);
$groups = [
 'jewelry'=>['label'=>'Jewelry boxes','match'=>['jewelry','ring','watch','necklace']],
 'gift'=>['label'=>'Gift & folding boxes','match'=>['gift','folding','carton','rigid','magnetic']],
 'mailer'=>['label'=>'Mailers & shippers','match'=>['mailer','corrugated','shipping','tray']],
 'bag'=>['label'=>'Bags & pouches','match'=>['bag','pouch','tote']],
 'label'=>['label'=>'Labels','match'=>['label','sticker']],
 'insert'=>['label'=>'Inserts','match'=>['insert','inlay','divider','pulp']],
];
if ($group !== 'all' && !isset($groups[$group])) $group = 'all';
$subs = [
 'ring-boxes'=>['Ring boxes',['ring']], 'velvet-jewelry-boxes'=>['Velvet jewelry boxes',['velvet']],
 'necklace-boxes'=>['Necklace boxes',['necklace']], 'jewelry-pouches'=>['Jewelry pouches',['pouch']],
 'paper-jewelry-boxes'=>['Paper jewelry boxes',['paper','kraft']], 'drawer-jewelry-boxes'=>['Drawer jewelry boxes',['drawer']],
 'lid-and-base-jewelry-boxes'=>['Lid and base jewelry boxes',['lid-and-base']], 'jewelry-gift-boxes'=>['Jewelry gift boxes',['gift']],
 'leather-jewelry-boxes'=>['Leather jewelry boxes',['leather','wooden']],
];
$available_subs=[];
foreach ($subs as $key=>$entry) { $page=get_page_by_path('jewelry-boxes/'.$key); if ($page && $page->post_status==='publish') $available_subs[$key]=$entry; }
if ($group!=='jewelry' || !isset($available_subs[$sub])) $sub='';
$ids=get_posts(['post_type'=>'page','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_packrivo_remediation_product','no_found_rows'=>true]);
$match_words=static function($slug,$words){foreach($words as $word) if (preg_match('~\\b'.preg_quote($word,'~').'(?:s|es)?\\b~i',$slug)) return true; return false;};
$ids=array_values(array_filter($ids,static function($id) use ($group,$groups,$sub,$available_subs,$search,$match_words) {
 $p=get_post($id); $slug=$p->post_name;
 if ($group!=='all' && !$match_words($slug,$groups[$group]['match'])) return false;
 if ($sub && !$match_words($slug,$available_subs[$sub][1])) return false;
 return $search==='' || stripos($p->post_title.' '.get_post_meta($id,'_packrivo_description',true),$search)!==false;
}));
$prices=[];
if ($sort==='price-asc' || $sort==='price-desc') foreach ($ids as $id) $prices[$id]=pk_from_price($id);
usort($ids,static function($a,$b) use ($sort,$prices) {
 if ($sort==='price-asc' || $sort==='price-desc') {
  $pa=$prices[$a]; $pb=$prices[$b];
  if ($pa===null && $pb!==null) return 1; if ($pb===null && $pa!==null) return -1;
  if ($pa!==null && $pb!==null && $pa!=$pb) return ($sort==='price-asc' ? 1 : -1)*($pa <=> $pb);
 } elseif (in_array($sort,['newest','oldest','updated'],true)) {
  $field=$sort==='updated'?'post_modified':'post_date'; $cmp=strcmp(get_post_field($field,$a),get_post_field($field,$b)); if($cmp) return $sort==='oldest' ? $cmp : -$cmp;
 }
 return strcasecmp(get_the_title($a),get_the_title($b)) ?: ($a <=> $b);
});
$q=new WP_Query(['post_type'=>'page','post_status'=>'publish','posts_per_page'=>24,'paged'=>$paged,'post__in'=>$ids ?: [0],'orderby'=>'post__in']);
$base=get_permalink(get_queried_object_id());
$contact=get_page_by_path('contact'); $contact_url=$contact && $contact->post_status==='publish' ? get_permalink($contact) : home_url('/get-a-quote/');
$params=array_filter(['group'=>$group==='all'?'':$group,'sub'=>$sub,'q'=>$search,'sort'=>$sort==='title'?'':$sort],static function($x){return $x!=='';});
?>
<main id="main" class="pk-main">
 <section class="pk-hero" style="padding:56px 0 40px"><div class="pk-wrap"><p class="pk-eyebrow">Catalogue · <?php echo count($ids); ?> structures</p><h1 class="pk-display" style="font-size:clamp(2.2rem,4.4vw,3.4rem)">Every structure we can source</h1><p>MOQ 500 · Dielines included · Live prices in the quote builder</p><p class="pk-lede">Filter by family, sort by newest or name, and open any structure for the specification, price range and dieline notes.</p></div></section>
 <section class="pk-section" style="padding-top:0"><div class="pk-wrap"><div class="pk-filterbar"><div class="pk-chips">
 <a class="pk-chip <?php echo $group==='all'?'is-active':''; ?>" href="<?php echo esc_url(add_query_arg(['sort'=>$sort],$base)); ?>">All</a>
 <?php foreach($groups as $key=>$g): ?><a class="pk-chip <?php echo $group===$key?'is-active':''; ?>" href="<?php echo esc_url(add_query_arg(['group'=>$key,'sort'=>$sort],$base)); ?>"><?php echo esc_html($g['label']); ?></a><?php endforeach; ?></div>
 <?php if($group==='jewelry' && $available_subs): ?><div class="pk-chips"><?php foreach($available_subs as $key=>$entry): ?><a class="pk-chip <?php echo $sub===$key?'is-active':''; ?>" href="<?php echo esc_url(add_query_arg(['group'=>'jewelry','sub'=>$key],$base)); ?>"><?php echo esc_html($entry[0]); ?></a><?php endforeach; ?></div><?php endif; ?>
 <form class="pk-sort" method="get" action="<?php echo esc_url($base); ?>"><?php if($group!=='all'): ?><input type="hidden" name="group" value="<?php echo esc_attr($group); ?>"><?php endif; ?><?php if($sub): ?><input type="hidden" name="sub" value="<?php echo esc_attr($sub); ?>"><?php endif; ?><label for="pk-search">Search</label><input type="search" name="q" id="pk-search" value="<?php echo esc_attr($search); ?>"><label for="sort">Sort</label><select id="sort" name="sort"><option value="title" <?php selected($sort,'title'); ?>>Name A–Z</option><option value="newest" <?php selected($sort,'newest'); ?>>Newest first</option><option value="oldest" <?php selected($sort,'oldest'); ?>>Oldest first</option><option value="updated" <?php selected($sort,'updated'); ?>>Recently updated</option><option value="price-asc" <?php selected($sort,'price-asc'); ?>>Price: low to high</option><option value="price-desc" <?php selected($sort,'price-desc'); ?>>Price: high to low</option></select><button type="submit">Search</button></form></div>
 <p class="pk-fine"><?php echo count($ids); ?> structures<?php if($search!==''): ?> matching “<?php echo esc_html($search); ?>”<?php endif; ?></p>
 <?php if($q->posts): ?><div class="pk-cards pk-cards-4" style="margin-top:18px"><?php foreach($q->posts as $p) get_template_part('template-parts/product-card',null,['product'=>$p,'group'=>$group==='all'?'':$groups[$group]['label']]); ?></div><?php else: ?><div class="pk-empty"><p>Nothing matches that yet.</p><p>Describe what you need and we will source it.</p><a class="pk-btn" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Describe your pack</a></div><?php endif; ?>
 <div class="pk-pager"><?php if($q->max_num_pages>1) echo wp_kses_post(paginate_links(['base'=>esc_url_raw(add_query_arg(array_merge($params,['pg'=>'%#%']),$base)),'format'=>'','current'=>$paged,'total'=>$q->max_num_pages,'end_size'=>1,'mid_size'=>2])); ?></div>
 <section class="pk-section"><h2 class="pk-h2">Need something not on this page?</h2><p>Counter displays, rigid trays, book-style boxes — send a photo of the product and a rough volume, and we will come back with a structure and a price range.</p><a class="pk-btn" href="<?php echo esc_url($contact_url); ?>">Send a brief</a> <a class="pk-btn-ghost" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Start from a price</a></section>
 </div></section>
</main><?php get_footer(); ?>
