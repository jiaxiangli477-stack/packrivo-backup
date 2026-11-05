<?php
/** WP-CLI: compare sampled rendered card prices with the live quote engine. */
$file = $args[0] ?? '';
$samples = $file && is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
if (!is_array($samples)) WP_CLI::error('Pass the JSON samples extracted from the three rendered pages.');
$failures = 0;
foreach (['/', '/products/', '/jewelry-boxes/'] as $page) {
    $cards = $samples[$page] ?? [];
    if (count($cards) < 3) {
        echo "$page only " . count($cards) . " priced shared cards; NOT TESTED\n";
        $failures++;
        continue;
    }
    foreach (array_slice($cards, 0, 3) as $card) {
        $product = get_page_by_path($card['slug']);
        $style = $product ? pk_engine_style_for($product->ID) : null;
        $ref = $style ? pk_card_reference($style) : null;
        if (!$ref) { echo "$page {$card['slug']} missing reference; FAIL\n"; $failures++; continue; }
        $result = PKQ_Pricing::price(['style'=>$style,'material'=>$ref[3],'print'=>'full-color','finish'=>'matte','quantity'=>250,'length'=>$ref[0],'width'=>$ref[1],'height'=>$ref[2],'rush'=>false], PKQ_Plugin::settings());
        $actual = is_wp_error($result) ? null : (float) $result['unit'];
        $ok = $actual !== null && abs((float)$card['display'] - $actual) < 0.005;
        printf("%s %s qty=250 card=%.2f engine=%.2f %s\n", $page, $card['slug'], $card['display'], $actual, $ok ? 'PASS' : 'FAIL');
        if (!$ok) $failures++;
    }
}
if ($failures) exit(1);
