<?php
/**
 * 29y 第 1 项：从报价引擎生成 llms.txt 的价格段。
 *
 * 跑法（演练/生产同构）：
 *   docker exec -e LLMS_BASE=/tmp/llms.base.txt -e LLMS_OUT=/tmp/llms.txt \
 *     -e LLMS_JSON=/tmp/llms-prices.json php sh -c \
 *     "php -d disable_functions= /usr/local/bin/wp --path=$WPC --allow-root eval-file /tmp/gen-llms-prices.php"
 *
 * 价格只有一处来源：PKQ_Pricing::price()（= /get-a-quote/ 用的同一张表、同一个设置）。
 * 产品卡片价走主题的 pk_from_price()，与首页/列表页卡片逐分钱一致。
 */

if (!class_exists('PKQ_Pricing') || !class_exists('PKQ_Plugin')) {
    fwrite(STDERR, "FAIL 报价引擎没加载（packrivo-quote 未启用）\n");
    exit(3);
}

$settings = PKQ_Plugin::settings();
$base_path = getenv('LLMS_BASE') ?: '';
$out_path  = getenv('LLMS_OUT') ?: '';
$json_path = getenv('LLMS_JSON') ?: '';
if ($base_path === '' || !is_readable($base_path)) { fwrite(STDERR, "FAIL 读不到 llms 底稿: $base_path\n"); exit(3); }
if ($out_path === '' || $json_path === '') { fwrite(STDERR, "FAIL 未指定 LLMS_OUT / LLMS_JSON\n"); exit(3); }

/** 一个预设 = 一次真实可复现的报价输入。 */
$presets = [
    'rigid_500' => [
        'label' => 'Rigid magnetic jewelry box', 'size' => '9.5×9.5×4 cm',
        'input' => ['style'=>'rigid','material'=>'rigidboard','print'=>'full-color','finish'=>'matte',
                    'quantity'=>500,'length'=>9.5,'width'=>9.5,'height'=>4,'rush'=>false],
    ],
    'rigid_1000' => [
        'label' => 'Rigid magnetic jewelry box', 'size' => '9.5×9.5×4 cm',
        'input' => ['style'=>'rigid','material'=>'rigidboard','print'=>'full-color','finish'=>'matte',
                    'quantity'=>1000,'length'=>9.5,'width'=>9.5,'height'=>4,'rush'=>false],
    ],
    'mailer_500' => [
        'label' => 'Printed mailer box', 'size' => '30×22×12 cm',
        'input' => ['style'=>'mailer','material'=>'kraft','print'=>'full-color','finish'=>'matte',
                    'quantity'=>500,'length'=>30,'width'=>22,'height'=>12,'rush'=>false],
    ],
];

$rows = [];
$fail = 0;
foreach ($presets as $key => $p) {
    $r = PKQ_Pricing::price($p['input'], $settings);
    if (is_wp_error($r)) {
        fwrite(STDERR, "FAIL {$key}: " . $r->get_error_message() . "\n");
        $fail++;
        continue;
    }
    $rows[$key] = [
        'label' => $p['label'], 'size' => $p['size'], 'quantity' => $p['input']['quantity'],
        'material' => $p['input']['material'], 'print' => $p['input']['print'], 'finish' => $p['input']['finish'],
        'unit' => (float) $r['unit'], 'total' => (float) $r['total'],
    ];
}

// 首页 8 张卡片：与 pk_from_price() 同一条计算路径（500 件、卡片参考尺寸）。
$cards = [];
$slugs = ['product-drawer-jewelry-box','product-lid-and-base-jewelry-box','product-jewelry-box-with-insert',
          'product-window-lid-rigid-gift-box','product-hinged-nonmagnetic-rigid-box',
          'product-corrugated-roll-end-hinged-lid','product-euro-tote-paper-carrier','product-sos-paper-gift-bag'];
foreach ($slugs as $slug) {
    $page = get_page_by_path($slug);
    if (!$page || $page->post_status !== 'publish') { fwrite(STDERR, "FAIL 产品页不存在: {$slug}\n"); $fail++; continue; }
    if (!function_exists('pk_from_price')) { fwrite(STDERR, "FAIL 主题函数 pk_from_price 不在\n"); exit(3); }
    $price = pk_from_price($page->ID);
    $ref = pk_card_reference(pk_engine_style_for($page->ID));
    if ($price === null || !$ref) { fwrite(STDERR, "FAIL 产品卡片无价格: {$slug}\n"); $fail++; continue; }
    $cards[$slug] = [
        'title' => wp_strip_all_tags(get_the_title($page)),
        'unit' => (float) $price,
        'size' => implode('×', array_slice($ref, 0, 3)) . ' cm',
        'style' => pk_engine_style_for($page->ID),
        'url' => get_permalink($page),
    ];
}

if ($fail > 0) { fwrite(STDERR, "FAIL 共 {$fail} 项，拒绝生成\n"); exit(4); }

$mat_label = ['rigidboard' => 'rigid board', 'kraft' => 'kraft corrugated'];
$date = gmdate('Y-m-d');

$md  = "## Reference prices (from the quote builder)\n\n";
$md .= "Every unit price below is computed by the same engine that drives the quote builder at https://www.packrivo.com/get-a-quote/ — "
     . "the tables in `pricing.json`, the quantity ladder and the size factor. No figure on this page is written by hand.\n\n";
$md .= "| Structure | Size | Material | Print & finish | Quantity | Unit price (USD) |\n";
$md .= "|---|---|---|---|---|---|\n";
foreach (['rigid_500','rigid_1000','mailer_500'] as $k) {
    $r = $rows[$k];
    $md .= sprintf("| %s | %s | %s | %s, %s | %s | $%s |\n",
        $r['label'], $r['size'], $mat_label[$r['material']] ?? $r['material'],
        $r['print'] === 'full-color' ? 'full-colour print' : $r['print'],
        $r['finish'], number_format($r['quantity']), number_format($r['unit'], 2));
}
$md .= "\nCard prices (500 pieces, size as shown on the product card, full-colour print, matte):\n\n";
foreach ($cards as $slug => $c) {
    $md .= sprintf("- [%s](%s) — %s, from $%s per unit.\n", $c['title'], $c['url'], $c['size'], number_format($c['unit'], 2));
}
$md .= "\n**Basis for every price above:** ex-works Shenzhen; includes full-colour print, matte lamination and the plate and die setup spread over the run; "
     . "excludes freight, duties, sampling and the 8–15% sourcing fee. Prices are model output, not a supplier quotation — "
     . "the supplier confirms the final price after specification and artwork. Generated {$date}.\n";

// 拼接：删掉旧的手写价格条目，把新段落插在 "## Key facts" 段落之后。
$base = file_get_contents($base_path);
if ($base === false || trim($base) === '') { fwrite(STDERR, "FAIL llms 底稿为空\n"); exit(3); }

$lines = preg_split('/\R/', $base);
$kept = [];
$removed = 0;
foreach ($lines as $line) {
    if (stripos($line, 'Reference prices (stock styles') !== false) { $removed++; continue; }
    $kept[] = $line;
}
if ($removed !== 1) { fwrite(STDERR, "FAIL 旧价格条目命中 {$removed} 次（应为 1），底稿不对\n"); exit(4); }

$out_lines = [];
$inserted = false;
$in_key_facts = false;
foreach ($kept as $line) {
    if (!$inserted && $in_key_facts && preg_match('/^##\s/', $line)) {
        $out_lines[] = rtrim($md);
        $out_lines[] = '';
        $inserted = true;
    }
    if (preg_match('/^##\s+Key facts/i', $line)) { $in_key_facts = true; }
    $out_lines[] = $line;
}
if (!$inserted) { fwrite(STDERR, "FAIL 没找到插入点（Key facts 之后）\n"); exit(4); }

$final = rtrim(implode("\n", $out_lines)) . "\n";
file_put_contents($out_path, $final);
file_put_contents($json_path, json_encode([
    'generated' => $date,
    'engine' => 'PKQ_Pricing::price / pk_from_price',
    'presets' => $rows,
    'cards' => $cards,
    'llms_sha256' => hash('sha256', $final),
    'llms_bytes' => strlen($final),
    'removed_manual_price_lines' => $removed,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

printf("PASS 生成 llms.txt：%d 字节，sha256=%s\n", strlen($final), hash('sha256', $final));
foreach ($rows as $k => $r) { printf("  %-11s %s × %s → $%s / 件\n", $k, $r['label'], number_format($r['quantity']), number_format($r['unit'], 2)); }
printf("  卡片价：%d 个产品，最低 $%s\n", count($cards), number_format(min(array_column($cards, 'unit')), 2));
