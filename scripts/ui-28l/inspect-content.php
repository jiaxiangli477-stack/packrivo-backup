<?php
require '/var/www/html/wp-load.php';
foreach (['inquiry','jewelry-boxes'] as $slug) {
 $p=get_page_by_path($slug); if(!$p){echo "$slug: MISSING\n";continue;}
 $GLOBALS['post']=$p; setup_postdata($p); $stored=$p->post_content; $content=get_the_content(); $filtered=apply_filters('the_content',$content);
 $stats=function($s){$result=['bytes'=>strlen($s)];foreach(['form','select','input','option','style','table'] as $tag)$result[$tag]=preg_match_all('~<'.$tag.'\b~i',$s);$result['css_marker']=substr_count($s,'#jewelry-supplier-references');return $result;};
 echo json_encode(['slug'=>$slug,'id'=>$p->ID,'native'=>get_post_meta($p->ID,'_packrivo_native_page',true),'stored'=>$stats($stored),'get_the_content'=>$stats($content),'filtered'=>$stats($filtered)],JSON_UNESCAPED_SLASHES)."\n";
}
