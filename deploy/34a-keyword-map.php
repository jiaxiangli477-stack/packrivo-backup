<?php
/**
 * 34a — 把定稿关键词表写进现有英文页面，只动三处：Yoast title、Yoast meta description、页面上的 <h1>。
 *
 * 调用：
 *   wp eval-file deploy/34a-keyword-map.php                        # 真跑
 *   wp eval-file deploy/34a-keyword-map.php dry-run                # 只算不写
 *   wp eval-file deploy/34a-keyword-map.php /path/map.csv dry-run  # 指定词表
 *   wp eval-file deploy/34a-keyword-map.php --map=/path/map.csv --dry-run
 * 环境变量：
 *   PK34A_BACKUP_DIR  备份目录，默认 __DIR__/backup
 *   PK34A_OUT_DIR     new-strings 输出目录，默认 仓库根/dsh-out
 *
 * 字段来源（已实测）：
 *   title  = postmeta._yoast_wpseo_title + wp_yoast_indexable.title/open_graph_title/twitter_title
 *   meta   = postmeta._yoast_wpseo_metadesc + wp_yoast_indexable.description/open_graph_description/twitter_description
 *   H1     = post_content 里唯一的 <h1 ...>…</h1> 的内层文本（主题 page.php 不再输出 H1，原生页的快照自带 H1）
 *
 * 写入规则：
 *   现库值 == 新值              → skip（幂等，重复跑不会重复写）
 *   现库值 == 词表现值          → update
 *   仅 MOQ 250/500 差异后相等   → update（32a/32a-2 已把标题里的 500 改成 250，词表现值是旧快照）
 *   其它                        → conflict，只报告不写
 * 8 个排名页：新 title 去掉 250/500 后必须与现库 title 完全一致，任何一条不符则整轮中止、一个字节都不写。
 */

if (!defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "This script must run through WP-CLI eval-file.\n");
    exit(1);
}

global $wpdb;

$argv = (isset($args) && is_array($args)) ? $args : array();
$dry = false;
$map_path = '';
foreach ($argv as $a) {
    $a = (string) $a;
    if ($a === 'dry-run' || $a === '--dry-run') {
        $dry = true;
    } elseif (strpos($a, '--map=') === 0) {
        $map_path = substr($a, 6);
    } elseif (strpos($a, '--') !== 0) {
        $map_path = $a;
    }
}
if ($map_path === '') {
    $map_path = __DIR__ . '/34a-keyword-map.csv';
}
if (!is_readable($map_path)) {
    WP_CLI::error("Keyword map not readable: $map_path; nothing changed.");
}
$backup_dir = getenv('PK34A_BACKUP_DIR');
if (!$backup_dir) {
    $backup_dir = __DIR__ . '/backup';
}
$out_dir = getenv('PK34A_OUT_DIR');
if (!$out_dir) {
    $out_dir = dirname(__DIR__) . '/dsh-out';
}

// ---------- 规范化 ----------
function pk34a_norm($s) {
    $s = html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = strip_tags($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}
// 去掉 MOQ 数字/短语后比较：用于识别「只有 500→250 差异」的旧快照
function pk34a_base($s) {
    $s = pk34a_norm($s);
    $s = preg_replace('/\(\s*MOQ\s*(?:250|500)\s*\)/iu', '', $s);
    $s = preg_replace('/\bMOQ\s*:?\s*(?:250|500)\b/iu', '', $s);
    $s = preg_replace('/\b(?:250|500)\b/u', '', $s);
    $s = preg_replace('/\s{2,}/u', ' ', $s);
    return strtolower(trim($s, " \t,;:-|–—"));
}
function pk34a_path($url) {
    $p = parse_url(trim($url), PHP_URL_PATH);
    if (!is_string($p) || $p === '') {
        return '/';
    }
    if (substr($p, -1) !== '/') {
        $p .= '/';
    }
    return $p;
}
function pk34a_csv_write($rows) {
    $buf = '';
    foreach ($rows as $row) {
        $cells = array();
        foreach ($row as $c) {
            $c = str_replace(array("\r\n", "\r"), ' ', (string) $c);
            if (strpos($c, ',') !== false || strpos($c, '"') !== false || strpos($c, "\n") !== false) {
                $c = '"' . str_replace('"', '""', $c) . '"';
            }
            $cells[] = $c;
        }
        $buf .= implode(',', $cells) . "\r\n";
    }
    return $buf;
}

// ---------- 读词表 ----------
$fh = fopen($map_path, 'rb');
if (!$fh) {
    WP_CLI::error("Cannot open $map_path; nothing changed.");
}
$header = fgetcsv($fh, 0, ',', '"', '');
if (!is_array($header)) {
    fclose($fh);
    WP_CLI::error('Keyword map has no header row; nothing changed.');
}
$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
$col = array();
foreach ($header as $i => $name) {
    $col[trim((string) $name)] = $i;
}
foreach (array('url', '现 title', '现 H1', '新 title', '新 meta', '新 H1') as $need) {
    if (!isset($col[$need])) {
        fclose($fh);
        WP_CLI::error("Keyword map is missing column [$need]; nothing changed.");
    }
}
$rows = array();
while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
    if ($r === array(null) || (count($r) === 1 && trim((string) $r[0]) === '')) {
        continue;
    }
    $url = trim((string) $r[$col['url']]);
    if ($url === '') {
        continue;
    }
    $rows[] = array(
        'url' => $url,
        'path' => pk34a_path($url),
        'type' => isset($col['页面类型']) && isset($r[$col['页面类型']]) ? trim((string) $r[$col['页面类型']]) : '',
        'old_title' => isset($r[$col['现 title']]) ? (string) $r[$col['现 title']] : '',
        'old_h1' => isset($r[$col['现 H1']]) ? (string) $r[$col['现 H1']] : '',
        'new_title' => isset($r[$col['新 title']]) ? trim((string) $r[$col['新 title']]) : '',
        'new_meta' => isset($r[$col['新 meta']]) ? trim((string) $r[$col['新 meta']]) : '',
        'new_h1' => isset($r[$col['新 H1']]) ? trim((string) $r[$col['新 H1']]) : '',
    );
}
fclose($fh);
if (!$rows) {
    WP_CLI::error('Keyword map has no data rows; nothing changed.');
}

$indexable_table = $wpdb->prefix . 'yoast_indexable';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($indexable_table))) !== $indexable_table) {
    WP_CLI::error("Yoast indexable table $indexable_table missing; nothing changed.");
}

// ---------- 解析 URL → 页面，取现库值 ----------
$pages = array();
$missing = array();
foreach ($rows as $row) {
    $path = $row['path'];
    $id = (int) url_to_postid(home_url($path));
    if (!$id) {
        $missing[] = $path;
        continue;
    }
    $post = get_post($id);
    if (!$post || $post->post_type !== 'page') {
        $missing[] = $path;
        continue;
    }
    $permalink_path = pk34a_path(get_permalink($id));
    if ($permalink_path !== $path) {
        $missing[] = $path;
        continue;
    }
    $row['id'] = $id;
    $row['slug'] = $post->post_name;
    $row['content'] = $post->post_content;
    $row['cur_title'] = metadata_exists('post', $id, '_yoast_wpseo_title') ? (string) get_post_meta($id, '_yoast_wpseo_title', true) : '';
    $row['cur_title_exists'] = metadata_exists('post', $id, '_yoast_wpseo_title');
    $row['cur_meta'] = metadata_exists('post', $id, '_yoast_wpseo_metadesc') ? (string) get_post_meta($id, '_yoast_wpseo_metadesc', true) : '';
    $row['cur_meta_exists'] = metadata_exists('post', $id, '_yoast_wpseo_metadesc');
    // 主题 functions.php:82-105 对这七个 hub 页（含首页）用 _packrivo_document_title /
    // _packrivo_description 覆盖 wpseo_title / wpseo_metadesc（priority 1100），
    // 因此这几页的 title/meta 真实字段源是这两个 meta，不是 Yoast 的键。
    $row['is_hub'] = in_array(trim($path, '/'), array('products', 'jewelry-boxes', 'mailer-boxes', 'samples', 'blog', 'about', 'inquiry'), true)
        || (int) get_option('page_on_front') === $id;
    $row['cur_doc_title'] = metadata_exists('post', $id, '_packrivo_document_title') ? (string) get_post_meta($id, '_packrivo_document_title', true) : '';
    $row['cur_doc_title_exists'] = metadata_exists('post', $id, '_packrivo_document_title');
    $row['cur_desc'] = metadata_exists('post', $id, '_packrivo_description') ? (string) get_post_meta($id, '_packrivo_description', true) : '';
    $row['cur_desc_exists'] = metadata_exists('post', $id, '_packrivo_description');
    $row['h1_count'] = 0;
    $row['h1_text'] = '';
    $row['h1_offset'] = 0;
    if (preg_match_all('~<h1\b[^>]*>([\s\S]*?)</h1>~i', $row['content'], $m, PREG_OFFSET_CAPTURE)) {
        $row['h1_count'] = count($m[1]);
        $row['h1_text'] = $m[1][0][0];
        $row['h1_offset'] = $m[1][0][1];
    }
    $pages[$path] = $row;
}

// ---------- 8 个排名页断言门：先算，任何一条不过就整轮中止 ----------
$rank_paths = array('/labels/', '/foldable-gift-boxes/', '/jewelry-boxes/', '/inserts/', '/products/', '/services/', '/guide-jewelry-insert-fit/', '/product-magnetic-jewelry-box/');
$gate_lines = array();
$gate_fail = array();
foreach ($rank_paths as $rp) {
    if (!isset($pages[$rp])) {
        $gate_fail[] = "$rp 未解析到页面";
        continue;
    }
    $p = $pages[$rp];
    if (pk34a_base($p['new_title']) !== pk34a_base($p['cur_title'])) {
        $gate_fail[] = sprintf('%s 新 title 去 MOQ 后 [%s] != 现库 [%s]', $rp, pk34a_base($p['new_title']), pk34a_base($p['cur_title']));
        continue;
    }
    $gate_lines[] = "$rp strip_eq";
}
if ($gate_fail) {
    foreach ($gate_fail as $line) {
        WP_CLI::line('GATE_FAIL ' . $line);
    }
    WP_CLI::error('Rank-page assertion failed (' . count($gate_fail) . '/' . count($rank_paths) . '); nothing changed.');
}

// ---------- 逐页定动作 ----------
$acts = array();   // [id][field] = ['action','before','after',...]
$drop = array();   // 被跳过的条目
$conflicts = array();
$drift = array();   // 词表现值与库值只差 MOQ 250/500 的页面（32a/32a-2 已经改过库里的数量）
$counts = array(
    'title_update' => 0, 'title_drift_update' => 0, 'title_skip' => 0, 'title_conflict' => 0, 'title_empty' => 0,
    'meta_update' => 0, 'meta_skip' => 0,
    'h1_update' => 0, 'h1_drift_update' => 0, 'h1_skip' => 0, 'h1_conflict' => 0, 'h1_no_content_h1' => 0,
    'hub_title_update' => 0, 'hub_title_drift_update' => 0, 'hub_title_skip' => 0, 'hub_title_conflict' => 0,
    'hub_desc_update' => 0, 'hub_desc_skip' => 0,
);
foreach ($pages as $path => $p) {
    $id = $p['id'];

    // title
    $new = pk34a_norm($p['new_title']);
    if ($new === '') {
        $counts['title_empty']++;
    } elseif (pk34a_norm($p['cur_title']) === $new) {
        $counts['title_skip']++;
    } elseif (pk34a_norm($p['cur_title']) === pk34a_norm($p['old_title'])) {
        $counts['title_update']++;
        $acts[$id]['title'] = array('before' => $p['cur_title'], 'after' => $p['new_title'], 'exists' => $p['cur_title_exists']);
    } elseif (pk34a_base($p['cur_title']) === pk34a_base($p['old_title']) && pk34a_base($p['cur_title']) !== '') {
        $counts['title_drift_update']++;
        $drift[] = array($path, 'title', $p['cur_title'], $p['old_title']);
        $acts[$id]['title'] = array('before' => $p['cur_title'], 'after' => $p['new_title'], 'exists' => $p['cur_title_exists']);
    } else {
        $counts['title_conflict']++;
        $conflicts[] = array($path, 'title', $p['cur_title'], $p['old_title'], $p['new_title']);
    }

    // meta description（词表对所有 141 页都给了新值，没有「现 meta」列，直接覆盖）
    if (pk34a_norm($p['cur_meta']) === pk34a_norm($p['new_meta'])) {
        $counts['meta_skip']++;
    } else {
        $counts['meta_update']++;
        $acts[$id]['meta'] = array('before' => $p['cur_meta'], 'after' => $p['new_meta'], 'exists' => $p['cur_meta_exists']);
    }

    // hub 页：主题按 URL 白名单用 _packrivo_document_title / _packrivo_description 覆盖
    // Yoast 的 title/meta，写 Yoast 键对渲染结果无效，所以这两个字段要写真实字段源。
    if (!empty($p['is_hub'])) {
        if (pk34a_norm($p['cur_doc_title']) === pk34a_norm($p['new_title'])) {
            $counts['hub_title_skip']++;
        } elseif (pk34a_norm($p['cur_doc_title']) === pk34a_norm($p['old_title'])) {
            $counts['hub_title_update']++;
            $acts[$id]['hub_title'] = array('before' => $p['cur_doc_title'], 'after' => $p['new_title'], 'exists' => $p['cur_doc_title_exists']);
        } elseif (pk34a_base($p['cur_doc_title']) === pk34a_base($p['old_title']) && pk34a_base($p['cur_doc_title']) !== '') {
            $counts['hub_title_drift_update']++;
            $drift[] = array($path, 'hub_document_title', $p['cur_doc_title'], $p['old_title']);
            $acts[$id]['hub_title'] = array('before' => $p['cur_doc_title'], 'after' => $p['new_title'], 'exists' => $p['cur_doc_title_exists']);
        } else {
            $counts['hub_title_conflict']++;
            $conflicts[] = array($path, 'hub_document_title', $p['cur_doc_title'], $p['old_title'], $p['new_title']);
        }
        if (pk34a_norm($p['cur_desc']) === pk34a_norm($p['new_meta'])) {
            $counts['hub_desc_skip']++;
        } else {
            $counts['hub_desc_update']++;
            $acts[$id]['hub_desc'] = array('before' => $p['cur_desc'], 'after' => $p['new_meta'], 'exists' => $p['cur_desc_exists']);
        }
    }

    // H1
    $new_h1 = pk34a_norm($p['new_h1']);    if ($p['h1_count'] === 0) {
        $counts['h1_no_content_h1']++;
        $drop[] = array($path, 'h1', 'post_content 里没有 <h1>，该页 H1 由主题/词典输出');
    } elseif ($p['h1_count'] > 1) {
        $counts['h1_conflict']++;
        $conflicts[] = array($path, 'h1', 'multi(' . $p['h1_count'] . ')', $p['old_h1'], $p['new_h1']);
    } elseif ($new_h1 === '') {
        $counts['h1_skip']++;
    } elseif (pk34a_norm($p['h1_text']) === $new_h1) {
        $counts['h1_skip']++;
    } elseif (pk34a_norm($p['h1_text']) === pk34a_norm($p['old_h1'])) {
        $counts['h1_update']++;
        $acts[$id]['h1'] = array('before' => $p['h1_text'], 'after' => $p['new_h1'], 'offset' => $p['h1_offset'], 'content' => $p['content']);
    } elseif (pk34a_base($p['h1_text']) === pk34a_base($p['old_h1']) && pk34a_base($p['h1_text']) !== '') {
        $counts['h1_drift_update']++;
        $drift[] = array($path, 'h1', $p['h1_text'], $p['old_h1']);
        $acts[$id]['h1'] = array('before' => $p['h1_text'], 'after' => $p['new_h1'], 'offset' => $p['h1_offset'], 'content' => $p['content']);
    } else {
        $counts['h1_conflict']++;
        $conflicts[] = array($path, 'h1', $p['h1_text'], $p['old_h1'], $p['new_h1']);
    }
}

// ---------- indexable 现状 ----------
$idx = array();
foreach ($pages as $path => $p) {
    $rowsx = $wpdb->get_results($wpdb->prepare(
        "SELECT id,title,description,open_graph_title,open_graph_description,twitter_title,twitter_description FROM {$indexable_table} WHERE object_type='post' AND object_id=%d",
        $p['id']
    ), ARRAY_A);
    $idx[$p['id']] = is_array($rowsx) ? $rowsx : array();
}

// ---------- 34a-new-strings.csv（追加 + 去重）----------
$strings = array();
$existing_keys = array();
$strings_path = rtrim($out_dir, '/\\') . '/34a-new-strings.csv';
if (is_readable($strings_path)) {
    $efh = fopen($strings_path, 'rb');
    $ehead = fgetcsv($efh, 0, ',', '"', '');
    while (($er = fgetcsv($efh, 0, ',', '"', '')) !== false) {
        if (!$er || count($er) < 4) {
            continue;
        }
        $existing_keys[$er[0] . "\x1f" . $er[1] . "\x1f" . $er[2] . "\x1f" . $er[3]] = $er;
    }
    fclose($efh);
    $strings = $existing_keys;
}
$new_pairs = 0;
foreach ($pages as $path => $p) {
    $pairs = array(
        array('yoast_title', $p['cur_title'], $p['new_title']),
        array('yoast_meta', $p['cur_meta'], $p['new_meta']),
        array('h1', $p['h1_count'] === 1 ? $p['h1_text'] : '', $p['new_h1']),
    );
    if (!empty($p['is_hub'])) {
        $pairs[] = array('hub_title', $p['cur_doc_title'], $p['new_title']);
        $pairs[] = array('hub_desc', $p['cur_desc'], $p['new_meta']);
    }
    foreach ($pairs as $pair) {
        list($source, $old, $newv) = $pair;
        $old = pk34a_norm($old);
        $newv = pk34a_norm($newv);
        if ($old === '' || $newv === '' || $old === $newv) {
            continue;
        }
        $key = $source . "\x1f" . $path . "\x1f" . $old . "\x1f" . $newv;
        if (isset($strings[$key])) {
            continue;
        }
        $strings[$key] = array($source, $path, $old, $newv);
        $new_pairs++;
    }
}
$strings_rows = count($strings);
$strings_body = pk34a_csv_write(array_merge(array(array('source', 'url', 'old_en', 'new_en')), array_values($strings)));

// ---------- 汇总输出 ----------
$changed_pages = count($acts);
$changed_fields = 0;
foreach ($acts as $f) {
    $changed_fields += count($f);
}
WP_CLI::line('map=' . $map_path);
WP_CLI::line('map_rows=' . count($rows) . ' resolved=' . count($pages) . ' missing=' . count($missing));
if ($missing) {
    WP_CLI::line('missing_list=' . implode(' ', $missing));
}
WP_CLI::line('gate_rank8=' . (count($gate_lines) === count($rank_paths) ? 'pass' : 'fail') . ' (' . count($gate_lines) . '/' . count($rank_paths) . ' strip_eq)');
foreach ($gate_lines as $line) {
    WP_CLI::line('  gate ' . $line);
}
WP_CLI::line('title: update=' . $counts['title_update'] . ' drift_update=' . $counts['title_drift_update'] . ' skip=' . $counts['title_skip'] . ' conflict=' . $counts['title_conflict'] . ' empty_new=' . $counts['title_empty']);
WP_CLI::line('meta:  update=' . $counts['meta_update'] . ' skip=' . $counts['meta_skip']);
WP_CLI::line('hub:   title update=' . $counts['hub_title_update'] . ' drift_update=' . $counts['hub_title_drift_update'] . ' skip=' . $counts['hub_title_skip'] . ' conflict=' . $counts['hub_title_conflict']
    . ' | desc update=' . $counts['hub_desc_update'] . ' skip=' . $counts['hub_desc_skip']);
WP_CLI::line('h1:    update=' . $counts['h1_update'] . ' drift_update=' . $counts['h1_drift_update'] . ' skip=' . $counts['h1_skip'] . ' conflict=' . $counts['h1_conflict'] . ' no_content_h1=' . $counts['h1_no_content_h1']);
WP_CLI::line('changed_pages=' . $changed_pages . ' changed_fields=' . $changed_fields);
foreach ($drift as $d) {
    WP_CLI::line('DRIFT ' . $d[0] . ' | ' . $d[1] . ' | db=' . $d[2] . ' | csv_old=' . $d[3]);
}
foreach ($conflicts as $c) {
    WP_CLI::line('CONFLICT ' . $c[0] . ' | ' . $c[1] . ' | db=' . str_replace("\n", ' ', $c[2]) . ' | csv_old=' . $c[3] . ' | csv_new=' . $c[4]);
}
foreach ($drop as $d) {
    WP_CLI::line('SKIP ' . $d[0] . ' | ' . $d[1] . ' | ' . $d[2]);
}
WP_CLI::line('new_strings_new=' . $new_pairs . ' new_strings_total=' . $strings_rows);

if ($dry) {
    WP_CLI::line('dry_run=1 (no database change, no backup file)');
    if (!is_dir($out_dir)) {
        @mkdir($out_dir, 0775, true);
    }
    if (@file_put_contents($strings_path, $strings_body) === false) {
        WP_CLI::error("Cannot write $strings_path");
    }
    WP_CLI::success('34a dry-run finished.');
    return;
}

if ($changed_pages === 0) {
    WP_CLI::line('changed_pages=0 — nothing to write (already applied).');
    WP_CLI::success('34a finished: idempotent no-op.');
    return;
}

// ---------- 备份：写库前把每个页面的旧值全部落盘 ----------
if (!is_dir($backup_dir) && !@mkdir($backup_dir, 0775, true)) {
    WP_CLI::error("Cannot create backup directory $backup_dir; nothing changed.");
}
$stamp = date('Ymd-His');
$backup_path = rtrim($backup_dir, '/\\') . '/34a-before-' . $stamp . '.csv';
$backup_rows = array(array('ts', 'post_id', 'url', 'field', 'old_value', 'new_value'));
foreach ($pages as $path => $p) {
    $id = $p['id'];
    $plan_title = isset($acts[$id]['title']) ? $acts[$id]['title']['after'] : '';
    $plan_meta = isset($acts[$id]['meta']) ? $acts[$id]['meta']['after'] : '';
    $plan_h1 = isset($acts[$id]['h1']) ? pk34a_norm($acts[$id]['h1']['after']) : '';
    $backup_rows[] = array($stamp, $id, $path, '_yoast_wpseo_title', $p['cur_title'], $plan_title);
    $backup_rows[] = array($stamp, $id, $path, '_yoast_wpseo_metadesc', $p['cur_meta'], $plan_meta);
    if (!empty($p['is_hub'])) {
        $backup_rows[] = array($stamp, $id, $path, '_packrivo_document_title', $p['cur_doc_title'], isset($acts[$id]['hub_title']) ? $acts[$id]['hub_title']['after'] : '');
        $backup_rows[] = array($stamp, $id, $path, '_packrivo_description', $p['cur_desc'], isset($acts[$id]['hub_desc']) ? $acts[$id]['hub_desc']['after'] : '');
    }
    if ($p['h1_count'] === 1) {
        $backup_rows[] = array($stamp, $id, $path, 'h1', $p['h1_text'], $plan_h1);
    }
    foreach ($idx[$id] as $rowx) {
        $backup_rows[] = array($stamp, $id, $path, 'idx:' . $rowx['id'] . ':title', (string) $rowx['title'], '');
        $backup_rows[] = array($stamp, $id, $path, 'idx:' . $rowx['id'] . ':description', (string) $rowx['description'], '');
        $backup_rows[] = array($stamp, $id, $path, 'idx:' . $rowx['id'] . ':open_graph_title', (string) $rowx['open_graph_title'], '');
        $backup_rows[] = array($stamp, $id, $path, 'idx:' . $rowx['id'] . ':open_graph_description', (string) $rowx['open_graph_description'], '');
        $backup_rows[] = array($stamp, $id, $path, 'idx:' . $rowx['id'] . ':twitter_title', (string) $rowx['twitter_title'], '');
        $backup_rows[] = array($stamp, $id, $path, 'idx:' . $rowx['id'] . ':twitter_description', (string) $rowx['twitter_description'], '');
    }
}
if (@file_put_contents($backup_path, pk34a_csv_write($backup_rows)) === false) {
    WP_CLI::error("Cannot write backup $backup_path; nothing changed.");
}
WP_CLI::line('backup=' . $backup_path . ' rows=' . (count($backup_rows) - 1));

// ---------- 写库 ----------
$wpdb->query('START TRANSACTION');
$post_pending = array();
$meta_pending = array();
$idx_pending = array();
foreach ($acts as $id => $fields) {
    $meta_targets = array(
        array('slot' => 'title', 'key' => '_yoast_wpseo_title', 'label' => 'Title'),
        array('slot' => 'meta', 'key' => '_yoast_wpseo_metadesc', 'label' => 'Meta description'),
        array('slot' => 'hub_title', 'key' => '_packrivo_document_title', 'label' => 'Hub document title'),
        array('slot' => 'hub_desc', 'key' => '_packrivo_description', 'label' => 'Hub description'),
    );
    foreach ($meta_targets as $mt) {
        if (!isset($fields[$mt['slot']])) {
            continue;
        }
        $before = $fields[$mt['slot']]['before'];
        $exists = $fields[$mt['slot']]['exists'];
        $row = $exists ? $wpdb->get_row($wpdb->prepare("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s LIMIT 1", $id, $mt['key']), ARRAY_A) : null;
        if ($exists && !$row) {
            $wpdb->query('ROLLBACK');
            WP_CLI::error("{$mt['label']} meta row vanished for post $id; transaction rolled back.");
        }
        $meta_pending[] = array('id' => $id, 'key' => $mt['key'], 'meta_id' => $row ? (int) $row['meta_id'] : 0, 'before' => $before, 'after' => $fields[$mt['slot']]['after']);
    }
    if (isset($fields['h1'])) {
        $before = $fields['h1']['before'];
        $after_raw = $fields['h1']['after'];
        if (html_entity_decode($before, ENT_QUOTES | ENT_HTML5, 'UTF-8') !== $before) {
            $after_raw = htmlspecialchars($after_raw, ENT_QUOTES, 'UTF-8');
        }
        $content = $fields['h1']['content'];
        $offset = $fields['h1']['offset'];
        $new_content = substr_replace($content, $after_raw, $offset, strlen($before));
        if ($new_content === $content) {
            $wpdb->query('ROLLBACK');
            WP_CLI::error("H1 replacement produced no change for post $id; transaction rolled back.");
        }
        $post_pending[] = array('id' => $id, 'before' => $content, 'after' => $new_content);
    }
}

foreach ($meta_pending as $mp) {
    if ($mp['meta_id'] > 0) {
        $ok = $wpdb->update($wpdb->postmeta, array('meta_value' => $mp['after']), array('meta_id' => $mp['meta_id'], 'meta_value' => $mp['before']));
    } else {
        $ok = $wpdb->insert($wpdb->postmeta, array('post_id' => $mp['id'], 'meta_key' => $mp['key'], 'meta_value' => $mp['after']));
    }
    if ($ok !== 1) {
        $wpdb->query('ROLLBACK');
        WP_CLI::error("Meta write failed for post {$mp['id']} ({$mp['key']}); transaction rolled back.");
    }
}
foreach ($post_pending as $pp) {
    $ok = $wpdb->update($wpdb->posts, array('post_content' => $pp['after']), array('ID' => $pp['id'], 'post_content' => $pp['before']));
    if ($ok !== 1) {
        $wpdb->query('ROLLBACK');
        WP_CLI::error("post_content write failed for post {$pp['id']} (concurrent change?); transaction rolled back.");
    }
}
foreach ($acts as $id => $fields) {
    if (empty($idx[$id])) {
        continue;
    }
    $data = array();
    $where = array();
    foreach ($idx[$id] as $rowx) {
        $data = array();
        $where = array('id' => (int) $rowx['id']);
        if (isset($fields['title'])) {
            $data['title'] = $fields['title']['after'];
            $data['open_graph_title'] = $fields['title']['after'];
            $data['twitter_title'] = $fields['title']['after'];
        }
        if (isset($fields['meta'])) {
            $data['description'] = $fields['meta']['after'];
            $data['open_graph_description'] = $fields['meta']['after'];
            $data['twitter_description'] = $fields['meta']['after'];
        }
        if (!$data) {
            continue;
        }
        if ($wpdb->update($indexable_table, $data, $where) !== 1) {
            $wpdb->query('ROLLBACK');
            WP_CLI::error("Yoast indexable write failed for post $id; transaction rolled back.");
        }
    }
}
if ($wpdb->query('COMMIT') === false) {
    $wpdb->query('ROLLBACK');
    WP_CLI::error('Commit failed; transaction rolled back.');
}
foreach (array_keys($acts) as $id) {
    clean_post_cache($id);
    wp_cache_delete($id, 'post_meta');
}
wp_cache_flush();

// ---------- 写后自检：重读库值与词表逐条比对 ----------
$after_title_bad = 0;
$after_meta_bad = 0;
$after_h1_bad = 0;
$after_hub_bad = 0;
foreach ($acts as $id => $fields) {
    $t = (string) get_post_meta($id, '_yoast_wpseo_title', true);
    $m = (string) get_post_meta($id, '_yoast_wpseo_metadesc', true);
    $ht = (string) get_post_meta($id, '_packrivo_document_title', true);
    $hd = (string) get_post_meta($id, '_packrivo_description', true);
    if (isset($fields['hub_title']) && $ht !== $fields['hub_title']['after']) {
        $after_hub_bad++;
        WP_CLI::line('AFTER_MISMATCH hub_document_title post=' . $id . ' db=' . $ht);
    }
    if (isset($fields['hub_desc']) && $hd !== $fields['hub_desc']['after']) {
        $after_hub_bad++;
        WP_CLI::line('AFTER_MISMATCH hub_description post=' . $id . ' db=' . $hd);
    }
    if (isset($fields['title']) && $t !== $fields['title']['after']) {
        $after_title_bad++;
        WP_CLI::line('AFTER_MISMATCH title post=' . $id . ' db=' . $t);
    }
    if (isset($fields['meta']) && $m !== $fields['meta']['after']) {
        $after_meta_bad++;
        WP_CLI::line('AFTER_MISMATCH meta post=' . $id . ' db=' . $m);
    }
    if (isset($fields['h1'])) {
        $c = (string) $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d", $id));
        if (!preg_match('~<h1\b[^>]*>([\s\S]*?)</h1>~i', $c, $mm) || pk34a_norm($mm[1]) !== pk34a_norm($fields['h1']['after'])) {
            $after_h1_bad++;
            WP_CLI::line('AFTER_MISMATCH h1 post=' . $id);
        }
    }
}
if (!is_dir($out_dir)) {
    @mkdir($out_dir, 0775, true);
}
if (@file_put_contents($strings_path, $strings_body) === false) {
    WP_CLI::line('WARN cannot write ' . $strings_path);
}
WP_CLI::line('after_check title_bad=' . $after_title_bad . ' meta_bad=' . $after_meta_bad . ' h1_bad=' . $after_h1_bad . ' hub_bad=' . $after_hub_bad);
WP_CLI::line('new_strings=' . $strings_path . ' new=' . $new_pairs . ' total=' . $strings_rows);
WP_CLI::success('34a applied: pages=' . $changed_pages . ' fields=' . $changed_fields);
