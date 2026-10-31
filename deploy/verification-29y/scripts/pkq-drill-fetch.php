<?php
/**
 * 演练用：取 /nl/get-a-quote/ 与一个 nl 产品页，抽出 PKQ 配置（含 endpoint/nonce/定价表）。
 * 用法：php pkq-drill-fetch.php <gateway>
 */
$gw = $argv[1] ?? '172.19.0.1';
$targets = [
    'quote' => 'https://www.packrivo.com/nl/get-a-quote/',
    'product' => 'https://www.packrivo.com/nl/thermoformed-insert-tray/',
];

function fetch(string $u, string $g): array
{
    $ch = curl_init($u);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ["www.packrivo.com:443:$g"], CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_ENCODING => '', CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'packrivo-29y-drill/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return [$code, is_string($body) ? $body : ''];
}

foreach ($targets as $key => $url) {
    [$code, $html] = fetch($url, $gw);
    file_put_contents("/tmp/drill-$key.html", $html);
    printf("%s %s HTTP %d  %d B\n", $key, $url, $code, strlen($html));
    if ($key !== 'quote') {
        continue;
    }
    if (preg_match('~var PKQ = (\{.*?\});~s', $html, $m)) {
        $json = $m[1];
        file_put_contents('/tmp/drill-pkq.json', $json);
        $cfg = json_decode($json, true);
        printf("  PKQ 解析: %s，键 %s\n", $cfg === null ? '失败' : '成功', implode(',', array_keys($cfg ?: [])));
        printf("  endpoint=%s\n  nonce=%s\n  currencies=%s minQty=%s maxQty=%s\n",
            $cfg['endpoint'] ?? '?', $cfg['nonce'] ?? '?', $cfg['currency'] ?? '?',
            $cfg['minQty'] ?? '?', $cfg['maxQty'] ?? '?');
        printf("  styles=%s\n", implode(',', array_keys($cfg['styles'] ?? [])));
        printf("  tiers=%s sizeRef=%s tierFloor=%s\n", json_encode($cfg['tiers'] ?? null),
            json_encode($cfg['sizeRef'] ?? null), json_encode($cfg['tierFloor'] ?? null));
    } else {
        echo "  没找到 var PKQ = {...};（检查脚本是否入队）\n";
    }
}
