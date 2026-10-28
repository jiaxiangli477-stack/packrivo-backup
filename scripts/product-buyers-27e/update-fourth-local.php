<?php
/** Local-only product update. Production content application must be separately reviewed. */
if (parse_url(home_url('/'),PHP_URL_HOST)!=='localhost' || parse_url(home_url('/'),PHP_URL_PORT)!==8081) { WP_CLI::error('This script only runs against localhost:8081.'); }
$rows=json_decode(file_get_contents('/task/fourth.json'),true);
if (!is_array($rows) || count($rows)!==1) WP_CLI::error('Expected exactly one reviewed product.');
$before=[];
foreach($rows as $row){
 $p=get_page_by_path($row['slug']); if(!$p || $p->post_status!=='publish') WP_CLI::error('Missing published page: '.$row['slug']);
 if(preg_match_all('~<h1\b[^>]*>.*?</h1>~is',$p->post_content)!==1) WP_CLI::error('Expected exactly one stored H1: '.$row['slug']);
 $before[]=['id'=>$p->ID,'post_title'=>$p->post_title,'post_content'=>$p->post_content,'meta'=>array_combine(['_packrivo_document_title','_packrivo_description','_yoast_wpseo_title','_yoast_wpseo_metadesc','_packrivo_remediation_product'],array_map(fn($k)=>get_post_meta($p->ID,$k,true),['_packrivo_document_title','_packrivo_description','_yoast_wpseo_title','_yoast_wpseo_metadesc','_packrivo_remediation_product']))];
}
$backup='/tasktest/pk27e-before-fourth.json'; if(file_exists($backup)) WP_CLI::error('Backup already exists; do not overwrite it.');
if(file_put_contents($backup,wp_json_encode($before,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))===false) WP_CLI::error('Backup failed.');
foreach($rows as $i=>$row){
 $old=$before[$i]; $content=preg_replace_callback('~(<h1\b[^>]*>).*?(</h1>)~is',fn($m)=>$m[1].esc_html($row['title']).$m[2],$old['post_content'],1);
 $result=wp_update_post(wp_slash(['ID'=>$old['id'],'post_title'=>$row['title'],'post_content'=>$content]),true); if(is_wp_error($result)) WP_CLI::error($result->get_error_message());
 foreach(['_packrivo_document_title'=>$row['title'].' | Packrivo','_packrivo_description'=>$row['description'],'_yoast_wpseo_title'=>$row['title'].' | Packrivo','_yoast_wpseo_metadesc'=>$row['description']] as $key=>$value) update_post_meta($old['id'],$key,$value);
 $m=$old['meta']['_packrivo_remediation_product']; if(is_array($m)){if(array_key_exists('name',$m)) $m['name']=$row['title']; if(array_key_exists('description',$m)) $m['description']=$row['description'];update_post_meta($old['id'],'_packrivo_remediation_product',$m);}
 echo $row['slug']," updated\n";
}
