<?php
$tests=[
    'about'=>'MOQ: 250 pieces per product/design',
    'product-collapsible-magnetic-gift-box'=>'MOQ: 250 pieces per product / design',
    'product-front-tuck-mailer'=>'MOQ: 250 pieces per product / design',
];
$failed=0;
foreach ($tests as $slug=>$text) {
    $page=get_page_by_path($slug);
    $ok=$page && strpos($page->post_content,$text)!==false;
    echo ($ok?'PASS ':'FAIL ').$slug."\n"; $failed+=!$ok;
}
$p=get_page_by_path('products');
$title=$p ? get_post_meta($p->ID,'_yoast_wpseo_title',true) : '';
$ok=$title==='Custom Packaging: 134 Styles, MOQ 250 | Packrivo';
echo ($ok?'PASS ':'FAIL ')."products Yoast title\n"; $failed+=!$ok;
$projection=$p ? get_post_meta($p->ID,'_packrivo_document_title',true) : '';
$ok=$projection==='Custom Packaging: 134 Styles, MOQ 250 | Packrivo';
echo ($ok?'PASS ':'FAIL ')."products theme-projected title\n"; $failed+=!$ok;
$ok=function_exists('packrivo_master_moq') && packrivo_master_moq()===250;
echo ($ok?'PASS ':'FAIL ')."site project MOQ\n"; $failed+=!$ok;
$cost=get_page_by_path('packaging-cost');
$ok=$cost && strpos($cost->post_content,'500 pieces per design, packed in export cartons, ex-works Shenzhen')!==false;
echo ($ok?'PASS ':'FAIL ')."500-piece price reference retained\n"; $failed+=!$ok;
if ($failed) WP_CLI::error("$failed checks failed");
