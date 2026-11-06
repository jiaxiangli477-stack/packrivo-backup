<?php
/** Read-only snapshot of target fields and hashes of untouched fields. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
global $wpdb;
$map=$args[0] ?? ''; $out=$args[1] ?? '';
if (!is_readable($map) || $out==='') WP_CLI::error('Usage: probe MAP.csv OUTPUT.json');
$f=fopen($map,'rb');$head=fgetcsv($f,0,',','"','');
$head[0]=preg_replace('/^\xEF\xBB\xBF/','',$head[0]);$pos=array_search('url',$head,true);
if ($pos===false) WP_CLI::error('Missing URL column.');
$allowed=array('_yoast_wpseo_title','_yoast_wpseo_metadesc','_packrivo_document_title','_packrivo_description');
$result=array();
while (($r=fgetcsv($f,0,',','"',''))!==false) {
    $url=$r[$pos]??'';if ($url==='') continue;
    $path=parse_url($url,PHP_URL_PATH) ?: '/';if (substr($path,-1)!=='/') $path.='/';
    $id=(int)url_to_postid(home_url($path));
    if (!$id) WP_CLI::error("Missing page $path");
    $post=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID=%d",$id),ARRAY_A);
    $content=$post['post_content'];unset($post['post_content']);
    $fields=array();foreach($allowed as $key) {
        $v=$wpdb->get_results($wpdb->prepare("SELECT meta_id,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id",$id,$key),ARRAY_A);
        $fields[$key]=$v;
    }
    $other=$wpdb->get_results($wpdb->prepare("SELECT meta_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d ORDER BY meta_id",$id),ARRAY_A);
    $other=array_values(array_filter($other,static function($item) use ($allowed){return !in_array($item['meta_key'],$allowed,true);}));
    $table=$wpdb->prefix.'yoast_indexable';
    $index=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE object_type='post' AND object_id=%d ORDER BY id",$id),ARRAY_A);
    $index_other=array();foreach($index as &$entry) {
        $copy=$entry;unset($copy['title'],$copy['description']);
        $index_other[]=$copy;
        $entry=array('id'=>$entry['id'],'title'=>$entry['title'],'description'=>$entry['description']);
    }unset($entry);
    $result[$path]=array('id'=>$id,'content'=>$content,'fields'=>$fields,'index'=>$index,
        'other_post_sha256'=>hash('sha256',wp_json_encode($post)),
        'other_meta_sha256'=>hash('sha256',wp_json_encode($other)),
        'other_index_sha256'=>hash('sha256',wp_json_encode($index_other)));
}
fclose($f);
$json=wp_json_encode($result,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
if (file_put_contents($out,$json)===false) WP_CLI::error('Cannot write probe output.');
WP_CLI::line('probe_pages='.count($result).' sha256='.hash_file('sha256',$out));
