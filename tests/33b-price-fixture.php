<?php
/** WP-CLI fixture: output the engine values used by the three pilot pages. */
$slugs=['product-magnetic-jewelry-box','product-drawer-jewelry-box','product-collapsible-magnetic-gift-box'];
$result=[];
foreach ($slugs as $slug) {
    $post=get_page_by_path($slug,OBJECT,'page');
    if (!$post || !($product=pk33b_get_product($post->ID))) WP_CLI::error('Missing pilot '.$slug);
    $input=pk33b_price_input($product,0);$rows=pk33b_price_rows($product,0);$tiles=[];
    if ($input) foreach (array_keys(PKQ_Plugin::settings()['styles']) as $style) {
        $value=PKQ_Pricing::price(array_merge($input,['style'=>$style,'quantity'=>500]), PKQ_Plugin::settings());
        if (is_wp_error($value)) WP_CLI::error('Cannot price style '.$style);
        $tiles[$style]=['label'=>PKQ_Plugin::settings()['styles'][$style]['label'],'unit'=>$value['unit']];
    }
    $deltas=[];foreach ($product['finishes'] as $finish) $deltas[$finish]=pk33b_finish_delta($product,$finish,0,1000);
    $result[$slug]=['rows'=>$rows,'input'=>$input,'finish_deltas'=>$deltas,'tiles'=>$tiles,'card'=>pk33b_card_price($post->ID,250)];
}
echo wp_json_encode($result,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
