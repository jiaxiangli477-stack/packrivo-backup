<?php
/** wp eval-file import.php <plan|apply|verify|rollback-check|rollback> <package-root> <expected-site> [backup-path] [pending130|all134] */
require __DIR__ . '/lib.php';
$mode = $args[0] ?? 'plan';
$root = rtrim($args[1] ?? '', '/\\');
$expected_site = rtrim($args[2] ?? '', '/');
$backup_path = $args[3] ?? $root . '/deploy/rollback-27g.json';
$scope = $args[4] ?? 'pending130';
$meta_keys = ['_packrivo_document_title','_packrivo_description','_yoast_wpseo_title','_yoast_wpseo_metadesc','_packrivo_remediation_product'];
$touched = [];
$transaction = false;

function pk27g_current($id, $meta_keys)
{
    $p = get_post($id);
    $meta = [];
    foreach ($meta_keys as $key) $meta[$key] = get_post_meta($id, $key, false);
    return ['id'=>$id,'slug'=>$p->post_name,'status'=>$p->post_status,'parent'=>(int)$p->post_parent,
        'post_title'=>$p->post_title,'post_content'=>$p->post_content,'post_modified'=>$p->post_modified,
        'post_modified_gmt'=>$p->post_modified_gmt,'body_hash'=>pk27g_body_hash($p->post_content),'meta'=>$meta];
}

function pk27g_target_meta($before, $item)
{
    $meta = $before['meta'];
    foreach (['_packrivo_document_title'=>$item['new_title'],'_yoast_wpseo_title'=>$item['new_title'],
        '_packrivo_description'=>$item['new_description'],'_yoast_wpseo_metadesc'=>$item['new_description']] as $key=>$value) $meta[$key] = [$value];
    if (!empty($meta['_packrivo_remediation_product'][0]) && is_array($meta['_packrivo_remediation_product'][0])) {
        $m = $meta['_packrivo_remediation_product'][0];
        if (array_key_exists('name', $m)) $m['name'] = $item['new_h1'];
        if (array_key_exists('description', $m)) $m['description'] = $item['new_description'];
        $meta['_packrivo_remediation_product'] = [$m];
    }
    return $meta;
}

function pk27g_write($id, $title, $content, $meta)
{
    $result = wp_update_post(wp_slash(['ID'=>$id,'post_title'=>$title,'post_content'=>$content]), true);
    if (is_wp_error($result) || !$result) throw new RuntimeException('Post write failed: ' . $id);
    foreach ($meta as $key=>$values) {
        delete_post_meta($id, $key);
        foreach ($values as $value) {
            if (!add_post_meta($id, $key, wp_slash($value))) throw new RuntimeException('Meta write failed: ' . $id . '/' . $key);
        }
    }
    clean_post_cache($id);
}

try {
    if (!$root || !in_array($mode, ['plan','apply','verify','rollback-check','rollback'], true)) throw new RuntimeException('Invalid arguments.');
    if (!$expected_site || $expected_site !== rtrim(home_url('/'), '/')) throw new RuntimeException('Expected-site guard failed.');
    $input = pk27g_inputs($root, $scope);
    $items = $input['items'];
    $snapshots = [];
    foreach ($items as $slug=>$item) {
        $p = get_page_by_path($slug);
        if (!$p || $p->post_type !== 'page' || $p->post_status !== 'publish') throw new RuntimeException('Published page missing: ' . $slug);
        $state = pk27g_current($p->ID, $meta_keys);
        $title = $state['meta']['_packrivo_document_title'][0] ?? $state['post_title'];
        if (!in_array($title, [$item['old_title'],$item['new_title']], true)) throw new RuntimeException('Unexpected title drift: ' . $slug);
        $snapshots[$slug] = $state;
    }
    echo 'input_pages=' . count($items) . ' preflight=PASS' . "\n";
    if ($mode === 'plan') { echo "writes=0\n"; return; }
    $backup = is_file($backup_path) ? pk27g_read_json($backup_path) : null;
    if ($backup && (($backup['input_hash'] ?? '') !== $input['hash'] || ($backup['site'] ?? '') !== $expected_site)) throw new RuntimeException('Backup input/site mismatch; original backup was not changed.');
    if ($mode === 'apply' && !$backup) {
        $backup = ['version'=>1,'site'=>$expected_site,'input_hash'=>$input['hash'],'input_files'=>$input['hashes'],'rows'=>$snapshots];
        $fh = fopen($backup_path, 'x');
        if (!$fh) throw new RuntimeException('Cannot create exclusive rollback backup.');
        $bytes = wp_json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        $written = fwrite($fh, $bytes); fclose($fh);
        if ($written !== strlen($bytes)) throw new RuntimeException('Rollback backup write incomplete; no DB writes performed.');
        echo 'backup=created rows=' . count($snapshots) . "\n";
    } elseif ($backup) echo 'backup=reused rows=' . count($backup['rows'] ?? []) . "\n";
    if (!$backup || array_keys($backup['rows'] ?? []) !== array_keys($items)) throw new RuntimeException('Complete matching backup required.');
    foreach ($snapshots as $slug=>$now) {
        $before = $backup['rows'][$slug];
        if ($now['id'] !== $before['id'] || $now['slug'] !== $before['slug'] || $now['status'] !== $before['status'] || $now['parent'] !== $before['parent'] || $now['body_hash'] !== $before['body_hash']) throw new RuntimeException('URL/body drift: ' . $slug);
    }
    if ($mode === 'verify') {
        $expected = $items;
        foreach (['products.json','third.json','fourth.json'] as $file) foreach (pk27g_read_json($root . '/scripts/product-buyers-27e/' . $file) as $row) {
            $expected[$row['slug']] = ['new_title'=>$row['title'].' | Packrivo','new_h1'=>$row['title'],'new_description'=>$row['description']];
        }
        $fail = []; $titles = []; $sourcing = 0;
        foreach ($expected as $slug=>$e) {
            $p = get_page_by_path($slug); if (!$p) { $fail[]=$slug.':missing'; continue; }
            $title = get_post_meta($p->ID, '_packrivo_document_title', true);
            $titles[] = mb_strtolower($title); if (stripos($title,'sourcing')!==false) $sourcing++;
            preg_match('~<h1\b[^>]*>(.*?)</h1>~is',$p->post_content,$h);
            $h1 = html_entity_decode(wp_strip_all_tags($h[1]??''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($title!==$e['new_title'] || get_post_meta($p->ID,'_yoast_wpseo_title',true)!==$e['new_title'] || $p->post_title!==$e['new_h1'] || $h1!==$e['new_h1'] || get_post_meta($p->ID,'_packrivo_description',true)!==$e['new_description'] || get_post_meta($p->ID,'_yoast_wpseo_metadesc',true)!==$e['new_description']) $fail[]=$slug.':field mismatch';
        }
        echo 'verified_pages='.count($expected).' sourcing_titles='.$sourcing.' duplicate_titles='.(count($titles)-count(array_unique($titles))).' mismatches='.count($fail)."\n";
        echo 'URL_and_non_H1_body_unchanged='.count($snapshots).'/'.count($items)."\n";
        if ($fail || $sourcing || count($titles)!==count(array_unique($titles))) throw new RuntimeException(implode('; ', $fail) ?: 'Title check failed.');
        return;
    }
    $plans = []; $already = 0;
    foreach ($items as $slug=>$item) {
        $now = $snapshots[$slug]; $before = $backup['rows'][$slug];
        $target_content = pk27g_replace_h1($before['post_content'], $item['new_h1']);
        $target_meta = pk27g_target_meta($before, $item);
        $target = ['title'=>$item['new_h1'],'content'=>$target_content,'meta'=>$target_meta];
        $old = ['title'=>$before['post_title'],'content'=>$before['post_content'],'meta'=>$before['meta']];
        $state = ['title'=>$now['post_title'],'content'=>$now['post_content'],'meta'=>$now['meta']];
        if ($state !== $target && $state !== $old) throw new RuntimeException('Unexpected edited fields: '.$slug);
        $desired = in_array($mode,['rollback','rollback-check'],true) ? $old : $target;
        if ($state === $desired) $already++; else $plans[$slug]=$desired;
    }
    if ($mode === 'rollback-check') { echo 'rollback_eligible='.count($plans).' already_restored='.$already.' writes=0'."\n"; return; }
    global $wpdb;
    if ($wpdb->query('START TRANSACTION') === false) throw new RuntimeException('Cannot start transaction.');
    $transaction = true;
    foreach ($plans as $slug=>$target) {
        $id = $snapshots[$slug]['id']; $touched[]=$id;
        pk27g_write($id,$target['title'],$target['content'],$target['meta']);
        if ($mode==='rollback') {
            $before=$backup['rows'][$slug];
            if ($wpdb->update($wpdb->posts,['post_modified'=>$before['post_modified'],'post_modified_gmt'=>$before['post_modified_gmt']],['ID'=>$id])===false) throw new RuntimeException('Timestamp restore failed.');
            clean_post_cache($id);
        }
        $check = pk27g_current($id,$meta_keys);
        if ($check['post_title']!==$target['title'] || $check['post_content']!==$target['content'] || $check['meta']!==$target['meta'] || $check['slug']!==$slug || $check['body_hash']!==$backup['rows'][$slug]['body_hash']) throw new RuntimeException('Write readback failed: '.$slug);
    }
    if ($wpdb->query('COMMIT') === false) throw new RuntimeException('Commit failed.');
    $transaction = false;
    echo $mode.'_updated='.count($plans).' already_current='.$already.' URL_and_body_check=PASS'."\n";
} catch (Throwable $e) {
    if ($transaction) { $wpdb->query('ROLLBACK'); foreach($touched as $id) clean_post_cache($id); }
    WP_CLI::error($e->getMessage());
}
