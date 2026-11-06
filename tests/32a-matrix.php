<?php
define('PKQ_STANDALONE', true);
class WP_Error { public function __construct(...$args) {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
$path = getenv('PRICING_ROOT');
require $path . '/class-pkq-pricing.php';
$data = json_decode(file_get_contents($path . '/pricing.json'), true);
$settings = ['styles'=>$data['styles'], 'min_qty'=>250, 'max_qty'=>250000, 'fee_percent'=>15, 'min_fee'=>150];
$new = getenv('OUTPUT_GOODS') === '1';
$count = 0;
foreach (array_keys($data['styles']) as $style)
foreach (array_keys($data['materials']) as $material)
foreach (array_keys($data['prints']) as $print)
foreach (array_keys($data['finishes']) as $finish)
foreach ([500,1000,2500,5000,10000,25000] as $qty)
foreach ([[9.5,9.5,4],[30,22,12],[8,8,3]] as $dims) {
    $input = ['style'=>$style,'material'=>$material,'print'=>$print,'finish'=>$finish,'quantity'=>$qty,'length'=>$dims[0],'width'=>$dims[1],'height'=>$dims[2]];
    $price = PKQ_Pricing::price($input, $settings);
    if (is_wp_error($price)) { fwrite(STDERR, "ERROR\n"); exit(2); }
    printf("%s,%s,%s,%s,%d,%s,%.2f\n",$style,$material,$print,$finish,$qty,implode('x',$dims),$price[$new?'goods_unit':'unit']);
    $count++;
}
fwrite(STDERR, "MATRIX_ROWS $count\n");
