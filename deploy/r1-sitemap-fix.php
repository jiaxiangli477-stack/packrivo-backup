<?php
/** Plugin Name: Packrivo R1 page sitemap order and English topics */
function r1_stable_page_sitemap_query($query,$path,$table){
 if(!is_string($query)||!is_string($path)||!is_string($table)||!preg_match('~^/(?:page-sitemap(?:[0-9]+)?|(?:ar|de|es|fr|it|ja|ko|nl|pt|zh-CN|zh-TW)-sitemap)\.xml$~iD',$path))return $query;
 $fields='SELECT l.ID, post_title, post_content, post_name, post_parent, post_author, post_status, post_modified_gmt, post_date, post_date_gmt';$needle='ORDER BY '.$table.'.post_modified ASC LIMIT ';
 if(strpos($query,$fields)===false||strpos($query,$table.".post_type = 'page'")===false||strpos($query,'o JOIN '.$table.' l ON l.ID = o.ID')===false||substr_count($query,$needle)!==1)return $query;
 return str_replace($needle,'ORDER BY '.$table.'.post_modified ASC, '.$table.'.ID ASC LIMIT ',$query);
}
function r1_sitemap_merge_exclusions($ids,$more){return array_values(array_unique(array_merge($ids,$more)));}
function r1_blog_page_numbers($count,$per=12){$pages=(int)ceil(max(0,(int)$count)/max(1,(int)$per));return $pages<2?[]:range(2,$pages);}
if(defined('R1_SITEMAP_TEST_ONLY')&&R1_SITEMAP_TEST_ONLY)return;
if(!defined('ABSPATH'))return;
add_filter('query',static function($query){global $wpdb;return r1_stable_page_sitemap_query($query,parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH),$wpdb->posts??'');},10);
add_filter('wpseo_sitemap_post_type_first_links',static function($links,$type){
 if($type!=='page'||!is_array($links)||!function_exists('pk31b_topics')||!function_exists('pk31b_topic_url'))return $links;
 $terms=pk31b_topics();if(is_wp_error($terms)||!is_array($terms))return $links;
 $seen=[];foreach($links as $link)if(is_array($link)&&isset($link['loc']))$seen[$link['loc']]=true;
 foreach($terms as $term){if(!is_object($term)||empty($term->slug)||!preg_match('/^[a-z0-9-]+$/',$term->slug))continue;$url=pk31b_topic_url($term);if(!isset($seen[$url])){$links[]=['loc'=>$url];$seen[$url]=true;}}
 // Journal canonicals use ?pg=N and 12 published posts per page (same query as 31a).
 $query=new WP_Query(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'paged'=>1,'fields'=>'ids','ignore_sticky_posts'=>true,'update_post_meta_cache'=>false,'update_post_term_cache'=>false]);
 $base=get_permalink((int)get_option('page_for_posts'))?:home_url('/blog/');
 // Locale sitemap builds an internal page-provider view; add the English journal archive there only.
 $request=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);if(is_string($request)&&preg_match('~^/(?:ar|de|es|fr|it|ja|ko|nl|pt|zh-CN|zh-TW)-sitemap\.xml$~iD',$request)&&!isset($seen[$base])){$links[]=['loc'=>$base];$seen[$base]=true;}
 foreach(r1_blog_page_numbers((int)$query->found_posts) as $page){$url=add_query_arg('pg',$page,$base);if(!isset($seen[$url])){$links[]=['loc'=>$url];$seen[$url]=true;}}
 return $links;
},10,2);

// Preserve the three catalog omissions pending the separate 30a noindex review; posts and metadata are untouched.
add_filter('wpseo_exclude_from_sitemap_by_post_ids',static function($ids){
 if(!is_array($ids))return $ids;$found=[];foreach(['catalog-paper-box-o1cn019mnvjf1i','catalog-paper-box-o1cn01gehn2a1i','catalog-paper-box-o1cn01p56uj3qy'] as $slug){$page=get_page_by_path($slug,OBJECT,'page');if($page)$found[]=(int)$page->ID;}return r1_sitemap_merge_exclusions($ids,$found);
},20);
