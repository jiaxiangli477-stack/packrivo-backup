<?php
/**
 * 29z §1 —— 撤掉 catalog 页、/products/、一篇博客里的 1688 进货价痕迹。
 *
 * 用法：
 *   bash strip-1688.sh --dry-run        # 只报告要改什么，不写库（推荐，字面量就是这个开关）
 *   bash strip-1688.sh --apply          # 真改
 *   直接走 wp-cli 时（wp eval-file 会把它自己的 --flag 当参数错误拦下，所以用位置参数或环境变量）：
 *   wp eval-file strip-1688.php dry-run
 *   STRIP1688_DRY=1 wp eval-file strip-1688.php
 *
 * 幂等：跑第二次应当打印「改动 0 条」。
 * 不写任何数字：删掉价格行以后原位置换成 Get a price → 链接。
 */

$args = isset($args) && is_array($args) ? $args : array();
$dry = in_array('--dry-run', $args, true)
    || in_array('dry-run', $args, true)
    || getenv('STRIP1688_DRY') === '1';
$verbose = in_array('--verbose', $args, true) || getenv('STRIP1688_VERBOSE') === '1';

$quote_url = home_url('/get-a-quote/');
$quote_default = '/get-a-quote/';

$stat = array(
    'mode' => $dry ? 'dry-run' : 'apply',
    'pages_catalog' => 0,
    'pages_touched' => 0,
    'rows_removed' => 0,
    'h2_blocks_removed' => 0,
    'leftover_pages' => 0,
    'leftover_samples' => array(),
    'metadesc_written' => 0,
    'metadesc_unchanged' => 0,
    'products_p_removed' => 0,
    'blog_links_unwrapped' => 0,
    'price_cells' => 0,
    'price_table_headers' => 0,
    'sentences_removed' => 0,
    'removed_sentences' => array(),
    'indexable_rows' => 0,
    'changes' => 0,
    'details' => array(),
);

/** 价格/来源残留词（判断一段内容是否还带 1688 痕迹） */
function s1688_bad_tokens()
{
    return array('1688', '¥', 'yuan', 'RMB', 'CNY', 'ex-works', 'US$0.0');
}

function s1688_scan($text)
{
    $hits = array();
    foreach (s1688_bad_tokens() as $t) {
        if (stripos($text, $t) !== false) {
            $hits[] = $t;
        }
    }
    return $hits;
}

/** 规格表里那一行的表头文本（历史两种写法都要认） */
function s1688_price_th_pattern()
{
    return '(?:Historical\s+1688\s+reference|Lowest\s+listed\s+tier|1688\s+reference|Historical\s+listing\s+price)';
}

/**
 * 1) 删价格行，并在原位置换成 Get a price 链接（表格里留一行，占位不塌）。
 */
function s1688_strip_price_row($content, $quote_href, &$removed)
{
    $pattern = '~<tr\b[^>]*>\s*<th\b[^>]*>\s*' . s1688_price_th_pattern() . '\s*</th>\s*<td\b[^>]*>[\s\S]*?</td>\s*</tr>~i';
    $count = 0;
    $out = preg_replace_callback($pattern, function ($m) use ($quote_href, &$count) {
        $count++;
        return '<tr><th scope="row">Price</th><td><a class="text-link" href="' . esc_url($quote_href) . '">Get a price &rarr;</a></td></tr>';
    }, $content);
    $removed = $count;
    return $out === null ? $content : $out;
}

/**
 * 2) 删 “What this tells you” 那个 h2 和它后面讲价格的那一段。
 *    这一段常常whole 在一个 <div> 里；整块只有 h2+p 时整块删，否则只删 h2+p。
 */
function s1688_strip_tells_block($content, &$removed, &$notes)
{
    $removed = 0;
    $pattern = '~<div\b[^>]*>\s*<h2\b[^>]*>\s*What this tells you\s*</h2>\s*(<p\b[^>]*>[\s\S]*?</p>)\s*</div>~i';
    $out = preg_replace_callback($pattern, function ($m) use (&$removed, &$notes) {
        $para = $m[1];
        $hits = s1688_scan($para);
        $pricey = $hits || stripos($para, 'lowest tier') !== false || stripos($para, 'listed price') !== false;
        if (!$pricey) {
            return $m[0]; // 与价格无关的段落不动
        }
        $removed++;
        $notes[] = array('dropped_paragraph' => trim(wp_strip_all_tags($para)));
        return '';
    }, $content);
    return $out === null ? $content : $out;
}

/**
 * 3) 分类 hub 页的对照表：表头是 “Lowest tier / Historical 1688 reference”，
 *    每一行有一个价格单元格。保留表结构，表头改成 Price，价格单元格换成报价链接。
 */
function s1688_fix_price_tables($content, $quote_href, &$cells, &$headers)
{
    $cells = 0;
    $headers = 0;
    $out = preg_replace_callback('~<table\b[^>]*>[\s\S]*?</table>~i', function ($tm) use ($quote_href, &$cells, &$headers) {
        $table = $tm[0];
        $head_re = '~<th\b[^>]*>\s*(?:Lowest\s+tier|Lowest\s+listed\s+tier|Historical\s+1688\s+reference|1688\s+reference|Price)\s*</th>~i';
        if (!preg_match($head_re, $table, $hm, PREG_OFFSET_CAPTURE)) {
            return $table;
        }
        // 列序号：数一下这条 <th> 前面有几个 <th scope="col">
        $head_pos = $hm[0][1];
        $thead_pos = stripos($table, '<thead');
        $before = $thead_pos !== false && $head_pos > $thead_pos
            ? substr($table, $thead_pos, $head_pos - $thead_pos)
            : substr($table, 0, $head_pos);
        $col = preg_match_all('~<th\b[^>]*scope=["\']col["\'][^>]*>~i', $before);
        $table = preg_replace($head_re, '<th scope="col">Price</th>', $table, 1);
        $headers++;

        $table = preg_replace_callback('~<tr\b[^>]*>[\s\S]*?</tr>~i', function ($rm) use ($col, $quote_href, &$cells) {
            $row = $rm[0];
            if (stripos($row, '<th') !== false && stripos($row, '<td') === false) {
                return $row; // 表头行
            }
            // 按 <td 切细胞，保留每个细胞自己的开始标签
            $parts = preg_split('~(?=<td\b)~i', $row);
            $out = array();
            $idx = -1;
            foreach ($parts as $p) {
                if (stripos($p, '<td') !== 0) {
                    $out[] = $p;
                    continue;
                }
                $idx++;
                if ($idx === $col && s1688_scan($p)) {
                    $cells++;
                    $out[] = '<td><a class="text-link" href="' . esc_url($quote_href) . '">Get a price &rarr;</a></td></tr>';
                    continue;
                }
                $out[] = $p;
            }
            $joined = implode('', $out);
            // 上一步把 </tr> 一起补上了，避免重复
            $joined = preg_replace('~</tr>\s*</tr>~i', '</tr>', $joined);
            return $joined;
        }, $table);
        return $table;
    }, $content);
    return $out === null ? $content : $out;
}

/**
 * 4) 正文里讲价格的那一句：只删句，不删整段，删掉的句子全部进审计清单。
 *    只在纯文本 <p>/<li> 里动手；带内联标签的段落留给人工看。
 */
function s1688_strip_price_sentences($content, &$removed, &$audit, $ctx)
{
    $removed = 0;
    $out = preg_replace_callback('~(<(p|li)\b[^>]*>)([\s\S]*?)(</\2>)~i', function ($m) use (&$removed, &$audit, $ctx) {
        $inner = $m[3];
        if (strpos($inner, '<') !== false) {
            return $m[0]; // 有内联标签，句子切分不可靠，交给人工
        }
        if (!s1688_scan($inner)) {
            return $m[0];
        }
        $sentences = preg_split('~(?<=[.!?])\s+~u', trim($inner));
        $keep = array();
        foreach ($sentences as $s) {
            if ($s !== '' && s1688_scan($s)) {
                $removed++;
                $audit[] = array('id' => $ctx['id'], 'slug' => $ctx['slug'], 'tag' => $m[2], 'sentence' => $s);
                continue;
            }
            $keep[] = $s;
        }
        if (!$keep) {
            return ''; // 整段都是价格话，整段去掉
        }
        return $m[1] . implode(' ', $keep) . $m[4];
    }, $content);
    return $out === null ? $content : $out;
}

/** 从规格表里取结构/材质，用于 meta description 的特点部分 */
function s1688_spec_fields($content)
{
    $want = array('structure' => '', 'material' => '', 'finish' => '');
    foreach ($want as $key => $_) {
        $re = '~<th\b[^>]*>\s*' . $key . '\s*</th>\s*<td\b[^>]*>([\s\S]*?)</td>~i';
        if (preg_match($re, $content, $m)) {
            $want[$key] = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($m[1])));
        }
    }
    return $want;
}

/** 生成 meta description：产品名 + 结构/材质 + 可选区分词 + 固定尾句，≤155 字符 */
function s1688_build_metadesc($title, $spec, $tag = '')
{
    $tail = 'Custom logo printing, MOQ 500 per design. Samples available.';
    $bits = array_filter(array($spec['structure'], $spec['material'], $spec['finish']));
    $feat = implode(', ', $bits);
    $head = trim(preg_replace('/\s+/', ' ', $title));
    $tag = trim($tag);
    $build = function ($h, $f, $g) use ($tail) {
        $s = $h;
        if ($f !== '') {
            $s .= ' — ' . $f . '.';
        } else {
            $s .= '.';
        }
        if ($g !== '') {
            $s .= ' ' . $g . '.';
        }
        return trim($s . ' ' . $tail);
    };
    $desc = $build($head, $feat, $tag);
    // 超长：只压标题尾部（区分词和尾句都保住），先弃特点再弃更多标题词
    while (mb_strlen($desc) > 155 && mb_strlen($head) > 24) {
        $cut = mb_strrpos($head, ' ');
        if ($cut === false || $cut < 12) {
            break;
        }
        $head = rtrim(mb_substr($head, 0, $cut), " ,—-");
        $desc = $build($head, $feat, $tag);
    }
    if (mb_strlen($desc) > 155 && $feat !== '') {
        $feat = '';
        $desc = $build($head, $feat, $tag);
    }
    if (mb_strlen($desc) > 155) {
        $desc = mb_substr($desc, 0, 152) . '...';
    }
    return trim($desc);
}

/* ---------------------------------------------------------------- 收集对象 */

$catalog_ids = array();
$all_pages = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'suppress_filters' => true,
));
foreach ($all_pages as $pid) {
    $slug = (string) get_post_field('post_name', $pid);
    if (strpos($slug, 'catalog-') === 0) {
        $catalog_ids[] = $pid;
    }
}
$stat['pages_catalog'] = count($catalog_ids);
echo "[collect] publish pages = " . count($all_pages) . ", slug 以 catalog- 开头 = " . count($catalog_ids) . "\n";

/* ------------------------------------------------- 1) catalog 页正文 + meta */

$metadesc_seen = array();
$meta_plan = array();

foreach ($catalog_ids as $pid) {
    $post = get_post($pid);
    if (!$post) {
        continue;
    }
    $before = $post->post_content;
    $touched = false;
    $notes = array();

    $rows = 0;
    $content = s1688_strip_price_row($before, $quote_url, $rows);
    if ($rows > 0) {
        $touched = true;
        $stat['rows_removed'] += $rows;
    }

    $blocks = 0;
    $content = s1688_strip_tells_block($content, $blocks, $notes);
    if ($blocks > 0) {
        $touched = true;
        $stat['h2_blocks_removed'] += $blocks;
    }

    $cells = 0;
    $heads = 0;
    $content = s1688_fix_price_tables($content, $quote_url, $cells, $heads);
    if ($cells > 0) {
        $touched = true;
        $stat['price_cells'] += $cells;
        $stat['price_table_headers'] += $heads;
    }

    $sent = 0;
    $content = s1688_strip_price_sentences($content, $sent, $stat['removed_sentences'], array('id' => $pid, 'slug' => $post->post_name));
    if ($sent > 0) {
        $touched = true;
        $stat['sentences_removed'] += $sent;
    }

    $left = s1688_scan($content);
    if ($left) {
        $stat['leftover_pages']++;
        if (count($stat['leftover_samples']) < 12) {
            $pos = stripos($content, $left[0]);
            $stat['leftover_samples'][] = array(
                'id' => $pid,
                'slug' => $post->post_name,
                'tokens' => $left,
                'snippet' => mb_substr(wp_strip_all_tags(substr($content, max(0, $pos - 90), 240)), 0, 200),
            );
        }
    }

    if ($content !== $before) {
        $stat['changes']++;
        $stat['pages_touched']++;
        if (!$dry) {
            wp_update_post(array('ID' => $pid, 'post_content' => $content));
        }
        if ($verbose || count($stat['details']) < 6) {
            $stat['details'][] = array(
                'id' => $pid,
                'slug' => $post->post_name,
                'rows_removed' => $rows,
                'blocks_removed' => $blocks,
                'len_before' => strlen($before),
                'len_after' => strlen($content),
                'notes' => $notes,
            );
        }
    }

    // meta description 计划（真正落库在后段统一做）
    $spec = s1688_spec_fields($content);
    $desc = s1688_build_metadesc($post->post_title, $spec);
    if (isset($metadesc_seen[$desc])) {
        // 撞车：先加标题破折号后的特征词，再加 slug 里的结构词，最后用 SKU 码兜底
        $cands = array();
        if (preg_match('/[—–]\s*(.+)$/u', $post->post_title, $tm)) {
            $cands[] = trim(preg_replace('/\s+/', ' ', $tm[1]));
        }
        $slug_no_sku = trim(str_replace('-', ' ', preg_replace('/-(o1cn[a-z0-9]+)$/i', '', $post->post_name)), ' -');
        $slug_no_sku = trim(preg_replace('/^catalog\s+/i', '', $slug_no_sku));
        if ($slug_no_sku !== '') {
            $cands[] = ucfirst($slug_no_sku);
        }
        $sku = '';
        if (preg_match('/-(o1cn[a-z0-9]+)$/i', $post->post_name, $sm)) {
            $sku = strtoupper($sm[1]);
        }
        foreach ($cands as $c) {
            $try = s1688_build_metadesc($post->post_title, $spec, $c);
            if (!isset($metadesc_seen[$try])) {
                $desc = $try;
                break;
            }
        }
        if (isset($metadesc_seen[$desc]) && $sku !== '') {
            $desc = s1688_build_metadesc($post->post_title, $spec, 'Ref ' . $sku);
        }
        $n = 2;
        while (isset($metadesc_seen[$desc]) && $n <= 9) {
            $desc = s1688_build_metadesc($post->post_title, $spec, 'Ref ' . $sku . ' ' . $n);
            $n++;
        }
        if (isset($metadesc_seen[$desc])) {
            $stat['metadesc_collisions'][] = array('id' => $pid, 'title' => $post->post_title);
        }
    }
    $metadesc_seen[$desc] = $pid;
    $meta_plan[$pid] = $desc;
}

/* ------------------------------------------------------ 2) 落 meta（Yoast） */

global $wpdb;
$wpdb->hide_errors();
$indexable_table = $wpdb->prefix . 'yoast_indexable';
$has_indexable = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $indexable_table)) === $indexable_table);
// Yoast 各版本列不一样（有的没有 og_description），按实际列来改
$idx_cols = $has_indexable ? $wpdb->get_col("DESCRIBE `{$indexable_table}`", 0) : array();
$idx_text_cols = array_values(array_intersect(array('description', 'open_graph_description', 'og_description', 'twitter_description'), $idx_cols));

foreach ($meta_plan as $pid => $desc) {
    $old_meta = (string) get_post_meta($pid, '_yoast_wpseo_metadesc', true);
    $old_og = (string) get_post_meta($pid, '_yoast_wpseo_opengraph-description', true);
    if ($old_meta !== $desc) {
        $stat['metadesc_written']++;
        if (!$dry) {
            update_post_meta($pid, '_yoast_wpseo_metadesc', $desc);
        }
    } else {
        $stat['metadesc_unchanged']++;
    }
    if ($old_og !== $desc) {
        if (!$dry) {
            update_post_meta($pid, '_yoast_wpseo_opengraph-description', $desc);
        }
    }
    if ($has_indexable && $idx_text_cols) {
        $fields = implode(', ', array_merge(array('id'), $idx_text_cols));
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT {$fields} FROM `{$indexable_table}` WHERE object_type='post' AND object_id=%d LIMIT 1",
            $pid
        ), ARRAY_A);
        if ($row) {
            $upd = array();
            foreach ($idx_text_cols as $col) {
                if (!isset($row[$col]) || (string) $row[$col] !== $desc) {
                    $upd[$col] = $desc;
                }
            }
            if ($upd) {
                $stat['indexable_rows']++;
                if (!$dry) {
                    $wpdb->update($indexable_table, $upd, array('id' => (int) $row['id']));
                }
            }
        }
    }
}
if ($has_indexable) {
    echo "[indexable] 列 = " . implode(',', $idx_cols) . "；会改的文本列 = " . (implode(',', $idx_text_cols) ?: '（无）') . "\n";
}
$wpdb->show_errors();

/* ------------------------------------------------------- 3) /products/ 正文 */

$products_page = get_page_by_path('products');
if ($products_page) {
    $before = $products_page->post_content;
    $pattern = '~<p\b[^>]*>(?:(?!</p>).)*?Listed\s+at\s*¥(?:(?!</p>).)*?</p>~i';
    $cnt = 0;
    $after = preg_replace_callback($pattern, function ($m) use (&$cnt) {
        $cnt++;
        return '';
    }, $before);
    if ($after === null) {
        $after = $before;
    }
    $stat['products_p_removed'] = $cnt;
    if ($cnt > 0 && $after !== $before) {
        $stat['changes']++;
        if (!$dry) {
            wp_update_post(array('ID' => $products_page->ID, 'post_content' => $after));
        }
        echo "[products] 删掉 {$cnt} 个带 1688 价格的 <p>（页面由 template-products-v4 渲染，正文不上屏）\n";
    } else {
        echo "[products] 没有需要删的 <p>\n";
    }
    $left = s1688_scan($after);
    if ($left) {
        echo "[products] 注意：正文仍有 " . implode('/', $left) . "，需人工看\n";
    }
} else {
    echo "[products] 找不到 slug=products 的页面\n";
}

/* ------------------------------------------- 4) 博客里的 1688 链接去链接化 */

$blog = get_page_by_path('jewelry-box-insert-fit-measurements', OBJECT, 'post');
if (!$blog) {
    $q = get_posts(array('post_type' => 'post', 'name' => 'jewelry-box-insert-fit-measurements', 'posts_per_page' => 1, 'post_status' => 'any'));
    $blog = $q ? $q[0] : null;
}
if ($blog) {
    $before = $blog->post_content;
    $cnt = 0;
    $after = preg_replace_callback(
        '~<a\b[^>]*href="[^"]*detail\.1688\.com[^"]*"[^>]*>([\s\S]*?)</a>~i',
        function ($m) use (&$cnt) {
            $cnt++;
            return 'a supplier size sheet (kept on file)';
        },
        $before
    );
    if ($after === null) {
        $after = $before;
    }
    $stat['blog_links_unwrapped'] = $cnt;
    if ($cnt > 0 && $after !== $before) {
        $stat['changes']++;
        if (!$dry) {
            wp_update_post(array('ID' => $blog->ID, 'post_content' => $after));
        }
        echo "[blog] 去掉 {$cnt} 个 detail.1688.com 链接，文字改成 a supplier size sheet (kept on file)\n";
    } else {
        echo "[blog] 没有 detail.1688.com 的 <a>\n";
    }
    foreach (array('1688', 'offer/') as $t) {
        if (stripos($after, $t) !== false) {
            echo "[blog] 注意：正文仍有 {$t}，需人工看\n";
        }
    }
} else {
    echo "[blog] 找不到 jewelry-box-insert-fit-measurements\n";
}

/* --------------------------------------------------------------- 5) 收尾 */

if (!$dry && $stat['changes'] > 0) {
    wp_cache_flush();
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
}

/* ------------------------------------------------------------- 输出汇总 */

$metas = array_values($meta_plan);
$lens = array_map('mb_strlen', $metas);
sort($lens);
$uniq = count(array_unique($metas));
$over = 0;
foreach ($lens as $l) {
    if ($l > 155) {
        $over++;
    }
}

echo "\n===== strip-1688 汇总（" . ($dry ? 'dry-run，未写库' : '已写库') . "）=====\n";
printf("catalog 页              : %d\n", $stat['pages_catalog']);
printf("正文被改的页            : %d\n", $stat['pages_touched']);
printf("删掉的价格行            : %d\n", $stat['rows_removed']);
printf("删掉的 What this tells you 块 : %d\n", $stat['h2_blocks_removed']);
printf("对照表价格单元格改链接    : %d（表头 %d 处）\n", $stat['price_cells'], $stat['price_table_headers']);
printf("正文里删掉的价格句子      : %d\n", $stat['sentences_removed']);
printf("正文仍有 1688 痕迹的页  : %d\n", $stat['leftover_pages']);
printf("/products/ 删掉的 <p>   : %d\n", $stat['products_p_removed']);
printf("博客去链接的 <a>        : %d\n", $stat['blog_links_unwrapped']);
printf("meta 要写的条数         : %d（原本已一致 %d）\n", $stat['metadesc_written'], $stat['metadesc_unchanged']);
printf("indexable 要更新的行    : %d（表%s）\n", $stat['indexable_rows'], $has_indexable ? '存在' : '不存在');
printf("meta 长度 min/max       : %d / %d（>155 的 %d 条）\n", $lens ? $lens[0] : 0, $lens ? end($lens) : 0, $over);
printf("meta 去重后条数         : %d / %d\n", $uniq, count($metas));
printf("改动合计                : %d\n", $stat['changes']);

if ($stat['leftover_samples']) {
    echo "\n--- 残留样例（前 12 条）---\n";
    foreach ($stat['leftover_samples'] as $s) {
        echo "  #{$s['id']} {$s['slug']} [" . implode(',', $s['tokens']) . "] ...{$s['snippet']}\n";
    }
}
if ($stat['details']) {
    echo "\n--- 改动样例 ---\n";
    foreach ($stat['details'] as $d) {
        echo "  #{$d['id']} {$d['slug']} 行-{$d['rows_removed']} 块-{$d['blocks_removed']} 字节 {$d['len_before']}→{$d['len_after']}\n";
        foreach ($d['notes'] as $n) {
            if (!empty($n['dropped_paragraph'])) {
                echo "      删掉的段落: " . mb_substr($n['dropped_paragraph'], 0, 160) . "\n";
            }
        }
    }
}

$audit_path = getenv('STRIP1688_AUDIT');
if (!$audit_path) {
    $audit_path = '/tmp/strip-1688-audit.json';
}
file_put_contents($audit_path, wp_json_encode(array(
    'mode' => $stat['mode'],
    'sentences' => array_values($stat['removed_sentences']),
    'details' => $stat['details'],
    'leftover_samples' => $stat['leftover_samples'],
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "\n[audit] 删掉的句子（" . count($stat['removed_sentences']) . " 句）与改动明细 -> {$audit_path}\n";

echo "\n[report] " . wp_json_encode(array(
    'mode' => $stat['mode'],
    'pages_catalog' => $stat['pages_catalog'],
    'pages_touched' => $stat['pages_touched'],
    'rows_removed' => $stat['rows_removed'],
    'h2_blocks_removed' => $stat['h2_blocks_removed'],
    'leftover_pages' => $stat['leftover_pages'],
    'metadesc_written' => $stat['metadesc_written'],
    'metadesc_total' => count($metas),
    'metadesc_unique' => $uniq,
    'metadesc_maxlen' => $lens ? end($lens) : 0,
    'metadesc_over155' => $over,
    'indexable_rows' => $stat['indexable_rows'],
    'products_p_removed' => $stat['products_p_removed'],
    'blog_links_unwrapped' => $stat['blog_links_unwrapped'],
    'changes' => $stat['changes'],
)) . "\n";
