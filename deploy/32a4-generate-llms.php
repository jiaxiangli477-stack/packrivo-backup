<?php
/** Generate staged llms.txt with live quote-engine prices; run only via WP-CLI. */
if(!defined('WP_CLI')||!WP_CLI)exit(1);
if(!class_exists('PKQ_Pricing')||!class_exists('PKQ_Plugin')||!function_exists('pk_from_price'))WP_CLI::error('Quote engine or theme unavailable.');
$base=$args[0]??'';$out=$args[1]??'';$meta=$args[2]??'';
if(!$base||!is_readable($base)||!$out||!$meta||$out===$meta||file_exists($out)||file_exists($meta))WP_CLI::error('Pass readable base and two new staging output paths.');
$s=PKQ_Plugin::settings();
if((int)$s['min_qty']!==250||(float)$s['fee_percent']!==15.0||(float)$s['min_fee']!==150.0)WP_CLI::error('Active MOQ/fee settings do not match the approved 32a policy.');
$presets=[
 'rigid_500'=>['Rigid magnetic jewelry box','9.5×9.5×4 cm',['style'=>'rigid','material'=>'rigidboard','print'=>'full-color','finish'=>'matte','quantity'=>500,'length'=>9.5,'width'=>9.5,'height'=>4]],
 'rigid_1000'=>['Rigid magnetic jewelry box','9.5×9.5×4 cm',['style'=>'rigid','material'=>'rigidboard','print'=>'full-color','finish'=>'matte','quantity'=>1000,'length'=>9.5,'width'=>9.5,'height'=>4]],
 'mailer_500'=>['Printed mailer box','30×22×12 cm',['style'=>'mailer','material'=>'kraft','print'=>'full-color','finish'=>'matte','quantity'=>500,'length'=>30,'width'=>22,'height'=>12]],
];
$rows=[];
foreach($presets as $key=>[$label,$size,$input]){
 $p=PKQ_Pricing::price($input,$s);if(is_wp_error($p))WP_CLI::error('Cannot price '.$key);
 $rows[$key]=['label'=>$label,'size'=>$size,'quantity'=>$input['quantity'],'unit'=>(float)$p['unit'],'goods_total'=>(float)$p['goods_total'],'fee'=>(float)$p['fee'],'total'=>(float)$p['total']];
}
$slugs=['product-drawer-jewelry-box','product-lid-and-base-jewelry-box','product-jewelry-box-with-insert','product-window-lid-rigid-gift-box','product-hinged-nonmagnetic-rigid-box','product-corrugated-roll-end-hinged-lid','product-euro-tote-paper-carrier','product-sos-paper-gift-bag'];
$cards=[];foreach($slugs as $slug){
 $page=get_page_by_path($slug);if(!$page||$page->post_status!=='publish')WP_CLI::error('Missing product '.$slug);
 $price=pk_from_price($page->ID);$ref=pk_card_reference(pk_engine_style_for($page->ID));
 if($price===null||!$ref)WP_CLI::error('Missing card price '.$slug);
 $cards[$slug]=['title'=>wp_strip_all_tags(get_the_title($page)),'unit'=>(float)$price,'size'=>implode('×',array_slice($ref,0,3)).' cm','url'=>get_permalink($page)];
}
$md="## Reference prices (from the quote builder)\n\n";
$md.="| Structure | Size | Quantity | Unit price (USD) |\n|---|---|---|---|\n";
foreach($rows as $row)$md.=sprintf("| %s | %s | %s | $%s |\n",$row['label'],$row['size'],number_format($row['quantity']),number_format($row['unit'],2));
$md.="\nProduct reference prices (500 pieces, size as shown on the product card, full-colour print, matte):\n\n";
foreach($cards as $card)$md.=sprintf("- [%s](%s) — %s, from $%s per unit.\n",$card['title'],$card['url'],$card['size'],number_format($card['unit'],2));
$md.="\n**Basis for every price above:** ex-works Shenzhen; includes full-colour print, matte finish, plate and die setup spread over the run, and the sourcing fee at 15% with a $150 minimum per order; excludes freight, duties and sampling. Your final rate is confirmed on the written quote.\n";
$source=(string)file_get_contents($base);$lines=preg_split('/\R/',$source);$kept=[];$removed=0;
foreach($lines as $line){if(stripos($line,'Reference prices (stock styles')!==false){$removed++;continue;}$kept[]=$line;}
if($removed!==1)WP_CLI::error('Expected one old reference-price line in the approved base.');
$result=[];$in_facts=false;$inserted=false;
foreach($kept as $line){
 if(!$inserted&&$in_facts&&preg_match('/^##\s/',$line)){$result[]=rtrim($md);$result[]='';$inserted=true;}
 if(preg_match('/^##\s+Key facts/i',$line))$in_facts=true;
 $result[]=$line;
}
if(!$inserted)WP_CLI::error('Cannot find insertion point after Key facts.');
$final=rtrim(implode("\n",$result))."\n";
$data=['engine'=>'PKQ_Pricing::price / pk_from_price','presets'=>$rows,'cards'=>$cards,'sha256'=>hash('sha256',$final),'bytes'=>strlen($final)];
if(file_put_contents($out,$final,LOCK_EX)===false)WP_CLI::error('Cannot write staged llms output.');
if(file_put_contents($meta,wp_json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n",LOCK_EX)===false)WP_CLI::error('Cannot write staged price metadata.');
WP_CLI::line('llms_bytes='.strlen($final).' sha256='.$data['sha256'].' cards='.count($cards));
foreach($rows as $key=>$row)WP_CLI::line($key.' qty='.$row['quantity'].' unit='.number_format($row['unit'],2).' fee='.number_format($row['fee'],2));
