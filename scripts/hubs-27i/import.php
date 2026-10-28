<?php
/** wp eval-file import.php <plan|backup|apply|verify|rollback-check|rollback> <package> <expected-site> [backup] */
require __DIR__ . '/../product-buyers-27g/lib.php';
$mode=$args[0]??'plan';$root=rtrim($args[1]??'','/\\');$site=rtrim($args[2]??'','/');$backupFile=$args[3]??$root.'/deploy/rollback-27i.json';
$keys=['_packrivo_document_title','_packrivo_description','_yoast_wpseo_title','_yoast_wpseo_metadesc'];$transaction=false;$touched=[];
function hubs27i_state($id,$keys){$p=get_post($id);$meta=get_post_meta($id);$target=[];foreach($keys as $key){$target[$key]=$meta[$key]??[];unset($meta[$key]);}return ['id'=>$id,'slug'=>$p->post_name,'post_title'=>$p->post_title,'post_content_sha256'=>hash('sha256',$p->post_content),'untouched_sha256'=>hash('sha256',wp_json_encode([(array)$p,$meta])),'meta'=>$target];}
function hubs27i_target($row,$before){$description=$row['path']==='/inquiry/'?($before['meta']['_packrivo_description'][0]??''):$row['description'];if($description==='')throw new RuntimeException('Existing inquiry description is empty.');return ['_packrivo_document_title'=>[$row['title']],'_packrivo_description'=>[$description],'_yoast_wpseo_title'=>[$row['title']],'_yoast_wpseo_metadesc'=>[$description]];}
try{
 if(!$root||!in_array($mode,['plan','backup','apply','verify','rollback-check','rollback'],true)||$site!==rtrim(home_url('/'),'/'))throw new RuntimeException('Invalid arguments or expected-site guard failed.');
 $file=$root.'/dsh-out/hubs-27i.json';$all=pk27g_read_json($file);$rows=array_values(array_filter($all,fn($r)=>isset($r['path'])));if(count($rows)!==8)throw new RuntimeException('Expected 8 approved hubs.');$hash=hash_file('sha256',$file);$current=[];
 foreach($rows as $row){$path=$row['path'];if(isset($current[$path]))throw new RuntimeException('Duplicate path.');$p=$path==='/'?get_post((int)get_option('page_on_front')):($path==='/blog/'?get_post((int)get_option('page_for_posts')):get_page_by_path(trim($path,'/')));if(!$p||$p->post_type!=='page'||$p->post_status!=='publish')throw new RuntimeException('Missing published hub: '.$path);$current[$path]=hubs27i_state($p->ID,$keys);}
 echo 'hub_pages='.count($current).' preflight=PASS'."\n";if($mode==='plan'){echo 'inquiry_existing_description=preserved writes=0'."\n";return;}
 $backup=is_file($backupFile)?pk27g_read_json($backupFile):null;
 if($backup&&(($backup['site']??'')!==$site||($backup['input_hash']??'')!==$hash))throw new RuntimeException('Original backup site/input mismatch; not overwritten.');
 if(!$backup&&in_array($mode,['backup','apply'],true)){$backup=['version'=>1,'site'=>$site,'input_hash'=>$hash,'rows'=>$current];$h=fopen($backupFile,'x');if(!$h)throw new RuntimeException('Cannot exclusively create backup.');$raw=wp_json_encode($backup,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";$n=fwrite($h,$raw);fclose($h);if($n!==strlen($raw))throw new RuntimeException('Backup write incomplete; no DB writes.');echo 'backup=created rows=8'."\n";}elseif($backup)echo 'backup=reused rows='.count($backup['rows'])."\n";
 if(!$backup||array_keys($backup['rows'])!==array_keys($current))throw new RuntimeException('Matching complete backup required.');
 $plans=[];$already=0;$mismatch=[];
 foreach($rows as $row){$path=$row['path'];$now=$current[$path];$before=$backup['rows'][$path];if($now['id']!==$before['id']||$now['untouched_sha256']!==$before['untouched_sha256'])throw new RuntimeException('H1/body/URL or unrelated data drift: '.$path);$target=hubs27i_target($row,$before);
  if($mode==='verify'){if($now['meta']!==$target)$mismatch[]=$path;continue;}
  if($now['meta']!==$target&&$now['meta']!==$before['meta'])throw new RuntimeException('Unexpected metadata edits: '.$path);
  $desired=in_array($mode,['rollback','rollback-check'],true)?$before['meta']:$target;if($now['meta']===$desired)$already++;else $plans[$path]=$desired;
 }
 if($mode==='verify'){echo 'verified_hubs=8 metadata_mismatches='.count($mismatch).' H1_body_URL_unchanged=8/8 inquiry_description_preserved=yes'."\n";if($mismatch)throw new RuntimeException(implode(',',$mismatch));return;}
 if($mode==='backup'){echo 'backup_only rows=8 writes=0'."\n";return;}
 if($mode==='rollback-check'){echo 'rollback_eligible='.count($plans).' already_restored='.$already.' writes=0'."\n";return;}
 global $wpdb;if($wpdb->query('START TRANSACTION')===false)throw new RuntimeException('Cannot start transaction.');$transaction=true;
 foreach($plans as $path=>$values){$id=$current[$path]['id'];$touched[]=$id;foreach($values as $key=>$list){delete_post_meta($id,$key);foreach($list as $v)if(!add_post_meta($id,$key,wp_slash($v)))throw new RuntimeException('Meta write failed.');}clean_post_cache($id);$after=hubs27i_state($id,$keys);if($after['meta']!==$values||$after['untouched_sha256']!==$backup['rows'][$path]['untouched_sha256'])throw new RuntimeException('Metadata readback/body guard failed: '.$path);}
 if($wpdb->query('COMMIT')===false)throw new RuntimeException('Commit failed.');$transaction=false;echo $mode.'_updated='.count($plans).' already_current='.$already.' H1_body_URL_check=PASS'."\n";
}catch(Throwable $e){if($transaction){$wpdb->query('ROLLBACK');foreach($touched as $id)clean_post_cache($id);}WP_CLI::error($e->getMessage());}
