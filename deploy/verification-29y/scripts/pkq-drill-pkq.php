<?php
/** 取任意页面上的 PKQ 配置：php pkq-drill-pkq.php <url> <out.json> <gateway> */
$url = $argv[1];
$out = $argv[2];
$gw = $argv[3] ?? '172.19.0.1';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_RESOLVE => ["www.packrivo.com:443:$gw"], CURLOPT_TIMEOUT => 60,
    CURLOPT_ENCODING => '', CURLOPT_USERAGENT => 'packrivo-29y-drill/1.0',
]);
$html = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);
printf("GET %s → HTTP %d，%d B\n", $url, $code, strlen((string) $html));
if (!preg_match('~var PKQ = (\{.*?\});~s', (string) $html, $m)) {
    fwrite(STDERR, "FAIL 页面没有 var PKQ\n");
    exit(2);
}
file_put_contents($out, $m[1]);
$cfg = json_decode($m[1], true);
printf("  已存 %s（nonce %s，styles %s）\n", $out, $cfg['nonce'] ?? '?', implode(',', array_keys($cfg['styles'] ?? [])));
if (preg_match('~pkv4QuoteTargets\s*=\s*(\{.*?\});~s', (string) $html, $t)) {
    printf("  pkv4QuoteTargets=%s\n", substr($t[1], 0, 160));
}
