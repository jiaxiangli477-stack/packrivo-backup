<?php
/** Exact published-post migration. WP-CLI eval-file; never creates revisions. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
global $wpdb;
$mode = $args[0] ?? '';
$backup = $args[1] ?? '';
$plan_file = __DIR__ . '/32a5-blog-plan.json';
if (!in_array($mode, ['dry-run', 'backup', 'apply', 'rollback'], true) || !$backup) WP_CLI::error('Usage: dry-run|backup|apply|rollback BACKUP.json');
$plan_raw = is_readable($plan_file) ? file_get_contents($plan_file) : false;
$plan = $plan_raw === false ? null : json_decode($plan_raw, true);
if (!is_array($plan) || ($plan['version'] ?? null) !== 1 || count($plan['operations'] ?? []) !== 318) WP_CLI::error('Plan count/version mismatch; nothing changed.');
$groups = [];
foreach ($plan['operations'] as $op) {
    if (($op['field'] ?? '') !== 'post_content' || ($op['count'] ?? 0) !== 1 || empty($op['id']) || empty($op['slug']) || empty($op['old']) || empty($op['new']) || $op['old'] === $op['new']) WP_CLI::error('Invalid operation; nothing changed.');
    $groups[(int) $op['id']][] = $op;
}
$rows = []; $counts = ['before'=>0, 'after'=>0];
foreach ($groups as $id=>$ops) {
    $post = $wpdb->get_row($wpdb->prepare("SELECT ID,post_name,post_type,post_status,post_content FROM {$wpdb->posts} WHERE ID=%d", $id), ARRAY_A);
    if (!$post || $post['post_type'] !== 'post' || $post['post_status'] !== 'publish' || $post['post_name'] !== $ops[0]['slug']) WP_CLI::error("Post identity mismatch $id; nothing changed.");
    $before = $post['post_content']; $after = $before;
    foreach ($ops as $op) {
        if ($op['slug'] !== $post['post_name']) WP_CLI::error("Plan identity mismatch $id; nothing changed.");
        $old = substr_count($before, $op['old']); $new = substr_count($before, $op['new']);
        if ($old === 1 && $new === 0) { $counts['before']++; $after = str_replace($op['old'], $op['new'], $after); }
        elseif ($old === 0 && $new === 1) { $counts['after']++; }
        else WP_CLI::error("Exact sentence count mismatch post=$id old=$old new=$new; nothing changed.");
    }
    $rows[$id] = ['id'=>$id,'slug'=>$post['post_name'],'before'=>$before,'after'=>$after];
}
if ($counts['before'] && $counts['after']) WP_CLI::error('Partially migrated plan; nothing changed.');
$state = $counts['before'] ? 'before' : 'after';
// Detect an extra occurrence of any planned sentence in another published
// post as well. The three explicitly deferred rewrite posts are excluded.
if ($mode !== 'rollback') {
    $expected = [];
    foreach ($plan['operations'] as $op) $expected[$op['old']] = ($expected[$op['old']] ?? 0) + 1;
    $actual = array_fill_keys(array_keys($expected), 0);
    $published = $wpdb->get_results("SELECT ID,post_content FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish'", ARRAY_A);
    if (!is_array($published)) WP_CLI::error('Cannot inspect published posts; nothing changed.');
    foreach ($published as $post) {
        if (in_array((int)$post['ID'], [448,508,1621], true)) continue;
        foreach ($actual as $sentence=>$unused) $actual[$sentence] += substr_count($post['post_content'], $sentence);
    }
    foreach ($expected as $sentence=>$n) {
        $wanted = $state === 'before' ? $n : 0;
        if ($actual[$sentence] !== $wanted) WP_CLI::error('Published-post occurrence count mismatch; nothing changed.');
    }
}
WP_CLI::line('mode='.$mode.' plan='.count($plan['operations']).' posts='.count($rows).' state='.$state.' old='.$counts['before'].' new='.$counts['after']);
if ($mode === 'dry-run') return;
if ($mode === 'backup') {
    if ($state !== 'before' || file_exists($backup)) WP_CLI::error('Backup requires untouched state and a new path.');
    $data = ['version'=>1,'plan_sha256'=>hash('sha256',$plan_raw),'rows'=>array_values($rows)];
    $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $handle = @fopen($backup, 'x');
    if (!$handle || fwrite($handle, $json) !== strlen($json) || !fclose($handle)) WP_CLI::error('Cannot write complete exclusive backup.');
    WP_CLI::line('backup_rows='.count($rows).' sha256='.hash_file('sha256',$backup)); return;
}
$saved = is_readable($backup) ? json_decode((string)file_get_contents($backup), true) : null;
if (!is_array($saved) || ($saved['plan_sha256'] ?? '') !== hash('sha256',$plan_raw) || count($saved['rows'] ?? []) !== count($rows)) WP_CLI::error('Backup missing or does not match this plan.');
$saved_rows=[]; foreach ($saved['rows'] as $row) $saved_rows[(int)$row['id']]=$row;
foreach ($rows as $id=>$row) {
    $saved_row=$saved_rows[$id] ?? null;
    if (!$saved_row || $saved_row['slug'] !== $row['slug'] || !isset($saved_row['before'],$saved_row['after']) || $row['before'] !== $saved_row[$state]) WP_CLI::error("Backup/current mismatch $id; nothing changed.");
}
$wanted = $mode === 'apply' ? 'after' : 'before';
if ($state === $wanted) { WP_CLI::line('changed=0 posts=0'); return; }
if ($wpdb->query('START TRANSACTION') === false) WP_CLI::error('Cannot start transaction.');
foreach ($rows as $id=>$row) {
    $current = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d AND post_type='post' AND post_status='publish' AND post_name=%s FOR UPDATE",$id,$row['slug']));
    if (!is_string($current) || $current !== $saved_rows[$id][$state] || $wpdb->update($wpdb->posts,['post_content'=>$saved_rows[$id][$wanted]],['ID'=>$id]) !== 1) {
        $wpdb->query('ROLLBACK'); WP_CLI::error("Concurrent change or write error $id; rolled back.");
    }
}
if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); WP_CLI::error('Commit failed.'); }
foreach (array_keys($rows) as $id) clean_post_cache($id);
WP_CLI::line('changed='.count($plan['operations']).' posts='.count($rows));
