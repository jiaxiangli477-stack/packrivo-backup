<?php
/** Restore only fields recorded in an intact version-3 backup. WP-CLI eval-file. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
global $wpdb;
$argv = isset($args) && is_array($args) ? $args : array();
$file = ''; $dry = false;
foreach ($argv as $arg) {
    $arg = (string)$arg;
    if ($arg === 'dry-run' || $arg === '--dry-run') $dry = true;
    elseif (strpos($arg, '--') === 0) WP_CLI::error('Use an explicit positional backup path; flags are not backup paths.');
    elseif ($file === '') $file = $arg;
    else WP_CLI::error('Unexpected argument; nothing rolled back.');
}
if ($file === '') WP_CLI::error('Explicit backup path required; nothing rolled back.');
if (!is_readable($file)) WP_CLI::error("Backup file not readable: $file; nothing rolled back.");
require_once __DIR__.'/34a-backup-lib.php';
try { $decoded=pk34a_backup_decode((string)file_get_contents($file)); }
catch (Throwable $e) { WP_CLI::error($e->getMessage().'; nothing rolled back.'); }
$meta_keys = array('_yoast_wpseo_title','_yoast_wpseo_metadesc','_packrivo_document_title','_packrivo_description');
$records=array();$seen=array();
foreach ($decoded as $r) {
    if ($r['version'] !== '3' || !ctype_digit($r['post_id']) || (int)$r['post_id'] < 1 || !in_array($r['old_exists'],array('0','1'),true) || !in_array($r['old_is_null'],array('0','1'),true)) WP_CLI::error('Invalid backup data; nothing rolled back.');
    $field=$r['field'];
    if (!in_array($field,$meta_keys,true) && $field !== 'h1' && !preg_match('/^idx:\d+:(title|description)$/',$field)) WP_CLI::error("Disallowed backup field $field; nothing rolled back.");
    if ($r['old_is_null'] === '1' && strpos($field,'idx:') !== 0) WP_CLI::error('Only indexable values may be null; nothing rolled back.');
    if (($field === 'h1' || strpos($field,'idx:') === 0) && $r['old_exists'] !== '1') WP_CLI::error('Invalid existence flag; nothing rolled back.');
    $key=$r['post_id'].':'.$field;
    if (isset($seen[$key])) WP_CLI::error("Duplicate backup field $key; nothing rolled back.");
    $seen[$key]=true; $records[]=$r;
}
if (!$records) WP_CLI::error('Backup contains no records; nothing rolled back.');
$indexable_table=$wpdb->prefix.'yoast_indexable';
foreach (array($wpdb->posts,$wpdb->postmeta,$indexable_table) as $table) {
    $engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
    if (strtoupper((string)$engine) !== 'INNODB') WP_CLI::error("Non-transactional table $table; nothing rolled back.");
}
if ($wpdb->query('START TRANSACTION') === false) WP_CLI::error('Cannot start transaction.');
$checked_posts=array();
foreach ($records as $r) {
    $id=(int)$r['post_id'];
    if (isset($checked_posts[$id])) {
        if ($checked_posts[$id] !== $r['url']) { $wpdb->query('ROLLBACK'); WP_CLI::error("Conflicting backup URLs for post $id; nothing rolled back."); }
        continue;
    }
    $post=$wpdb->get_row($wpdb->prepare("SELECT post_type,post_status FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$id),ARRAY_A);
    if (!$post || $post['post_type']!=='page' || $post['post_status']!=='publish' || (int)url_to_postid(home_url($r['url']))!==$id) {
        $wpdb->query('ROLLBACK'); WP_CLI::error("Page identity changed $id; nothing rolled back.");
    }
    $checked_posts[$id]=$r['url'];
}
$pending=array();$unchanged=0;
foreach ($records as $r) {
    $id=(int)$r['post_id'];$field=$r['field'];
    if (in_array($field,$meta_keys,true)) {
        $rows=$wpdb->get_results($wpdb->prepare("SELECT meta_id,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s FOR UPDATE",$id,$field),ARRAY_A);
        if (!is_array($rows) || count($rows)>1) { $wpdb->query('ROLLBACK'); WP_CLI::error("Duplicate metadata $id $field; nothing rolled back."); }
        if ($r['old_exists']==='0' && !$rows) { $unchanged++; continue; }
        if ($r['old_exists']==='1' && count($rows)===1 && $rows[0]['meta_value']===$r['old_value']) { $unchanged++; continue; }
        if (count($rows)!==1 || $rows[0]['meta_value']!==$r['new_value']) { $wpdb->query('ROLLBACK'); WP_CLI::error("Metadata changed since migration $id $field; nothing rolled back."); }
        $pending[]=array('record'=>$r,'meta_id'=>(int)$rows[0]['meta_id']);
    } elseif ($field === 'h1') {
        $body=$wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$id));
        if (!is_string($body) || preg_match_all('~<h1\b[^>]*>([\s\S]*?)</h1>~i',$body,$m,PREG_OFFSET_CAPTURE)!==1) { $wpdb->query('ROLLBACK'); WP_CLI::error("H1 structure changed for post $id; nothing rolled back."); }
        $current=$m[1][0][0];
        if ($current===$r['old_value']) { $unchanged++; continue; }
        if ($current!==$r['new_value']) { $wpdb->query('ROLLBACK'); WP_CLI::error("H1 changed since migration $id; nothing rolled back."); }
        $pending[]=array('record'=>$r,'content'=>$body,'offset'=>$m[1][0][1]);
    } else {
        preg_match('/^idx:(\d+):(title|description)$/',$field,$match);
        $row=$wpdb->get_row($wpdb->prepare("SELECT object_id,object_type,{$match[2]} AS val FROM {$indexable_table} WHERE id=%d FOR UPDATE",(int)$match[1]),ARRAY_A);
        $old=$r['old_is_null']==='1' ? null : $r['old_value'];
        if (!$row || (int)$row['object_id']!==$id || $row['object_type']!=='post') { $wpdb->query('ROLLBACK'); WP_CLI::error("Indexable identity changed $id $field; nothing rolled back."); }
        if ($row['val']===$old) { $unchanged++; continue; }
        if ($row['val']!==$r['new_value']) { $wpdb->query('ROLLBACK'); WP_CLI::error("Indexable changed since migration $id $field; nothing rolled back."); }
        $pending[]=array('record'=>$r,'row'=>(int)$match[1],'column'=>$match[2]);
    }
}
WP_CLI::line('rollback_file='.$file.' records='.count($records).' pending='.count($pending).' unchanged='.$unchanged);
if ($dry) { $wpdb->query('ROLLBACK'); WP_CLI::line('dry_run=1 (no database change)'); return; }
$counts=array('meta'=>0,'h1'=>0,'indexable'=>0);$touched=array();
foreach ($pending as $p) {
    $r=$p['record'];$id=(int)$r['post_id'];$field=$r['field'];
    if (in_array($field,$meta_keys,true)) {
        $ok=$r['old_exists']==='0' ? $wpdb->delete($wpdb->postmeta,array('meta_id'=>$p['meta_id'])) : $wpdb->update($wpdb->postmeta,array('meta_value'=>$r['old_value']),array('meta_id'=>$p['meta_id']));
        $counts['meta']++;
    } elseif ($field==='h1') {
        $restored=substr_replace($p['content'],$r['old_value'],$p['offset'],strlen($r['new_value']));
        $ok=$wpdb->update($wpdb->posts,array('post_content'=>$restored),array('ID'=>$id));
        $counts['h1']++;
    } else {
        $value=$r['old_is_null']==='1' ? null : $r['old_value'];
        // Suppress Yoast's automatic updated_at modification on rollback too.
        $ok=$value===null
            ? $wpdb->query($wpdb->prepare("UPDATE {$indexable_table} SET {$p['column']}=NULL, updated_at=updated_at WHERE id=%d",$p['row']))
            : $wpdb->query($wpdb->prepare("UPDATE {$indexable_table} SET {$p['column']}=%s, updated_at=updated_at WHERE id=%d",$value,$p['row']));
        $counts['indexable']++;
    }
    if ($ok!==1) { $wpdb->query('ROLLBACK'); WP_CLI::error("Restore failed $id $field; transaction rolled back."); }
    $touched[$id]=true;
}
if ($wpdb->query('COMMIT')===false) { $wpdb->query('ROLLBACK'); WP_CLI::error('Rollback commit failed.'); }
foreach (array_keys($touched) as $id) { clean_post_cache($id); wp_cache_delete($id,'post_meta'); }
WP_CLI::line('changed='.count($pending).' meta='.$counts['meta'].' h1='.$counts['h1'].' indexable='.$counts['indexable']);
WP_CLI::success('34a rollback finished.');
