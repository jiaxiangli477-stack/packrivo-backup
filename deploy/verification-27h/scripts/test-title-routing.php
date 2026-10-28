<?php
define('ABSPATH',__DIR__); if(($argv[1]??'')==='yoast')define('WPSEO_VERSION','test');
$hooks=[];$case=[];
function cbkey($cb){return is_string($cb)?$cb:(is_array($cb)?implode('::',$cb):spl_object_hash($cb));}
function add_action($hook,$cb,$priority=10,$args=1){global $hooks;$hooks[$hook][$priority][cbkey($cb)]=$cb;}
function add_filter($hook,$cb,$priority=10,$args=1){add_action($hook,$cb,$priority,$args);}
function has_action($hook,$cb){global $hooks;foreach($hooks[$hook]??[] as $p=>$list)if(isset($list[cbkey($cb)]))return $p;return false;}
function remove_action($hook,$cb,$priority=10){global $hooks;unset($hooks[$hook][$priority][cbkey($cb)]);}
function is_singular($type=null){global $case;return $case['page'];}
function get_queried_object_id(){return 1;}
function get_post_meta($id,$key,$single=true){global $case;return $key==='_packrivo_native_page'?($case['native']?'1':'') : ($key==='_packrivo_remediation_product'&&$case['product']?['name'=>'test']:'');}
function current_theme_supports($key){return true;}
require 'E:/破甲工作区/packrivo-wp/packrivo-home-v4/functions.php';
$callback=$hooks['wp'][99][array_key_first($hooks['wp'][99])];
$cases=defined('WPSEO_VERSION')?[['label'=>'Yoast core path retained','page'=>true,'native'=>true,'product'=>true,'parent'=>true,'core'=>true,'custom'=>false,'want'=>true]]:[
 ['label'=>'parent title replaces core','page'=>true,'native'=>true,'product'=>true,'parent'=>true,'core'=>true,'custom'=>false,'want'=>false],
 ['label'=>'product fallback restored','page'=>true,'native'=>true,'product'=>true,'parent'=>false,'core'=>false,'custom'=>false,'want'=>true],
 ['label'=>'directory fallback restored','page'=>true,'native'=>true,'product'=>false,'parent'=>false,'core'=>false,'custom'=>false,'want'=>true],
 ['label'=>'non-native core retained','page'=>true,'native'=>false,'product'=>false,'parent'=>true,'core'=>true,'custom'=>false,'want'=>true],
 ['label'=>'post core retained','page'=>false,'native'=>false,'product'=>false,'parent'=>true,'core'=>true,'custom'=>false,'want'=>true],
 ['label'=>'custom renderer retained','page'=>true,'native'=>true,'product'=>true,'parent'=>false,'core'=>false,'custom'=>true,'want'=>false],
];$fail=0;
foreach($cases as $case){$hooks['wp_head']=[];if($case['parent'])add_action('wp_head','packrivo_output_page_metadata',2);if($case['core'])add_action('wp_head','_wp_render_title_tag',1);if($case['custom'])add_action('wp_head',['PKPW_Product_Renderer','render_title'],1);$callback();$actual=has_action('wp_head','_wp_render_title_tag')!==false;$ok=$actual===$case['want'];echo ($ok?'PASS':'FAIL'),': ',$case['label'],"\n";if(!$ok)$fail++;}
exit($fail?1:0);
