<?php
/** Standalone contract tests; no WordPress installation or production data. */
define('ABSPATH', __DIR__ . '/../');
define('PKQ_DIR', __DIR__ . '/../packrivo-wp/packrivo-quote/');
class WP_Error {
    public $code; public $message;
    public function __construct($code, $message) { $this->code=$code; $this->message=$message; }
}
function is_wp_error($v) { return $v instanceof WP_Error; }
function get_post($id) { global $posts; return $posts[$id] ?? null; }
function get_post_meta($id, $key, $single) { global $metadata; return $metadata[$id][$key] ?? ''; }
$file=__DIR__.'/../packrivo-wp/packrivo-home-v4/inc/33b-product-data.php';
if (!is_file($file)) { fwrite(STDERR,"FAIL missing product data API\n"); exit(1); }
require $file;
$n=0;
function check($ok,$message) { global $n; $n++; if (!$ok) { fwrite(STDERR,"FAIL $message\n"); exit(1); } echo "PASS $message\n"; }
$slug='product-demo-box';
$valid=[
 'h1'=>'Existing title','lede'=>'Existing description','category_hub'=>'jewelry-boxes',
 'engine_style'=>'rigid','engine_material'=>'rigidboard',
 'sizes'=>[['label'=>'Example','l_mm'=>95,'w_mm'=>95,'h_mm'=>40,'source'=>'quote']],
 'images'=>[['file'=>'assets/img/products/product-demo-box/approved.webp','alt'=>'Box','caption'=>'Existing image']],
 'construction'=>['Existing structure'],'finishes'=>['matte','foil'],'inserts'=>['film-frame','molded-pulp-on-request'],
 'specs'=>[['label'=>'Known','value'=>'Value','source'=>'confirmed-fact'],['label'=>'Unknown','value'=>'Pending','source'=>'']],
 'faq'=>[['question'=>'Question?','answer'=>'Answer.']],'related'=>['product-related-box'],
 'keywords'=>['primary'=>'existing','secondary'=>['existing secondary']],
];
$out=pk33b_normalize_product($slug,$valid);
check(!is_wp_error($out),'valid data accepted, including film-frame');
check(count(pk33b_visible_specs($out))===1,'blank source spec retained but hidden');
check(count($out['specs'])===2,'blank source retained for later completion');
$number=$valid;$number['specs']=[['label'=>'Recycled','value'=>70,'source'=>'confirmed-fact']];
$number_out=pk33b_normalize_product($slug,$number);
check(!is_wp_error($number_out) && $number_out['specs'][0]['value']==='70','finite numeric spec normalized to string');
$number['specs'][0]['value']=INF;check(is_wp_error(pk33b_normalize_product($slug,$number)),'non-finite numeric spec rejected');
check(pk33b_product_image_path($slug,'approved.webp')==='assets/img/products/product-demo-box/approved.webp','basename anchored inside product directory');
check(pk33b_product_image_path($slug,'../../outside.webp')==='' && pk33b_product_image_path($slug,'https://evil.test/a.webp')==='','image traversal and external URL rejected');
foreach ([
 ['sizes', [['label'=>'X','l_mm'=>95,'w_mm'=>95,'h_mm'=>40,'source'=>'invented']]],
 ['sizes', [['label'=>'X','l_mm'=>-1,'w_mm'=>95,'h_mm'=>40,'source'=>'quote']]],
 ['finishes',['not-in-pricing']], ['inserts',['unknown']], ['engine_style','unknown'], ['engine_material','unknown'],
 ['images',[['file'=>'../outside.webp','alt'=>'','caption'=>'']]],
 ['specs',[['label'=>'X','value'=>'Y','source'=>'unverified']]],
] as [$field,$bad]) {
 $x=$valid;$x[$field]=$bad;check(is_wp_error(pk33b_normalize_product($slug,$x)),"reject invalid $field");
}
$minimal=pk33b_normalize_product($slug,[]);
check(!is_wp_error($minimal) && $minimal['sizes']===[] && $minimal['images']===[] && $minimal['faq']===[],'missing optional data has empty safe defaults');
check(is_wp_error(pk33b_normalize_product('../bad',[])),'bad product slug rejected');
$legacy=$valid;$legacy['related']=['textile-jewelry-pouch'];
check(!is_wp_error(pk33b_normalize_product($slug,$legacy)),'legacy related product slug accepted');
$legacy['related']=['../outside'];check(is_wp_error(pk33b_normalize_product($slug,$legacy)),'related path traversal rejected');
$posts=[1=>(object)['ID'=>1,'post_name'=>$slug,'post_type'=>'page','post_status'=>'publish'],2=>(object)['ID'=>2,'post_name'=>'product-other','post_type'=>'page','post_status'=>'publish'],4=>(object)['ID'=>4,'post_name'=>'product-empty','post_type'=>'page','post_status'=>'publish']];
$metadata=[1=>['pk_product'=>json_encode($valid)],2=>['pk_product'=>'not-json'],4=>['pk_product'=>'{}']];
check(is_array(pk33b_get_product(1)),'stored JSON is decoded and validated');
check(pk33b_get_product(2)===null && pk33b_get_product(3)===null,'missing or invalid meta falls back');
check(pk33b_get_product(4)===null,'record without h1 falls back to original template');
echo "TOTAL $n PASS\n";
