<?php
/** Build a read-only route list for the 12-language R1 HTML field audit. */
function r1_build_paths($localizable,$english,$langs){
 $allowed=['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW'];$out=[];
 foreach($langs as $lang)if(!in_array($lang,$allowed,true))throw new InvalidArgumentException('invalid language');
 foreach(array_merge($localizable,$english) as $p)if(!is_string($p)||!preg_match('~^/(?!/)[^\r\n#]*$~',$p)||strpos($p,'://')!==false||strpos($p,'..')!==false)throw new InvalidArgumentException('invalid path');
 foreach($english as $p)$out[$p]=true;
 foreach($localizable as $p){$out[$p]=true;foreach($langs as $lang)$out['/'.$lang.($p==='/'?'/':$p)]=true;}
 $result=array_keys($out);sort($result,SORT_STRING);return $result;
}
function r1_scan_blog_pages($base,$count){$total=(int)ceil(max(0,(int)$count)/12);$paths=[];for($i=2;$i<=$total;$i++)$paths[]=$base.(strpos($base,'?')===false?'?':'&').'pg='.$i;return $paths;}
if(defined('R1_PATHS_TEST_ONLY')&&R1_PATHS_TEST_ONLY)return;
if(!defined('WP_CLI')||!WP_CLI)exit(1);
try{
 $file=$args[0]??'';$loc=is_readable($file)?json_decode((string)file_get_contents($file),true):null;if(!is_array($loc)||!$loc)throw new RuntimeException('invalid paths.json');
 $english=['/','/blog/'];$posts=get_posts(['post_type'=>['post','page'],'post_status'=>'publish','posts_per_page'=>-1,'has_password'=>false,'fields'=>'ids','suppress_filters'=>false]);if(!is_array($posts)||!$posts)throw new RuntimeException('no published posts/pages');
 foreach($posts as $id){$u=parse_url(get_permalink((int)$id));if(!is_array($u)||empty($u['path']))continue;$english[]=$u['path'].(isset($u['query'])?'?'.$u['query']:'');}
 if(function_exists('pk31b_topics')&&function_exists('pk31b_topic_url'))foreach(pk31b_topics() as $term){$u=parse_url(pk31b_topic_url($term));if(is_array($u)&&!empty($u['path']))$english[]=$u['path'];}
 $q=new WP_Query(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'paged'=>1,'fields'=>'ids','ignore_sticky_posts'=>true,'update_post_meta_cache'=>false,'update_post_term_cache'=>false]);$blog=parse_url(get_permalink((int)get_option('page_for_posts'))?:home_url('/blog/'));if(!is_array($blog)||empty($blog['path']))throw new RuntimeException('invalid blog permalink');$base=$blog['path'].(isset($blog['query'])?'?'.$blog['query']:'');foreach(r1_scan_blog_pages($base,(int)$q->found_posts) as $path)$english[]=$path;
 $paths=r1_build_paths($loc,$english,['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']);WP_CLI::line(wp_json_encode($paths,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}catch(Throwable $e){WP_CLI::error($e->getMessage());}
