<?php
/**
 * Exact, page-only MOQ text migration. Run with WP-CLI eval-file after backup.
 * The adjacent plan binds each exact old/new string to its page and field.
 */
global $wpdb;
$file = __DIR__ . '/32a-moq-plan.json';
$plan = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
if (!is_array($plan) || ($plan['version'] ?? null) !== 1 || empty($plan['operations'])) {
    WP_CLI::error('Missing or invalid adjacent 32a-moq-plan.json; nothing changed.');
}
$groups = [];
foreach ($plan['operations'] as $op) {
    if (!isset($op['id'], $op['slug'], $op['field'], $op['old'], $op['new']) || $op['old'] === '' || $op['old'] === $op['new']) {
        WP_CLI::error('Invalid migration entry; nothing changed.');
    }
    if (!in_array($op['field'], ['post_content','post_title','_yoast_wpseo_title','_yoast_wpseo_metadesc','_yoast_wpseo_opengraph-description','_packrivo_document_title','_packrivo_description','_packrivo_json_ld','_pkpw_legacy_body'], true)) {
        WP_CLI::error('Unexpected field in migration plan; nothing changed.');
    }
    $key = $op['id'] . ':' . $op['field'] . ':' . ($op['meta_id'] ?? '');
    $groups[$key][] = $op;
}
$pending = []; $counts_before = ['MOQ 500'=>0, '500 pieces per'=>0];
$counts_after = $counts_before; $replaced = 0; $changed_posts = []; $meta_ops = [];
foreach ($groups as $items) {
    $first = $items[0]; $id = (int) $first['id'];
    $post = $wpdb->get_row($wpdb->prepare("SELECT ID,post_name,post_type,post_title,post_content FROM {$wpdb->posts} WHERE ID=%d", $id), ARRAY_A);
    if (!$post || $post['post_type'] !== 'page' || $post['post_name'] !== $first['slug']) {
        WP_CLI::error("Page identity mismatch for ID $id; nothing changed.");
    }
    $field = $first['field'];
    if (in_array($field, ['_yoast_wpseo_title','_yoast_wpseo_metadesc','_yoast_wpseo_opengraph-description'], true)) {
        foreach ($items as $op) $meta_ops[$id][] = $op;
    }
    if (in_array($field, ['post_content','post_title'], true)) {
        $old_value = $post[$field];
    } else {
        $meta = $wpdb->get_row($wpdb->prepare("SELECT meta_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_id=%d AND post_id=%d", (int) ($first['meta_id'] ?? 0), $id), ARRAY_A);
        if (!$meta || $meta['meta_key'] !== $field) WP_CLI::error("Meta identity mismatch for ID $id; nothing changed.");
        $old_value = $meta['meta_value'];
    }
    $value = $old_value;
    foreach ($counts_before as $pattern => $_) $counts_before[$pattern] += substr_count($old_value, $pattern);
    foreach ($items as $op) {
        $count = substr_count($value, $op['old']);
        if ($count === 0) {
            if (strpos($value, $op['new']) === false) WP_CLI::error("Exact old/new text missing for page {$first['slug']} field $field; nothing changed.");
            continue;
        }
        $value = str_replace($op['old'], $op['new'], $value, $actual);
        $replaced += $actual;
    }
    foreach ($counts_after as $pattern => $_) $counts_after[$pattern] += substr_count($value, $pattern);
    if ($value !== $old_value) {
        $pending[] = ['id'=>$id,'field'=>$field,'meta_id'=>(int) ($first['meta_id'] ?? 0),'before'=>$old_value,'after'=>$value];
        $changed_posts[$id] = true;
    }
}
$indexable_table = $wpdb->prefix . 'yoast_indexable';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($indexable_table))) !== $indexable_table) {
    WP_CLI::error('Yoast indexable table missing; nothing changed.');
}
$cache_pending = []; $cache_replaced = 0;
foreach ($meta_ops as $id => $items) {
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id,title,description,open_graph_title,open_graph_description,twitter_title,twitter_description FROM {$indexable_table} WHERE object_type='post' AND object_id=%d", $id), ARRAY_A);
    if ($rows === null) WP_CLI::error('Cannot inspect Yoast indexable cache; nothing changed.');
    foreach ($rows as $row) {
        $data=[]; $where=['id'=>(int)$row['id']];
        foreach ($items as $op) {
            if ($op['field']==='_yoast_wpseo_title') $columns=['title','open_graph_title','twitter_title'];
            elseif ($op['field']==='_yoast_wpseo_metadesc') $columns=['description','open_graph_description','twitter_description'];
            else $columns=['open_graph_description'];
            foreach ($columns as $column) {
                $value = array_key_exists($column,$data) ? $data[$column] : $row[$column];
                if (!is_string($value) || strpos($value,$op['old'])===false) continue;
                if (!array_key_exists($column,$where)) $where[$column]=$row[$column];
                $data[$column]=str_replace($op['old'],$op['new'],$value,$count);
                $cache_replaced += $count;
            }
        }
        if ($data) $cache_pending[]=['data'=>$data,'where'=>$where];
    }
}
$site_moq = get_option('packrivo_moq', null);
if ($site_moq !== null && (!is_scalar($site_moq) || !in_array((string)$site_moq, ['250','500'], true))) {
    WP_CLI::error('Unexpected packrivo_moq value; inspect the site setting before changing anything.');
}
$update_site_moq = $site_moq === null || (int)$site_moq !== 250;
WP_CLI::line('before MOQ 500=' . $counts_before['MOQ 500'] . ' | 500 pieces per=' . $counts_before['500 pieces per']);
WP_CLI::line('site_moq_before=' . ($site_moq === null ? 'missing(default 500)' : (string)$site_moq));
// All identity and text checks above finish before any write.
if ($pending || $cache_pending || $update_site_moq) {
    if ($wpdb->query('START TRANSACTION') === false) WP_CLI::error('Could not start transaction; nothing changed.');
    foreach ($pending as $row) {
        if (in_array($row['field'], ['post_content','post_title'], true)) {
            $table=$wpdb->posts; $where=['ID'=>$row['id'], $row['field']=>$row['before']];
            $data=[$row['field']=>$row['after']];
        } else {
            $table=$wpdb->postmeta; $where=['meta_id'=>$row['meta_id'],'post_id'=>$row['id'],'meta_value'=>$row['before']];
            $data=['meta_value'=>$row['after']];
        }
        $result=$wpdb->update($table,$data,$where);
        if ($result !== 1) {
            $wpdb->query('ROLLBACK');
            WP_CLI::error('Concurrent change or database error; transaction rolled back.');
        }
    }
    foreach ($cache_pending as $row) {
        if ($wpdb->update($indexable_table,$row['data'],$row['where']) !== 1) {
            $wpdb->query('ROLLBACK');
            WP_CLI::error('Concurrent indexable change or database error; transaction rolled back.');
        }
    }
    if ($update_site_moq && !update_option('packrivo_moq',250,false)) {
        $wpdb->query('ROLLBACK');
        WP_CLI::error('Cannot update packrivo_moq; transaction rolled back.');
    }
    if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); WP_CLI::error('Commit failed.'); }
    foreach (array_keys($changed_posts) as $id) { clean_post_cache($id); wp_cache_delete($id,'post_meta'); }
}
WP_CLI::line('after MOQ 500=' . $counts_after['MOQ 500'] . ' | 500 pieces per=' . $counts_after['500 pieces per']);
WP_CLI::line('replaced=' . $replaced . ' | fields=' . count($pending) . ' | pages=' . count($changed_posts));
WP_CLI::line('yoast_cache_replaced=' . $cache_replaced . ' | indexable_rows=' . count($cache_pending));
WP_CLI::line('site_moq_after=' . (int)get_option('packrivo_moq',0) . ' | site_moq_updated=' . ($update_site_moq ? '1' : '0'));
