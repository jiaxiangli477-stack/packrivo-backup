<?php
// 28m 段1：首页 8 卡诊断——$home_specs 每一项缺什么（封面/价格/页面）
chdir('/var/www/html');
require_once '/var/www/html/wp-load.php';

$specs = [
    ['product-drawer-jewelry-box','Jewelry boxes','Custom Drawer Jewelry Boxes'],
    ['product-magnetic-jewelry-box','Jewelry boxes','Custom Magnetic Jewelry Boxes'],
    ['product-lid-and-base-jewelry-box','Jewelry boxes','Custom Lid-and-Base Jewelry Boxes'],
    ['product-jewelry-box-with-insert','Jewelry boxes','Custom Jewelry Boxes with Fitted Inserts'],
    ['product-window-lid-rigid-gift-box','Gift boxes','Custom Window Gift Boxes'],
    ['product-corrugated-roll-end-hinged-lid','Mailer boxes','Custom Mailer Boxes'],
    ['product-sheet-labels','Labels','Custom Sheet Labels'],
];

function pk28m_row($slug) {
    $page = get_page_by_path($slug);
    if (!$page) return ['slug'=>$slug,'exists'=>false];
    $cover = get_the_post_thumbnail_url($page,'medium_large') ?: get_post_meta($page->ID,'_packrivo_article_cover_url',true);
    $style = function_exists('pk_engine_style_for') ? pk_engine_style_for($page->ID) : null;
    $price = function_exists('pk_from_price') ? pk_from_price($page->ID) : null;
    return [
        'slug'=>$slug, 'exists'=>true, 'id'=>$page->ID, 'status'=>$page->post_status,
        'title'=>get_the_title($page),
        'cover'=>$cover ?: null, 'cover_meta'=>get_post_meta($page->ID,'_packrivo_article_cover_url',true) ?: null,
        'thumb_id'=>get_post_thumbnail_id($page->ID),
        'style'=>$style, 'price'=>$price,
    ];
}

$out = ['specs'=>[], 'all_products'=>[]];
foreach ($specs as [$slug,$group,$label]) {
    $r = pk28m_row($slug);
    $r['group'] = $group; $r['label'] = $label;
    $r['missing'] = [];
    if (empty($r['exists'])) { $r['missing'][] = 'page'; }
    else {
        if (($r['status'] ?? '') !== 'publish') $r['missing'][] = 'status:' . $r['status'];
        if (empty($r['cover'])) $r['missing'][] = 'cover';
        if ($r['price'] === null) $r['missing'][] = 'price';
    }
    $out['specs'][] = $r;
}

$ids = get_posts(['post_type'=>'page','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'','orderby'=>'title','order'=>'ASC']);
foreach ($ids as $id) {
    $slug = get_post_field('post_name',$id);
    $is_product = strpos($slug,'product-')===0 || get_post_meta($id,'_packrivo_remediation_product',true)!=='';
    if (!$is_product) continue;
    $cover = get_the_post_thumbnail_url($id,'medium_large') ?: get_post_meta($id,'_packrivo_article_cover_url',true);
    $style = function_exists('pk_engine_style_for') ? pk_engine_style_for($id) : null;
    $price = function_exists('pk_from_price') ? pk_from_price($id) : null;
    $out['all_products'][] = ['slug'=>$slug,'title'=>get_the_title($id),'cover'=>$cover?:null,'style'=>$style,'price'=>$price];
}

$out['card_ready'] = array_values(array_filter($out['all_products'], fn($p)=>$p['cover'] && $p['price']!==null));
$out['cover_only'] = array_values(array_filter($out['all_products'], fn($p)=>$p['cover'] && $p['price']===null));
$out['price_only'] = array_values(array_filter($out['all_products'], fn($p)=>!$p['cover'] && $p['price']!==null));

echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), "\n";
