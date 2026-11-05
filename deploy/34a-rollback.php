<?php
/**
 * 34a 回滚 — 读 deploy/backup/34a-before-<timestamp>.csv，把 title / meta description / H1 还原成写前原值。
 *
 * 用法：
 *   wp eval-file deploy/34a-rollback.php                        # 用 backup/ 里最新的 34a-before-*.csv
 *   wp eval-file deploy/34a-rollback.php /path/34a-before-x.csv
 *   wp eval-file deploy/34a-rollback.php --file=/path/x.csv dry-run
 * 环境变量：
 *   PK34A_BACKUP_DIR  备份目录，默认 __DIR__/backup
 *
 * 备份行的 field 取值：
 *   _yoast_wpseo_title / _yoast_wpseo_metadesc   old_value 空串表示写前没有这条 meta → 回滚时删掉
 *   h1                                            old_value 是 <h1> 内层原始文本；new_value 为空表示这页 H1 没写过
 *   idx:<rowid>:<column>                          wp_yoast_indexable 单列原值
 */

if (!defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "This script must run through WP-CLI eval-file.\n");
    exit(1);
}

global $wpdb;

$argv = (isset($args) && is_array($args)) ? $args : array();
$dry = false;
$file = '';
foreach ($argv as $a) {
    $a = (string) $a;
    if ($a === 'dry-run' || $a === '--dry-run') {
        $dry = true;
    } elseif (strpos($a, '--file=') === 0) {
        $file = substr($a, 7);
    } elseif (strpos($a, '--') !== 0) {
        $file = $a;
    }
}
if ($file === '') {
    $dir = getenv('PK34A_BACKUP_DIR');
    if (!$dir) {
        $dir = __DIR__ . '/backup';
    }
    $cand = glob(rtrim($dir, '/\\') . '/34a-before-*.csv');
    if (!$cand) {
        WP_CLI::error("No 34a-before-*.csv under $dir; nothing rolled back.");
    }
    sort($cand);
    $file = end($cand);
}
if (!is_readable($file)) {
    WP_CLI::error("Backup file not readable: $file; nothing rolled back.");
}
$indexable_table = $wpdb->prefix . 'yoast_indexable';

$fh = fopen($file, 'rb');
$header = fgetcsv($fh, 0, ',', '"', '');
if (!is_array($header)) {
    fclose($fh);
    WP_CLI::error('Backup has no header row; nothing rolled back.');
}
$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
$col = array();
foreach ($header as $i => $n) {
    $col[trim((string) $n)] = $i;
}
foreach (array('post_id', 'url', 'field', 'old_value', 'new_value') as $need) {
    if (!isset($col[$need])) {
        fclose($fh);
        WP_CLI::error("Backup is missing column [$need]; nothing rolled back.");
    }
}
$by_post = array();
while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
    if ($r === array(null) || (count($r) === 1 && trim((string) $r[0]) === '')) {
        continue;
    }
    $id = (int) $r[$col['post_id']];
    if (!$id) {
        continue;
    }
    $by_post[$id][] = array(
        'url' => (string) $r[$col['url']],
        'field' => (string) $r[$col['field']],
        'old' => isset($r[$col['old_value']]) ? (string) $r[$col['old_value']] : '',
        'new' => isset($r[$col['new_value']]) ? (string) $r[$col['new_value']] : '',
    );
}
fclose($fh);
if (!$by_post) {
    WP_CLI::error("Backup $file has no usable rows; nothing rolled back.");
}

function pk34a_r_norm($s) {
    $s = html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = strip_tags($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

$plan_meta = array();
$plan_h1 = array();
$plan_idx = array();
$skipped = array();
foreach ($by_post as $id => $items) {
    foreach ($items as $it) {
        $f = $it['field'];
        if ($f === '_yoast_wpseo_title' || $f === '_yoast_wpseo_metadesc' || $f === '_packrivo_document_title' || $f === '_packrivo_description') {
            $plan_meta[] = array('id' => $id, 'key' => $f, 'old' => $it['old'], 'new' => $it['new']);
        } elseif ($f === 'h1') {
            if ($it['new'] === '' && $it['old'] === '') {
                continue;
            }
            if ($it['new'] === '') {
                $skipped[] = "$id h1 new_value 为空（这页 H1 未写入），无需回滚";
                continue;
            }
            $plan_h1[] = array('id' => $id, 'old' => $it['old'], 'new' => $it['new'], 'url' => $it['url']);
        } elseif (strpos($f, 'idx:') === 0) {
            $parts = explode(':', $f);
            if (count($parts) !== 3) {
                $skipped[] = "$id 无法解析 indexable 字段 $f";
                continue;
            }
            $plan_idx[] = array('id' => $id, 'row' => (int) $parts[1], 'column' => $parts[2], 'old' => $it['old']);
        } else {
            $skipped[] = "$id 未知字段 $f";
        }
    }
}

WP_CLI::line('rollback_file=' . $file);
WP_CLI::line('posts=' . count($by_post) . ' meta_rows=' . count($plan_meta) . ' h1_rows=' . count($plan_h1) . ' idx_rows=' . count($plan_idx));
foreach ($skipped as $s) {
    WP_CLI::line('SKIP ' . $s);
}
if ($dry) {
    WP_CLI::line('dry_run=1 (no database change)');
    WP_CLI::success('34a rollback dry-run finished.');
    return;
}

$wpdb->query('START TRANSACTION');
$meta_done = 0;
foreach ($plan_meta as $mp) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s LIMIT 1", $mp['id'], $mp['key']), ARRAY_A);
    if ($mp['old'] === '') {
        if ($row) {
            $ok = $wpdb->delete($wpdb->postmeta, array('meta_id' => (int) $row['meta_id']));
            if ($ok !== 1) {
                $wpdb->query('ROLLBACK');
                WP_CLI::error("Delete meta failed for post {$mp['id']} {$mp['key']}; rolled back.");
            }
            $meta_done++;
        }
        continue;
    }
    if ($row) {
        if ((string) $row['meta_value'] === $mp['old']) {
            continue;
        }
        $ok = $wpdb->update($wpdb->postmeta, array('meta_value' => $mp['old']), array('meta_id' => (int) $row['meta_id']));
    } else {
        $ok = $wpdb->insert($wpdb->postmeta, array('post_id' => $mp['id'], 'meta_key' => $mp['key'], 'meta_value' => $mp['old']));
    }
    if ($ok !== 1) {
        $wpdb->query('ROLLBACK');
        WP_CLI::error("Restore meta failed for post {$mp['id']} {$mp['key']}; rolled back.");
    }
    $meta_done++;
}

$h1_done = 0;
$h1_bad = array();
foreach ($plan_h1 as $hp) {
    $content = (string) $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d", $hp['id']));
    if ($content === '') {
        $h1_bad[] = $hp['id'] . ' post_content 读不到';
        continue;
    }
    if (!preg_match_all('~<h1\b[^>]*>([\s\S]*?)</h1>~i', $content, $m, PREG_OFFSET_CAPTURE) || count($m[1]) !== 1) {
        $h1_bad[] = $hp['id'] . ' 当前 <h1> 数量不为 1';
        continue;
    }
    $cur_raw = $m[1][0][0];
    $cur_off = $m[1][0][1];
    if (pk34a_r_norm($cur_raw) === pk34a_r_norm($hp['old'])) {
        continue;
    }
    if (pk34a_r_norm($cur_raw) !== pk34a_r_norm($hp['new'])) {
        $h1_bad[] = $hp['id'] . ' 当前 H1 既不是新值也不是旧值: ' . pk34a_r_norm($cur_raw);
        continue;
    }
    $old_raw = $hp['old'];
    if (html_entity_decode($cur_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8') !== $cur_raw) {
        $old_raw = htmlspecialchars($old_raw, ENT_QUOTES, 'UTF-8');
    }
    $new_content = substr_replace($content, $old_raw, $cur_off, strlen($cur_raw));
    $ok = $wpdb->update($wpdb->posts, array('post_content' => $new_content), array('ID' => $hp['id'], 'post_content' => $content));
    if ($ok !== 1) {
        $wpdb->query('ROLLBACK');
        WP_CLI::error("Restore H1 failed for post {$hp['id']}; transaction rolled back.");
    }
    $h1_done++;
}

$idx_done = 0;
foreach ($plan_idx as $ip) {
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$indexable_table} WHERE id=%d", $ip['row']));
    if (!$exists) {
        continue;
    }
    $cur = $wpdb->get_var($wpdb->prepare("SELECT {$ip['column']} FROM {$indexable_table} WHERE id=%d", $ip['row']));
    if ((string) $cur === $ip['old']) {
        continue;
    }
    $ok = $wpdb->update($indexable_table, array($ip['column'] => $ip['old']), array('id' => $ip['row']));
    if ($ok !== 1) {
        $wpdb->query('ROLLBACK');
        WP_CLI::error("Restore indexable failed for row {$ip['row']} col {$ip['column']}; rolled back.");
    }
    $idx_done++;
}
if ($wpdb->query('COMMIT') === false) {
    $wpdb->query('ROLLBACK');
    WP_CLI::error('Commit failed; transaction rolled back.');
}
foreach (array_keys($by_post) as $id) {
    clean_post_cache($id);
    wp_cache_delete($id, 'post_meta');
}
wp_cache_flush();

WP_CLI::line('restored meta=' . $meta_done . ' h1=' . $h1_done . ' idx=' . $idx_done . ' h1_problems=' . count($h1_bad));
foreach ($h1_bad as $b) {
    WP_CLI::line('H1_PROBLEM ' . $b);
}
WP_CLI::line('rolled_back=1');
WP_CLI::success('34a rollback finished.');
