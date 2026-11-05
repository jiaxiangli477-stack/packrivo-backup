<?php
$rows=[];
foreach(range(250,10000,250) as $q){
 $p=PKQ_Pricing::price(['style'=>'mailer','material'=>'kraft','print'=>'full-color','finish'=>'matte','quantity'=>$q,'length'=>30,'width'=>22,'height'=>12],PKQ_Plugin::settings());
 if(is_wp_error($p))WP_CLI::error('pricing');$rows[]=[$q,$p['goods_total'],$p['fee'],$p['unit'],$p['total']];
}
$slugs=['product-window-lid-rigid-gift-box','product-hinged-nonmagnetic-rigid-box','product-corrugated-roll-end-hinged-lid','product-euro-tote-paper-carrier','product-sos-paper-gift-bag'];$cards=[];
foreach($slugs as $slug){$page=get_page_by_path($slug);if(!$page)WP_CLI::error('missing '.$slug);$style=pk_engine_style_for($page->ID);$r=pk_card_reference($style);$p=PKQ_Pricing::price(['style'=>$style,'material'=>$r[3],'print'=>'full-color','finish'=>'matte','quantity'=>250,'length'=>$r[0],'width'=>$r[1],'height'=>$r[2]],PKQ_Plugin::settings());if(is_wp_error($p))WP_CLI::error('pricing '.$slug);$cards[$slug]=['unit'=>$p['unit'],'style'=>$style,'goods'=>$p['goods_total'],'fee'=>$p['fee'],'total'=>$p['total']];}
echo wp_json_encode(['slider'=>$rows,'cards'=>$cards]);
