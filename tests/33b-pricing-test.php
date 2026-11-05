<?php
define('ABSPATH', __DIR__ . '/');
define('PKQ_STANDALONE', true);
define('PKQ_DIR', __DIR__ . '/../packrivo-wp/packrivo-quote/');
class WP_Error { public function __construct(...$args) {} }
function is_wp_error($value) { return $value instanceof WP_Error; }
function add_filter(...$args) {}
function pk_from_price($post_id,$quantity) { return 9.99; }
function pk33b_get_product($post_id) { return $GLOBALS['test_structured'][$post_id] ?? null; }
function get_posts($args) { return $GLOBALS['test_lookup'][$args['name'] ?? ''] ?? []; }
class PKQ_Plugin {
    public static function settings() {
        $data=json_decode(file_get_contents(PKQ_DIR.'includes/pricing.json'),true);
        return ['styles'=>$data['styles'],'min_qty'=>250,'max_qty'=>250000,'fee_percent'=>15,'min_fee'=>150];
    }
}
require PKQ_DIR.'includes/class-pkq-pricing.php';
require __DIR__.'/../packrivo-wp/packrivo-home-v4/inc/33b-pricing.php';
$product=['engine_style'=>'rigid','engine_material'=>'rigidboard','sizes'=>[['label'=>'Quote','l_mm'=>95,'w_mm'=>95,'h_mm'=>40,'source'=>'quote']],'finishes'=>['foil']];
$input=pk33b_price_input($product,0); $rows=pk33b_price_rows($product,0);
if (!$input || $input['length']!==9.5 || !isset($rows[250],$rows[1000],$rows[25000])) exit(1);
foreach ($rows as $q=>$row) {
    $expected=PKQ_Pricing::price(array_merge($input,['quantity'=>$q]), PKQ_Plugin::settings());
    if (is_wp_error($expected) || $row['unit']!==$expected['unit'] || $row['goods_total']!==$expected['goods_total'] || $row['fee']!==$expected['fee']) exit(2);
}
$delta=pk33b_finish_delta($product,'foil',0,1000);
$base=PKQ_Pricing::price(array_merge($input,['quantity'=>1000]), PKQ_Plugin::settings());
$foil=PKQ_Pricing::price(array_merge($input,['quantity'=>1000,'finish'=>'foil']), PKQ_Plugin::settings());
if ($delta!==round($foil['unit']-$base['unit'],2)) exit(3);
foreach ([19,1201,95.1] as $unsafe) {
    $bad=$product; $bad['sizes'][0]['h_mm']=$unsafe;
    if (pk33b_price_input($bad,0)!==null || pk33b_price_rows($bad,0)!==[]) exit(4);
}
if (pk33b_price_input(['sizes'=>[]],0)!==null || pk33b_finish_delta($product,'gloss',0,1000)!==null) exit(5);
$partial=$product; $partial['engine_material']='';
if (!pk33b_dimensions($partial,0) || pk33b_price_input($partial,0)!==null || pk33b_price_rows($partial,0)!==[]) exit(6);
$GLOBALS['test_structured']=[1=>$product,2=>$partial];
if (pk33b_card_price(1,250)!==(float)$rows[250]['unit'] || pk33b_card_price(2,250)!==null || pk33b_card_price(3,250)!==9.99) exit(7);
$GLOBALS['test_lookup']=['product-test'=>[(object)['ID'=>1,'post_name'=>'product-test']]];
if (pk33b_quote_product_style('','product-test')!=='rigid' || pk33b_quote_product_style('mailer','product-test')!=='mailer') exit(8);
$_GET=[]; $mismatch=pk33b_quote_reference_spec(['length'=>30,'material'=>'kraft'],'product-test','mailer');
$guard=pk33b_quote_required_fields([],'product-test','mailer',$mismatch);
if (isset($mismatch['length']) || isset($mismatch['material']) || empty($guard['size']) || empty($guard['material'])) exit(9);
$known_guard=pk33b_quote_required_fields([],'product-test','rigid',[]);
if (empty($known_guard['size']) || empty($known_guard['material'])) exit(10);
echo 'PASS tiers='.count($rows)." exact PHP engine; invalid or nonrepresentable dimensions rejected; finish delta includes fee\n";
