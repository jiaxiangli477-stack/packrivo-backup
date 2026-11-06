<?php
const R1_SITEMAP_TEST_ONLY=true;
require __DIR__.'/../deploy/r1-sitemap-fix.php';
$q="SELECT l.ID, post_title, post_content, post_name, post_parent, post_author, post_status, post_modified_gmt, post_date, post_date_gmt FROM (SELECT ID FROM wp_posts WHERE wp_posts.post_type = 'page' ORDER BY wp_posts.post_modified ASC LIMIT 100 OFFSET 0) o JOIN wp_posts l ON l.ID = o.ID";
$fixed=r1_stable_page_sitemap_query($q,'/page-sitemap.xml','wp_posts');
if(strpos($fixed,'ORDER BY wp_posts.post_modified ASC, wp_posts.ID ASC LIMIT 100')===false)exit(1);
if(r1_stable_page_sitemap_query($q,'/post-sitemap.xml','wp_posts')!==$q)exit(2);
if(r1_stable_page_sitemap_query($fixed,'/page-sitemap.xml','wp_posts')!==$fixed)exit(3);
if(r1_stable_page_sitemap_query(str_replace("post_type = 'page'","post_type = 'post'",$q),'/page-sitemap.xml','wp_posts')===$fixed)exit(4);
$v=r1_sitemap_merge_exclusions([11,22],[22,33]);if($v!==[11,22,33])exit(5);
echo "R1_SITEMAP_ORDER_TEST cases=5 PASS\n";
