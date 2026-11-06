<?php
/** R1 exact three-meta migration; use only with explicit, separately backed up database. */
function r1_moq_spec() {
 return [
  '2884:_packrivo_description'=>['id'=>2884,'type'=>'page','slug'=>'samples','field'=>'_packrivo_description','old'=>'Order a sample of your custom box or pouch to check size, print and finish before your 500-piece order. Get a price.','new'=>'Order a sample of your custom box or pouch to check size, print and finish before your 250-piece order. Get a price.'],
  '448:_packrivo_document_title'=>['id'=>448,'type'=>'post','slug'=>'packaging-moq-design-planning','field'=>'_packrivo_document_title','old'=>'Custom Packaging MOQ: Plan 500 Pieces per Design','new'=>'Custom Packaging MOQ: Plan 250 Pieces per Design'],
  '448:_packrivo_description'=>['id'=>448,'type'=>'post','slug'=>'packaging-moq-design-planning','field'=>'_packrivo_description','old'=>'Plan a 500-piece packaging MOQ by design. Separate selling products, packaging variants and reserve allocations with a clear quantity worksheet for your brief.','new'=>'Plan a 250-piece packaging MOQ by design. Separate selling products, packaging variants and reserves with a clear quantity worksheet for your brief.'],
 ];
}
function r1_moq_state($values) {
 $spec=r1_moq_spec();$states=[];foreach($spec as $key=>$row){if(!array_key_exists($key,$values))throw new RuntimeException('Missing target '.$key);$v=$values[$key];if($v===$row['old'])$states[]='before';elseif($v===$row['new'])$states[]='after';else throw new RuntimeException('Exact value mismatch '.$key);}
 if(count(array_unique($states))!==1)throw new RuntimeException('Partial or inconsistent target state');return $states[0];
}
if(defined('R1_MOQ_TEST_ONLY')&&R1_MOQ_TEST_ONLY)return;
if(!defined('WP_CLI')||!WP_CLI)exit(1);
try {
 global $wpdb;$mode=$args[0]??'';$backup=$args[1]??'';
 if(!in_array($mode,['dry-run','backup','apply','rollback'],true)||$backup==='')throw new RuntimeException('Usage: dry-run|backup|apply|rollback BACKUP.json');
 foreach([$wpdb->posts,$wpdb->postmeta] as $table){$engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));if(strtoupper((string)$engine)!=='INNODB')throw new RuntimeException('Non-transactional table '.$table);}
 $spec=r1_moq_spec();$values=[];$entries=[];
 foreach($spec as $key=>$r){$post=$wpdb->get_row($wpdb->prepare("SELECT ID,post_name,post_type,post_status FROM {$wpdb->posts} WHERE ID=%d",$r['id']),ARRAY_A);if(!$post||$post['post_type']!==$r['type']||$post['post_name']!==$r['slug']||$post['post_status']!=='publish')throw new RuntimeException('Post identity mismatch '.$key);$rows=$wpdb->get_results($wpdb->prepare("SELECT meta_id,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",$r['id'],$r['field']),ARRAY_A);if(count($rows)!==1)throw new RuntimeException('Expected exactly one metadata row '.$key);$values[$key]=$rows[0]['meta_value'];$entries[$key]=['id'=>$r['id'],'field'=>$r['field'],'meta_id'=>(int)$rows[0]['meta_id'],'old'=>$r['old'],'new'=>$r['new']];}
 foreach(['_yoast_wpseo_title'=>'448:_packrivo_document_title','_yoast_wpseo_metadesc'=>'448:_packrivo_description'] as $field=>$target){$rows=$wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",448,$field),ARRAY_A);if(count($rows)!==1||$rows[0]['meta_value']!==$spec[$target]['new'])throw new RuntimeException('Yoast target mismatch '.$field);}
 $state=r1_moq_state($values);$planhash=hash('sha256',json_encode($spec,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));WP_CLI::line("mode=$mode fields=3 state=$state plan_sha256=$planhash");
 if($mode==='dry-run')return;
 if($mode==='backup'){
  if($state!=='before'||file_exists($backup))throw new RuntimeException('Backup requires before state and new path');$json=wp_json_encode(['version'=>1,'plan_sha256'=>$planhash,'entries'=>$entries],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);$f=@fopen($backup,'x');if(!$f||fwrite($f,$json)!==strlen($json)||!fclose($f))throw new RuntimeException('Backup write failed');WP_CLI::line('backup_fields=3 sha256='.hash_file('sha256',$backup));return;
 }
 $saved=is_readable($backup)?json_decode((string)file_get_contents($backup),true):null;
 if(!is_array($saved)||($saved['version']??null)!==1||($saved['plan_sha256']??'')!==$planhash||($saved['entries']??null)!==$entries)throw new RuntimeException('Backup does not match exact plan or metadata IDs');
 $wanted=$mode==='apply'?'after':'before';if($state===$wanted){WP_CLI::line('changed=0');return;}
 if($wpdb->query('START TRANSACTION')===false)throw new RuntimeException('Cannot start transaction');
 try {
  foreach(['_yoast_wpseo_title'=>'448:_packrivo_document_title','_yoast_wpseo_metadesc'=>'448:_packrivo_description'] as $field=>$target){$locked_target=$wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s FOR UPDATE",448,$field),ARRAY_A);if(count($locked_target)!==1||$locked_target[0]['meta_value']!==$spec[$target]['new'])throw new RuntimeException('Concurrent Yoast target mismatch '.$field);}
  foreach($spec as $key=>$r){$entry=$entries[$key];$post=$wpdb->get_row($wpdb->prepare("SELECT post_name,post_type,post_status FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$r['id']),ARRAY_A);$meta=$wpdb->get_row($wpdb->prepare("SELECT post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_id=%d FOR UPDATE",$entry['meta_id']),ARRAY_A);$expected=$state==='before'?$r['old']:$r['new'];$value=$wanted==='after'?$r['new']:$r['old'];if(!$post||$post['post_name']!==$r['slug']||$post['post_type']!==$r['type']||$post['post_status']!=='publish'||!$meta||(int)$meta['post_id']!==$r['id']||$meta['meta_key']!==$r['field']||$meta['meta_value']!==$expected)throw new RuntimeException('Concurrent conflict '.$key);if($wpdb->update($wpdb->postmeta,['meta_value'=>$value],['meta_id'=>$entry['meta_id']])!==1)throw new RuntimeException('Update failed '.$key);}
  if($wpdb->query('COMMIT')===false)throw new RuntimeException('Commit failed');
 }catch(Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
 foreach([2884,448] as $id)clean_post_cache($id);WP_CLI::line('changed=3');
}catch(Throwable $e){WP_CLI::error($e->getMessage().'; nothing changed or transaction rolled back');}
