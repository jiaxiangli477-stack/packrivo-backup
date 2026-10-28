<?php
/**
 * 演练环境准备：按 slug 把 27g/27i 的 rollback 依据写回，得到"上线前"的内容状态。
 * 不按 ID 写——演练库是从本地另一份库导入的，ID 与依据里的 ID 不是一回事。
 * 只用于本地演练环境，不参与 RUNBOOK 的任何一步。
 * 用法：wp eval-file prep-restore-slug.php <rollback.json>
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "must run under wp eval-file\n"); exit(1); }
global $wpdb;

$file = $args[0] ?? '';
if ($file === '' || !is_readable($file)) { fwrite(STDERR, "cannot read $file\n"); exit(1); }
$j = json_decode(file_get_contents($file), true);
if (!is_array($j) || !isset($j['rows'])) { fwrite(STDERR, "no rows in $file\n"); exit(1); }

$n = 0; $missing = []; $noContent = 0;
foreach ($j['rows'] as $key => $r) {
    $slug = $r['slug'] ?? (is_string($key) && $key !== '' && $key[0] !== '/' ? $key : '');
    if ($slug === '') { $missing[] = (string) $key; continue; }

    $post = get_page_by_path($slug, OBJECT, ['page', 'post']);
    if (!$post) { $missing[] = $slug; continue; }

    $data = ['ID' => $post->ID];
    foreach (['post_title', 'post_content', 'post_excerpt', 'post_status'] as $f) {
        if (array_key_exists($f, $r) && $r[$f] !== null) { $data[$f] = $r[$f]; }
    }
    if (array_key_exists('parent', $r)) { $data['post_parent'] = (int) $r['parent']; }
    if (!isset($r['post_content'])) { $noContent++; }   // 27i 只存了正文哈希，正文写不回去
    if (count($data) > 1) { wp_update_post($data, true); }

    foreach (($r['meta'] ?? []) as $k => $v) {
        if (is_array($v) && count($v) === 1) { $v = reset($v); }
        update_post_meta($post->ID, $k, $v);
    }
    $n++;
}

clean_post_cache(0);
wp_cache_flush();
printf("restore_slug=%d missing=%d 无正文依据=%d\n", $n, count($missing), $noContent);
if ($missing) { printf("缺: %s\n", implode(', ', array_slice($missing, 0, 10))); }
