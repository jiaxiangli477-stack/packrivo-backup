<?php
/** Run with wp eval-file. Outputs a read-only inventory as CSV to STDOUT. */
global $wpdb;
$posts = $wpdb->get_results("SELECT ID,post_name,post_status,post_title,post_content FROM {$wpdb->posts} WHERE post_type='page' AND post_name LIKE 'catalog-%' ORDER BY post_name");
$out = fopen('php://output', 'w');
fputcsv($out, ['id','slug','status','post_title','seo_title','meta_description','robots_noindex','word_count']);
foreach ($posts as $post) {
 $words = str_word_count(wp_strip_all_tags(strip_shortcodes($post->post_content)));
 fputcsv($out, [$post->ID,$post->post_name,$post->post_status,$post->post_title,get_post_meta($post->ID,'_yoast_wpseo_title',true),get_post_meta($post->ID,'_yoast_wpseo_metadesc',true),get_post_meta($post->ID,'_yoast_wpseo_meta-robots-noindex',true),$words]);
}
fclose($out);
