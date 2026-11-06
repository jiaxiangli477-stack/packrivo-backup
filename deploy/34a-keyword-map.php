<?php
/**
 * 34a — 把定稿关键词表写进现有英文页面，只动三处：Yoast title、Yoast meta description、页面上的 <h1>。
 *
 * 调用（WP-CLI 的 eval-file 会拦截未知长选项，推荐注册包装命令）：
 *   wp --require=deploy/34a-cli.php pk34a migrate /path/map.csv --force-url=/collapsible-magnetic-gift-box/ --dry-run
 *   wp eval-file deploy/34a-keyword-map.php /path/map.csv dry-run force-url=/collapsible-magnetic-gift-box/
 * 环境变量：
 *   PK34A_BACKUP_DIR  备份目录，默认 __DIR__/backup
 *   PK34A_OUT_DIR     new-strings 输出目录，默认 仓库根/dsh-out
 *   PK34A_APPROVED_STRINGS  经翻译闸门检查的原始英文清单路径
 *   PK34A_MANIFEST_SHA256   翻译闸门前后核对一致的清单 SHA-256
 *
 * 字段来源（已实测）：
 *   title  = postmeta._yoast_wpseo_title + wp_yoast_indexable.title
 *   meta   = postmeta._yoast_wpseo_metadesc + wp_yoast_indexable.description
 *   H1     = post_content 里唯一的 <h1 ...>…</h1> 的内层文本（主题 page.php 不再输出 H1，原生页的快照自带 H1）
 *
 * 写入规则：
 *   现库值 == 新值              → skip（幂等，重复跑不会重复写）
 *   现库值 == 词表现值          → update
 *   仅 MOQ 250/500 差异后相等   → update（32a/32a-2 已把标题里的 500 改成 250，词表现值是旧快照）
 *   其它                        → conflict，只报告不写
 * 8 个排名页：新 title 去掉 MOQ 250/500 后必须与现库 title 完全一致，任何一条不符则整轮中止、一个字节都不写。
 */

if (!defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "This script must run through WP-CLI eval-file.\n");
    exit(1);
}
require_once __DIR__ . '/34a-backup-lib.php';
require_once __DIR__ . '/34a-plan-lib.php';

global $wpdb;

$argv = (isset($args) && is_array($args)) ? $args : array();
$dry = false;
$map_path = '';
$force_url = '';
foreach ($argv as $a) {
    $a = (string) $a;
    if ($a === 'dry-run' || $a === '--dry-run') {
        $dry = true;
    } elseif (strpos($a, '--map=') === 0) {
        $map_path = substr($a, 6);
    } elseif (strpos($a, '--force-url=') === 0 || strpos($a, 'force-url=') === 0) {
        $force_url = substr($a, strpos($a, '=') + 1);
        if ($force_url !== '/collapsible-magnetic-gift-box/') {
            WP_CLI::error('Only /collapsible-magnetic-gift-box/ may be forced; nothing changed.');
        }
    } elseif (strpos($a, '--') !== 0) {
        $map_path = $a;
    } else {
        WP_CLI::error("Unknown argument $a; nothing changed.");
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
    $s = preg_replace('/\s{2,}/u', ' ', $s);
    return strtolower(trim($s, " \t,;:-|–—"));
}
function pk34a_rank_matches($new, $yoast, $hub, $is_hub) {
    if (pk34a_base($new) !== pk34a_base($yoast)) return false;
    return !$is_hub || $hub === '' || pk34a_base($new) === pk34a_base($hub);
}
function pk34a_index_plan($page, $fields, $indexables) {
    $title=isset($fields['title']) ? $fields['title']['after']
        : (pk34a_norm($page['cur_title']) === pk34a_norm($page['new_title']) ? $page['cur_title'] : null);
    $desc=isset($fields['meta']) ? $fields['meta']['after']
        : (pk34a_norm($page['cur_meta']) === pk34a_norm($page['new_meta']) ? $page['cur_meta'] : null);
    $result=array();
    foreach ($indexables as $row) foreach (array('title'=>$title,'description'=>$desc) as $column=>$desired) {
        if ($desired === null || $row[$column] === $desired) continue;
        $result[]=array('row'=>(int)$row['id'],'column'=>$column,'old'=>$row[$column],'new'=>$desired);
    }
    return $result;
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
    $stream = fopen('php://temp', 'w+');
    if (!$stream) WP_CLI::error('Cannot open CSV buffer; nothing changed.');
    foreach ($rows as $row) {
        if (fputcsv($stream, $row, ',', '"', '') === false) WP_CLI::error('Cannot encode CSV; nothing changed.');
    }
    rewind($stream);
    $data = stream_get_contents($stream);
    fclose($stream);
    return $data;
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
$moq_adjusted = array();
while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
    if ($r === array(null) || (count($r) === 1 && trim((string) $r[0]) === '')) {
        continue;
    }
    $url = trim((string) $r[$col['url']]);
    if ($url === '') {
        continue;
    }
    $new_title = isset($r[$col['新 title']]) ? trim((string) $r[$col['新 title']]) : '';
    $new_meta = isset($r[$col['新 meta']]) ? trim((string) $r[$col['新 meta']]) : '';
    foreach (array('new_title' => $new_title, 'new_meta' => $new_meta) as $field => $value) {
        $converted = preg_replace('/(\bMOQ\s*:?\s*)500\b/iu', '${1}250', $value);
        if ($converted !== $value) $moq_adjusted[] = array(pk34a_path($url), $field, $value, $converted);
        if ($field === 'new_title') $new_title = $converted;
        else $new_meta = $converted;
    }
    $rows[] = array(
        'url' => $url,
        'path' => pk34a_path($url),
        'type' => isset($col['页面类型']) && isset($r[$col['页面类型']]) ? trim((string) $r[$col['页面类型']]) : '',
        'old_title' => isset($r[$col['现 title']]) ? (string) $r[$col['现 title']] : '',
        'old_h1' => isset($r[$col['现 H1']]) ? (string) $r[$col['现 H1']] : '',
        'new_title' => $new_title,
        'new_meta' => $new_meta,
        'new_h1' => isset($r[$col['新 H1']]) ? trim((string) $r[$col['新 H1']]) : '',
    );
}
fclose($fh);
if (count($rows) !== 141) WP_CLI::error('Keyword map must contain exactly 141 rows; nothing changed.');
$unique_paths = array();
foreach ($rows as $row) {
    if (isset($unique_paths[$row['path']])) WP_CLI::error('Duplicate map path '.$row['path'].'; nothing changed.');
    $unique_paths[$row['path']] = true;
}
WP_CLI::line('moq_500_normalized=' . count($moq_adjusted));
foreach ($moq_adjusted as $change) WP_CLI::line('MOQ_ADJUST ' . implode(' | ', $change));

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
    if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish') {
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
if ($missing) WP_CLI::error('Unresolved map pages: '.implode(' ', $missing).'; nothing changed.');
if ($force_url !== '' && !isset($pages[$force_url])) WP_CLI::error('Forced path not resolved; nothing changed.');

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
    if (!pk34a_rank_matches($p['new_title'], $p['cur_title'], $p['cur_doc_title'], !empty($p['is_hub']))) {
        $gate_fail[] = sprintf('%s new=[%s] yoast=[%s] hub=[%s]', $rp, pk34a_base($p['new_title']), pk34a_base($p['cur_title']), pk34a_base($p['cur_doc_title']));
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
    $title_hard_conflict = false;
    $hub_title_hard_conflict = false;

    // The quote theme owns all three rendered fields at priority 1200.
    if ($path === '/get-a-quote/') {
        $drop[] = array($path, 'title/meta/h1', 'theme-owned; no database fields changed');
        continue;
    }

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
    } elseif ($force_url === '/collapsible-magnetic-gift-box/' && $path === $force_url) {
        $counts['title_update']++;
        $acts[$id]['title'] = array('before' => $p['cur_title'], 'after' => $p['new_title'], 'exists' => $p['cur_title_exists']);
        $drift[] = array($path, 'title_forced', $p['cur_title'], $p['old_title']);
    } else {
        $counts['title_conflict']++;
        $title_hard_conflict = true;
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
    // 即使主题可回退 Yoast，缺失/空 hub title 仍按冲突停止，避免无授权创建投影。
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
            $hub_title_hard_conflict = true;
            $conflicts[] = array($path, 'hub_document_title', $p['cur_doc_title'], $p['old_title'], $p['new_title']);
        }
        if (pk34a_norm($p['cur_desc']) === pk34a_norm($p['new_meta'])) {
            $counts['hub_desc_skip']++;
        } else {
            $counts['hub_desc_update']++;
            $acts[$id]['hub_desc'] = array('before' => $p['cur_desc'], 'after' => $p['new_meta'], 'exists' => $p['cur_desc_exists']);
        }
    }
    if (!empty($p['is_hub']) && ($title_hard_conflict || $hub_title_hard_conflict)) {
        WP_CLI::error("Hub title conflict at $path; refusing a partial title/indexable change.");
    }

    // H1
    $new_h1 = pk34a_norm($p['new_h1']);
    if (in_array($path, array('/', '/products/', '/samples/'), true)) {
        $counts['h1_skip']++;
        $drop[] = array($path, 'h1', 'retained by explicit review decision');
    } elseif ($p['h1_count'] === 0) {
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
        "SELECT id,title,description FROM {$indexable_table} WHERE object_type='post' AND object_id=%d",
        $p['id']
    ), ARRAY_A);
    if (!is_array($rowsx)) WP_CLI::error('Indexable query failed for '.$path.'; nothing changed.');
    if (count($rowsx) !== 1) WP_CLI::error('Expected one indexable for '.$path.'; nothing changed.');
    $idx[$p['id']] = $rowsx;
}

// Plan stale indexables independently, even if post meta is already current.
$index_acts = array();
foreach ($pages as $path => $p) {
    if ($path === '/get-a-quote/') continue;
    $id=$p['id'];$plan=pk34a_index_plan($p,$acts[$id] ?? array(),$idx[$id]);
    if ($plan) $index_acts[$id]=$plan;
}

// Bind every newly written source string and the quote theme strings to the
// same reviewed manifest used by the translation gate. Index-only repairs do
// not introduce a new source string; they restore existing meta to its cache.
$approved_path=getenv('PK34A_APPROVED_STRINGS');
if (!$approved_path) $approved_path=dirname(__DIR__).'/dsh-out/34a-new-strings.csv';
if (!is_readable($approved_path)) WP_CLI::error('Approved strings manifest is not readable; nothing changed.');
$approved_raw=file_get_contents($approved_path);$approved_sha=(string)getenv('PK34A_MANIFEST_SHA256');
if (!pk34a_manifest_hash_matches($approved_raw,$approved_sha)) WP_CLI::error('Gated manifest SHA-256 missing or changed; nothing changed.');
$mf=fopen('php://temp','w+');fwrite($mf,$approved_raw);rewind($mf);$mh=fgetcsv($mf,0,',','"','');
if (is_array($mh)) $mh[0]=preg_replace('/^\xEF\xBB\xBF/','',(string)$mh[0]);
if ($mh!==array('source','url','old_en','new_en')) WP_CLI::error('Invalid approved strings manifest header; nothing changed.');
$manifest=array();
while (($mr=fgetcsv($mf,0,',','"',''))!==false) {
    if (count($mr)!==4 || $mr[0]==='' || $mr[3]==='') WP_CLI::error('Invalid approved strings manifest row; nothing changed.');
    $manifest[]=array_combine($mh,$mr);
}
fclose($mf);
if (!$manifest || !function_exists('pkv4_quote_strings')) WP_CLI::error('Approved manifest or quote theme is unavailable; nothing changed.');
try { $manifest_gaps=pk34a_manifest_gaps($acts,$manifest,pkv4_quote_strings()); }
catch (Throwable $e) { WP_CLI::error('Cannot validate approved strings: '.$e->getMessage()); }
if ($manifest_gaps) {
    foreach ($manifest_gaps as $gap) WP_CLI::line('MANIFEST_MISSING post='.$gap['id'].' field='.$gap['field'].' value='.$gap['value']);
    WP_CLI::error('Planned English strings are not covered by the gated manifest; nothing changed.');
}
WP_CLI::line('manifest_coverage=pass rows='.count($manifest).' sha256='.$approved_sha);

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
$affected_ids = array_values(array_unique(array_merge(array_keys($acts),array_keys($index_acts))));
$changed_pages = count($affected_ids);
$changed_fields = 0;
foreach ($acts as $f) {
    $changed_fields += count($f);
}
$planned_primary = $changed_fields;
foreach ($index_acts as $items) $changed_fields += count($items);
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
WP_CLI::line('changed_pages=' . $changed_pages . ' changed_fields=' . $changed_fields . ' primary=' . $planned_primary . ' indexable=' . ($changed_fields-$planned_primary));
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
WP_CLI::line('force_url=' . ($force_url === '' ? '(none)' : $force_url));

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
    WP_CLI::line('changed=0');
    WP_CLI::line('changed_pages=0 — nothing to write (already applied).');
    WP_CLI::success('34a finished: idempotent no-op.');
    return;
}

// ---------- Exact, typed backup of only the fields that will be changed ----------
$paths_by_id = array();
foreach ($pages as $path => $p) {
    if (isset($paths_by_id[$p['id']])) WP_CLI::error('Duplicate page ID in map; nothing changed.');
    $paths_by_id[$p['id']] = $path;
}
$records = array();
$meta_keys = array('title'=>'_yoast_wpseo_title', 'meta'=>'_yoast_wpseo_metadesc', 'hub_title'=>'_packrivo_document_title', 'hub_desc'=>'_packrivo_description');
foreach ($acts as $id => $fields) {
    foreach ($meta_keys as $slot => $key) {
        if (!isset($fields[$slot])) continue;
        $a = $fields[$slot];
        $records[] = array('id'=>(int)$id, 'url'=>$paths_by_id[$id], 'field'=>$key, 'old'=>$a['before'], 'new'=>$a['after'], 'old_exists'=>(bool)$a['exists'], 'old_null'=>false);
    }
    if (isset($fields['h1'])) {
        $a = $fields['h1'];
        $records[] = array('id'=>(int)$id, 'url'=>$paths_by_id[$id], 'field'=>'h1', 'old'=>$a['before'], 'new'=>esc_html($a['after']), 'old_exists'=>true, 'old_null'=>false);
    }
}
foreach ($index_acts as $id => $items) foreach ($items as $item) {
    $records[] = array('id'=>(int)$id, 'url'=>$paths_by_id[$id], 'field'=>'idx:'.$item['row'].':'.$item['column'],
        'old'=>$item['old'] === null ? '' : $item['old'], 'new'=>$item['new'],
        'old_exists'=>true, 'old_null'=>$item['old'] === null);
}
if (!$records) WP_CLI::error('No write records despite planned changes.');
if (!is_dir($backup_dir) && !@mkdir($backup_dir, 0775, true)) WP_CLI::error("Cannot create backup directory $backup_dir; nothing changed.");
$stamp = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(5));
$backup_path = rtrim($backup_dir, '/\\') . '/34a-before-' . $stamp . '.csv';
try { $payload=pk34a_backup_encode($records,$stamp); }
catch (Throwable $e) { WP_CLI::error('Cannot encode complete backup; nothing changed: '.$e->getMessage()); }
$fh = @fopen($backup_path, 'x');
if (!$fh || fwrite($fh, $payload) !== strlen($payload) || !fclose($fh)) WP_CLI::error("Cannot write complete exclusive backup $backup_path; nothing changed.");
WP_CLI::line('backup=' . $backup_path . ' rows=' . count($records) . ' sha256=' . hash_file('sha256', $backup_path));
foreach (array($wpdb->posts,$wpdb->postmeta,$indexable_table) as $table) {
    $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $table));
    if (strtoupper((string)$engine) !== 'INNODB') WP_CLI::error("Non-transactional table $table; nothing changed.");
}
if ($wpdb->query('START TRANSACTION') === false) WP_CLI::error('Cannot start transaction; nothing changed.');
$locked_ids = $affected_ids;
foreach ($rank_paths as $path) $locked_ids[] = $pages[$path]['id'];
$locked_ids = array_values(array_unique($locked_ids));sort($locked_ids, SORT_NUMERIC);
foreach ($locked_ids as $id) {
    $post = $wpdb->get_row($wpdb->prepare("SELECT post_name,post_type,post_status FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$id), ARRAY_A);
    $path = $paths_by_id[$id];
    if (!$post || $post['post_type'] !== 'page' || $post['post_status'] !== 'publish' || $post['post_name'] !== $pages[$path]['slug']) {
        $wpdb->query('ROLLBACK'); WP_CLI::error("Concurrent page identity change $id; transaction rolled back.");
    }
}
// Revalidate the ranking gate under row locks, including hub overrides even
// when a protected title itself does not need to be updated.
foreach ($rank_paths as $path) {
    $p=$pages[$path];
    $checks=array(array('_yoast_wpseo_title',$p['cur_title_exists'],$p['cur_title']));
    if ($p['is_hub']) $checks[]=array('_packrivo_document_title',$p['cur_doc_title_exists'],$p['cur_doc_title']);
    foreach ($checks as $check) {
        list($key,$exists,$value)=$check;
        $actual=$wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s FOR UPDATE",$p['id'],$key),ARRAY_A);
        if (!is_array($actual) || count($actual)!==($exists?1:0) || ($exists && $actual[0]['meta_value']!==$value)) {
            $wpdb->query('ROLLBACK');WP_CLI::error("Rank title changed after preflight $path $key; transaction rolled back.");
        }
    }
}
// An index-only repair depends on unchanged source meta too. Lock it even
// when no primary meta write is planned, or the cache could lag a race.
foreach ($index_acts as $id=>$items) foreach ($items as $item) {
    $slot=$item['column']==='title' ? 'title' : 'meta';
    if (isset($acts[$id][$slot])) continue; // The planned meta row is locked below.
    $p=$pages[$paths_by_id[$id]];
    $key=$slot==='title' ? '_yoast_wpseo_title' : '_yoast_wpseo_metadesc';
    $exists=$slot==='title' ? $p['cur_title_exists'] : $p['cur_meta_exists'];
    $value=$slot==='title' ? $p['cur_title'] : $p['cur_meta'];
    $actual=$wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s FOR UPDATE",$id,$key),ARRAY_A);
    if (!is_array($actual) || count($actual)!==($exists?1:0) || ($exists && $actual[0]['meta_value']!==$value)) {
        $wpdb->query('ROLLBACK');WP_CLI::error("Index source changed after preflight {$p['path']} $key; transaction rolled back.");
    }
}
$locked = array();
foreach ($records as $i => $r) {
    $field = $r['field']; $id = $r['id'];
    if (in_array($field, array_values($meta_keys), true)) {
        $matches = $wpdb->get_results($wpdb->prepare("SELECT meta_id,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s FOR UPDATE",$id,$field), ARRAY_A);
        if (!is_array($matches) || count($matches) !== ($r['old_exists'] ? 1 : 0) || ($r['old_exists'] && $matches[0]['meta_value'] !== $r['old'])) {
            $wpdb->query('ROLLBACK'); WP_CLI::error("Concurrent or duplicate meta $id $field; transaction rolled back.");
        }
        $locked[$i] = $r['old_exists'] ? (int)$matches[0]['meta_id'] : 0;
    } elseif ($field === 'h1') {
        $content = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$id));
        if (!is_string($content) || $content !== $acts[$id]['h1']['content']) {
            $wpdb->query('ROLLBACK'); WP_CLI::error("Concurrent post content $id; transaction rolled back.");
        }
        $locked[$i] = $content;
    } elseif (preg_match('/^idx:(\d+):(title|description)$/', $field, $m)) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT object_id,object_type,{$m[2]} AS val FROM {$indexable_table} WHERE id=%d FOR UPDATE",(int)$m[1]), ARRAY_A);
        $expected = $r['old_null'] ? null : $r['old'];
        if (!$row || (int)$row['object_id'] !== $id || $row['object_type'] !== 'post' || $row['val'] !== $expected) {
            $wpdb->query('ROLLBACK'); WP_CLI::error("Concurrent indexable $id $field; transaction rolled back.");
        }
        $locked[$i] = (int)$m[1];
    } else {
        $wpdb->query('ROLLBACK'); WP_CLI::error("Unexpected field $field; transaction rolled back.");
    }
}
$write_counts = array('meta'=>0,'h1'=>0,'indexable'=>0);
foreach ($records as $i => $r) {
    $field=$r['field']; $id=$r['id'];
    if (in_array($field, array_values($meta_keys), true)) {
        $ok = $r['old_exists'] ? $wpdb->update($wpdb->postmeta,array('meta_value'=>$r['new']),array('meta_id'=>$locked[$i]))
            : $wpdb->insert($wpdb->postmeta,array('post_id'=>$id,'meta_key'=>$field,'meta_value'=>$r['new']));
        $write_counts['meta']++;
    } elseif ($field === 'h1') {
        $a=$acts[$id]['h1']; $new_content=substr_replace($locked[$i],$r['new'],$a['offset'],strlen($r['old']));
        $ok=$wpdb->update($wpdb->posts,array('post_content'=>$new_content),array('ID'=>$id));
        $write_counts['h1']++;
    } else {
        preg_match('/^idx:(\d+):(title|description)$/',$field,$m);
        // Yoast's updated_at has ON UPDATE CURRENT_TIMESTAMP. Assigning it
        // to itself prevents an unrelated timestamp change.
        $ok=$wpdb->query($wpdb->prepare("UPDATE {$indexable_table} SET {$m[2]}=%s, updated_at=updated_at WHERE id=%d",$r['new'],$locked[$i]));
        $write_counts['indexable']++;
    }
    if ($ok !== 1) { $wpdb->query('ROLLBACK'); WP_CLI::error("Write failed $id $field; transaction rolled back."); }
}
// Verify within the transaction so a failed postcondition cannot commit.
foreach ($records as $r) {
    $field=$r['field'];$id=$r['id'];
    if (in_array($field,array_values($meta_keys),true)) $value=$wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",$id,$field));
    elseif ($field === 'h1') {
        $body=$wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d",$id));
        $value=preg_match('~<h1\b[^>]*>([\s\S]*?)</h1>~i',(string)$body,$m) ? $m[1] : null;
    } else {
        preg_match('/^idx:(\d+):(title|description)$/',$field,$m);
        $value=$wpdb->get_var($wpdb->prepare("SELECT {$m[2]} FROM {$indexable_table} WHERE id=%d",(int)$m[1]));
    }
    if ($value !== $r['new']) { $wpdb->query('ROLLBACK'); WP_CLI::error("Postcondition failed $id $field; transaction rolled back."); }
}
if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); WP_CLI::error('Commit failed; transaction rolled back.'); }
foreach ($affected_ids as $id) { clean_post_cache($id); wp_cache_delete($id, 'post_meta'); }
if (!is_dir($out_dir)) @mkdir($out_dir, 0775, true);
if (@file_put_contents($strings_path, $strings_body) === false) WP_CLI::warning('Cannot write '.$strings_path);
WP_CLI::line('changed='.count($records).' meta='.$write_counts['meta'].' h1='.$write_counts['h1'].' indexable='.$write_counts['indexable']);
WP_CLI::line('after_check title_bad=0 meta_bad=0 h1_bad=0 hub_bad=0 indexable_bad=0');
WP_CLI::line('new_strings='.$strings_path.' new='.$new_pairs.' total='.$strings_rows);
WP_CLI::success('34a applied: pages='.$changed_pages.' fields='.count($records));
