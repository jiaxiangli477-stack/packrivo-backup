<?php
/**
 * 演练：用页面下发的 nonce，真提交一次报价（写入演练库的 pkq_order），拿服务端重算结果。
 * 用法：php pkq-drill-submit.php <gateway> <state.json>
 */
$gw = $argv[1] ?? '172.19.0.1';
$statePath = $argv[2] ?? '/tmp/drill-state.json';
$cfg = json_decode(file_get_contents('/tmp/drill-pkq.json'), true);
$state = json_decode(file_get_contents($statePath), true);

$payload = [
    'style' => $state['style'],
    'product' => 'thermoformed-insert-tray',
    'material' => $state['material'],
    'print' => $state['print'],
    'finish' => $state['finish'],
    'quantity' => $state['qty'],
    'length' => $state['length'],
    'width' => $state['width'],
    'height' => $state['height'],
    'rush' => $state['rush'],
    'name' => '29y drill (nl)',
    'email' => 'drill-29y@example.com',
    'company' => 'Packrivo 29y rehearsal',
    'country' => 'NL',
    'phone' => '+31 20 000 0000',
    'notes' => '29y front/back price consistency drill — not a real enquiry',
    'website' => '',
];

$ch = curl_init($cfg['endpoint']);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_RESOLVE => ["www.packrivo.com:443:$gw"],
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-PKQ-Nonce: ' . $cfg['nonce'],
        'X-WP-Nonce: ' . ($cfg['wpNonce'] ?? ''),
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_USERAGENT => 'packrivo-29y-drill/1.0',
]);
$body = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

echo "POST {$cfg['endpoint']}  HTTP $code\n";
echo "请求体: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";
echo "响应体: $body\n";
file_put_contents('/tmp/drill-order-response.json', is_string($body) ? $body : '');
