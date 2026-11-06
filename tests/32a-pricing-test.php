<?php
define('PKQ_STANDALONE', true);
class WP_Error {
    public $code;
    public function __construct($code, $message, $data = []) { $this->code = $code; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
require __DIR__ . '/../packrivo-wp/packrivo-quote/includes/class-pkq-pricing.php';
$json = json_decode(file_get_contents(__DIR__ . '/../packrivo-wp/packrivo-quote/includes/pricing.json'), true);
$settings = ['styles' => $json['styles'], 'min_qty' => 250, 'max_qty' => 250000, 'fee_percent' => 15, 'min_fee' => 150];
$base = ['style'=>'rigid', 'material'=>'rigidboard', 'print'=>'none', 'finish'=>'foil', 'length'=>9.5, 'width'=>9.5, 'height'=>4];
$cases = [[250,4.68,5.38],[500,2.94,3.38],[1000,2.45,2.82],[3000,1.93,2.22]];
$fail = 0;
foreach ($cases as [$quantity,$goods,$unit]) {
    $p = PKQ_Pricing::price($base + ['quantity'=>$quantity], $settings);
    $ok = !is_wp_error($p) && isset($p['goods_unit'], $p['fee'], $p['fee_is_min']) && abs($p['goods_unit']-$goods)<0.011 && abs($p['unit']-$unit)<0.011;
    echo ($ok ? 'PASS' : 'FAIL') . " qty=$quantity actual=" . json_encode($p) . "\n";
    $fail += !$ok;
}
$p = PKQ_Pricing::price($base + ['quantity'=>249], $settings);
$ok = is_wp_error($p) && $p->code === 'pkq_qty';
echo ($ok ? 'PASS' : 'FAIL') . " minimum qty=249\n";
$fail += !$ok;
$p = PKQ_Pricing::price(['style'=>'carton','material'=>'white','print'=>'one-color','finish'=>'none','length'=>8,'width'=>8,'height'=>3,'quantity'=>250], $settings);
$ok = !is_wp_error($p) && isset($p['fee'], $p['fee_is_min']) && $p['fee'] === 150.0 && $p['fee_is_min'];
echo ($ok ? 'PASS' : 'FAIL') . ' minimum fee actual=' . json_encode($p) . "\n";
$fail += !$ok;
exit($fail ? 1 : 0);
