<?php
/** Exact, reversible seven-row blog migration. Run only via WP-CLI eval-file. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
global $wpdb;
$mode = $args[0] ?? '';
$csv_path = $args[1] ?? '';
$backup_path = $args[2] ?? '';
if (!in_array($mode, ['dry-run','backup','apply','rollback'], true) || !$csv_path || !$backup_path) {
    WP_CLI::error('Usage: dry-run|backup|apply CSV BACKUP.json (rollback uses the companion script)');
}
$raw = is_readable($csv_path) ? file_get_contents($csv_path) : false;
if ($raw === false) WP_CLI::error('Cannot read CSV; nothing changed.');
$input = fopen('php://temp', 'w+');
fwrite($input, $raw); rewind($input);
$header = fgetcsv($input);
if (is_array($header)) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
if ($header !== ['post_id','field','old_exact','new_exact']) WP_CLI::error('Invalid CSV header; nothing changed.');
$ops = [];
while (($row = fgetcsv($input)) !== false) {
    if (count($row) !== 4) WP_CLI::error('Invalid CSV row; nothing changed.');
    [$id, $field, $old, $new] = $row;
    if (!ctype_digit($id) || !in_array($field, ['post_title','post_content','_yoast_wpseo_title','_yoast_wpseo_metadesc'], true) || $old === '' || $new === '' || $old === $new) WP_CLI::error('Invalid CSV operation; nothing changed.');
    if ((int)$id === 448 && $field === 'post_content') {
        foreach (['01-worksheet','b03-448-teaching'] as $image) {
            $pattern = '~<figure\b[^>]*>(?:(?!</?figure\b).)*'.preg_quote($image, '~').'(?:(?!</?figure\b).)*</figure\s*>~is';
            if (preg_match_all($pattern, $new, $figures) !== 1 || !preg_match('~<img\b[^>]*'.preg_quote($image, '~').'[^>]*>~is', $figures[0][0])) WP_CLI::error("Cannot identify image in figure $image; nothing changed.");
            $new = preg_replace($pattern, '', $new, -1, $removed);
            if ($removed !== 1 || stripos($new, $image) !== false) WP_CLI::error("Cannot isolate figure $image; nothing changed.");
        }
    }
    $ops[] = ['id'=>(int)$id, 'field'=>$field, 'old'=>$old, 'new'=>$new];
}
fclose($input);
if (count($ops) !== 7) WP_CLI::error('Expected exactly seven operations; nothing changed.');
$groups = [];
foreach ($ops as $op) $groups[$op['id'].':'.$op['field']][] = $op;
$expected = ['448:post_title'=>1,'448:_yoast_wpseo_title'=>1,'448:_yoast_wpseo_metadesc'=>1,'448:post_content'=>1,'508:post_content'=>2,'1621:post_content'=>1];
if (count($groups) !== count($expected)) WP_CLI::error('Unexpected operation targets; nothing changed.');
foreach ($expected as $key=>$n) if (count($groups[$key] ?? []) !== $n) WP_CLI::error("Unexpected operation target $key; nothing changed.");
$entries = []; $old_count = 0; $new_count = 0;
foreach ($groups as $key => $items) {
    $id = $items[0]['id']; $field = $items[0]['field'];
    $post = $wpdb->get_row($wpdb->prepare("SELECT ID, post_name, post_status, post_type, post_title, post_content FROM {$wpdb->posts} WHERE ID=%d", $id), ARRAY_A);
    if (!$post || $post['post_status'] !== 'publish' || $post['post_type'] !== 'post') WP_CLI::error("Post identity mismatch $id; nothing changed.");
    if (in_array($field, ['post_title','post_content'], true)) {
        $value = $post[$field]; $meta_id = null;
    } else {
        $meta = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s", $id, $field), ARRAY_A);
        if (count($meta) !== 1) WP_CLI::error("Metadata count mismatch $key; nothing changed.");
        $value = $meta[0]['meta_value']; $meta_id = (int)$meta[0]['meta_id'];
    }
    $result = $value;
    foreach ($items as $op) {
        if ($key === '448:post_content' && $value !== $op['old'] && $value !== $op['new']) WP_CLI::error('Post 448 must match the complete old or transformed new content; nothing changed.');
        $old = substr_count($value, $op['old']); $new = substr_count($value, $op['new']);
        if ($old === 1 && $new === 0) { $old_count++; $result = str_replace($op['old'], $op['new'], $result); }
        elseif ($old === 0 && $new === 1) $new_count++;
        else WP_CLI::error("Exact match mismatch $key old=$old new=$new; nothing changed.");
    }
    $entries[$key] = ['id'=>$id,'slug'=>$post['post_name'],'field'=>$field,'meta_id'=>$meta_id,'before'=>$value,'after'=>$result];
}
if ($old_count !== 7 && $new_count !== 7) WP_CLI::error('Partial or inconsistent migration state; nothing changed.');
$state = $old_count === 7 ? 'before' : 'after';
$table = $wpdb->prefix.'yoast_indexable';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) WP_CLI::error('Yoast indexable table missing; nothing changed.');
foreach ([$wpdb->posts,$wpdb->postmeta,$table] as $transaction_table) {
    $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $transaction_table));
    if (strtoupper((string)$engine) !== 'INNODB') WP_CLI::error("Non-transactional table $transaction_table; nothing changed.");
}
$columns = ['title','description','open_graph_title','open_graph_description','twitter_title','twitter_description'];
$cache = $wpdb->get_results("SELECT id, object_type, object_id, title, description, open_graph_title, open_graph_description, twitter_title, twitter_description FROM {$table} WHERE object_type='post' AND object_id=448", ARRAY_A);
if (count($cache) !== 1) WP_CLI::error('Expected one post 448 Yoast indexable; nothing changed.');
$index_before = $cache[0]; $index_after = $index_before;
if ($state === 'before') {
    foreach (['_yoast_wpseo_title'=>['title','open_graph_title','twitter_title'], '_yoast_wpseo_metadesc'=>['description','open_graph_description','twitter_description']] as $field=>$targets) {
        $item = $groups['448:'.$field][0];
        if ($index_before[$targets[0]] !== $item['old']) WP_CLI::error("Yoast $field cache differs from old metadata; nothing changed.");
        $index_after[$targets[0]] = $item['new'];
        // These caches are sourced from separate social metadata, not the SEO
        // title/description. An absent social meta key is represented by NULL
        // when Yoast refreshes the post, even if the old cache was populated.
        $social_keys = ['open_graph_title'=>'_yoast_wpseo_opengraph-title','open_graph_description'=>'_yoast_wpseo_opengraph-description','twitter_title'=>'_yoast_wpseo_twitter-title','twitter_description'=>'_yoast_wpseo_twitter-description'];
        foreach (array_slice($targets, 1) as $column) {
            if ($index_after[$column] === $item['old'] && !metadata_exists('post', 448, $social_keys[$column])) $index_after[$column] = null;
        }
    }
}
WP_CLI::line("mode=$mode plan=7 fields=".count($entries)." state=$state old=$old_count new=$new_count");
if ($mode === 'dry-run') return;
if ($mode === 'backup') {
    if ($state !== 'before' || file_exists($backup_path)) WP_CLI::error('Backup requires untouched state and a new path.');
    $data = ['version'=>1,'csv_sha256'=>hash('sha256',$raw),'entries'=>$entries,'index_before'=>$index_before,'index_after'=>$index_after];
    $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $handle = @fopen($backup_path, 'x');
    if (!$handle || fwrite($handle, $json) !== strlen($json) || !fclose($handle)) WP_CLI::error('Could not write complete exclusive backup.');
    WP_CLI::line('backup_fields='.count($entries).' indexable_rows=1 sha256='.hash_file('sha256',$backup_path)); return;
}
$saved = is_readable($backup_path) ? json_decode((string)file_get_contents($backup_path), true) : null;
if (!is_array($saved) || ($saved['version'] ?? null) !== 1 || ($saved['csv_sha256'] ?? '') !== hash('sha256',$raw) || count($saved['entries'] ?? []) !== count($entries)) WP_CLI::error('Backup missing or does not match CSV; nothing changed.');
foreach ($entries as $key=>$entry) {
    $stored = $saved['entries'][$key] ?? null;
    if (!$stored || $stored['id'] !== $entry['id'] || $stored['slug'] !== $entry['slug'] || $stored['field'] !== $entry['field'] || $stored['meta_id'] !== $entry['meta_id'] || $stored[$state] !== $entry['before']) WP_CLI::error("Backup/current mismatch $key; nothing changed.");
    if ($state === 'before' && $stored['after'] !== $entry['after']) WP_CLI::error("Backup/plan mismatch $key; nothing changed.");
}
if ($cache[0] !== ($saved['index_'.$state] ?? null)) WP_CLI::error('Yoast backup/current mismatch; nothing changed.');
$wanted = $mode === 'apply' ? 'after' : 'before';
if ($state === $wanted) { WP_CLI::line('changed=0 fields=0 indexable_rows=0'); return; }
if ($wpdb->query('START TRANSACTION') === false) WP_CLI::error('Cannot start transaction.');
foreach ($entries as $key=>$entry) {
    $saved_entry = $saved['entries'][$key]; $id = $entry['id']; $field = $entry['field'];
    if ($entry['meta_id'] === null) {
        $locked = $wpdb->get_row($wpdb->prepare("SELECT post_name,post_status,post_type,{$field} AS value FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$id), ARRAY_A);
        if (!$locked || $locked['post_name'] !== $entry['slug'] || $locked['post_status'] !== 'publish' || $locked['post_type'] !== 'post' || $locked['value'] !== $saved_entry[$state] || $wpdb->update($wpdb->posts,[$field=>$saved_entry[$wanted]],['ID'=>$id]) !== 1) { $wpdb->query('ROLLBACK'); WP_CLI::error("Post conflict $key; rolled back."); }
    } else {
        $locked = $wpdb->get_row($wpdb->prepare("SELECT post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_id=%d FOR UPDATE",$entry['meta_id']), ARRAY_A);
        if (!$locked || (int)$locked['post_id'] !== $id || $locked['meta_key'] !== $field || $locked['meta_value'] !== $saved_entry[$state] || $wpdb->update($wpdb->postmeta,['meta_value'=>$saved_entry[$wanted]],['meta_id'=>$entry['meta_id']]) !== 1) { $wpdb->query('ROLLBACK'); WP_CLI::error("Meta conflict $key; rolled back."); }
    }
}
$locked = $wpdb->get_row($wpdb->prepare("SELECT id, object_type, object_id, title, description, open_graph_title, open_graph_description, twitter_title, twitter_description FROM {$table} WHERE id=%d FOR UPDATE",(int)$cache[0]['id']), ARRAY_A);
if ($locked !== $saved['index_'.$state]) { $wpdb->query('ROLLBACK'); WP_CLI::error('Yoast concurrent change; rolled back.'); }
$updates = []; foreach ($columns as $column) if ($saved['index_'.$state][$column] !== $saved['index_'.$wanted][$column]) $updates[$column] = $saved['index_'.$wanted][$column];
if (!$updates || $wpdb->update($table,$updates,['id'=>(int)$cache[0]['id']]) !== 1) { $wpdb->query('ROLLBACK'); WP_CLI::error('Yoast cache update failed; rolled back.'); }
if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); WP_CLI::error('Commit failed.'); }
foreach ([448,508,1621] as $id) clean_post_cache($id);
WP_CLI::line('changed=7 fields='.count($entries).' indexable_rows=1');
