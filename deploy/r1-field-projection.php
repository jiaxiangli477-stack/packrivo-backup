<?php
/** Plugin Name: Packrivo R1 exact public metadata projections */
function r1_field_project($slug,$value,$pages){return is_string($value)&&isset($pages[$slug][$value]) ? $pages[$slug][$value] : $value;}
function r1_field_h1_project($slug,$html,$pages){
 if(!is_string($html)||!isset($pages[$slug])||$slug!=='jewelry-box-manufacturer')return $html;
 return preg_replace_callback('~(<h1\b[^>]*>)(.*?)(</h1\s*>)~is',static function($m)use($slug,$pages){return $m[1].r1_field_project($slug,$m[2],$pages).$m[3];},$html);
}
if(defined('R1_FIELD_TEST_ONLY')&&R1_FIELD_TEST_ONLY)return;
if(!defined('ABSPATH'))return;
function r1_field_pages(){static $pages=null;if($pages!==null)return $pages;$p=__DIR__.'/r1-500-field-map.json';$j=is_readable($p)?json_decode((string)file_get_contents($p),true):null;$pages=is_array($j)&&($j['version']??null)===1&&is_array($j['pages']??null)?$j['pages']:[];return $pages;}
function r1_field_slug(){if(!is_singular('page'))return '';return (string)get_post_field('post_name',(int)get_queried_object_id());}
function r1_field_filter($value){return r1_field_project(r1_field_slug(),$value,r1_field_pages());}
foreach(['wpseo_title','wpseo_metadesc','wpseo_opengraph_title','wpseo_opengraph_desc','wpseo_twitter_title','wpseo_twitter_description','pre_get_document_title'] as $hook)add_filter($hook,'r1_field_filter',20000);
// The native page template emits its stored content without the_content; buffer only this English page.
add_action('template_redirect',static function(){
 if(is_admin()||is_feed()||r1_field_slug()!=='jewelry-box-manufacturer'||(function_exists('packrivo_lang')&&packrivo_lang()!==null))return;
 ob_start(static function($html){return r1_field_h1_project('jewelry-box-manufacturer',$html,r1_field_pages());});
},-20);
