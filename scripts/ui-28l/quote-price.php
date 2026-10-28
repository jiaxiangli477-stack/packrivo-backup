<?php
define('PKQ_STANDALONE', true);
class WP_Error {public $message; public function __construct($code='', $message=''){$this->message=$message;}}
function is_wp_error($v){return $v instanceof WP_Error;}
require 'E:/破甲工作区/packrivo-wp/packrivo-quote/includes/class-pkq-pricing.php';
$cases=json_decode(stream_get_contents(STDIN),true);
$settings=['min_qty'=>500,'max_qty'=>250000,'setup_print'=>220,'setup_die'=>120,'rush_percent'=>22,'styles'=>[]];
foreach(['mailer'=>1.42,'product'=>1.18,'shipping'=>1.02,'rigid'=>3.4,'carton'=>.74,'pouch'=>.46,'poly'=>.62,'bag'=>.92,'sticker'=>.16] as $name=>$base)$settings['styles'][$name]=['label'=>$name,'base'=>$base];
$out=[];foreach($cases as $input){$r=PKQ_Pricing::price($input,$settings);if(is_wp_error($r))throw new Exception($r->message);$out[]=$r;}echo json_encode($out,JSON_UNESCAPED_SLASHES);
