<?php
global $wpdb;
$admin=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);
if (!$admin) WP_CLI::error('No administrator in local rehearsal');
wp_set_current_user((int)$admin[0]);
$public=pkpw_store()->read_public(11);
$candidate=pkpw_store()->read_candidate(11,(int)$admin[0]);
$preview=pkpw_store()->read_preview(11,3,(int)$admin[0]);
$values=[
    'read_public(11)'=>$public['commercial']['moq'] ?? null,
    'read_candidate(11)'=>$candidate['record']['commercial']['moq'] ?? null,
    'read_preview(11,3)'=>$preview['commercial']['moq'] ?? null,
];
$failed=0;
foreach($values as $name=>$value) { $ok=$value===250; echo ($ok?'PASS ':'FAIL ').$name.' moq='.json_encode($value)."\n";$failed+=!$ok; }
$plain=wp_json_encode([$public['faqs']??[],$public['seo']??[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$ok=strpos($plain,'The current MOQ is 500 pieces per product / design.')===false && strpos($plain,'MOQ 500 pieces per product/design;')===false;
echo ($ok?'PASS ':'FAIL ')."public projection removes known stale MOQ text\n";$failed+=!$ok;
$oldPreview=wp_json_encode([$preview['faqs']??[],$preview['seo']??[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$ok=strpos($oldPreview,'The current MOQ is 500 pieces per product / design.')===false && strpos($oldPreview,'MOQ 500 pieces per product/design;')===false;
echo ($ok?'PASS ':'FAIL ')."historical preview removes known stale MOQ text\n";$failed+=!$ok;
$record=$candidate['record']??null;
if(is_array($record))foreach([250,500] as $qty){
    $record['commercial']['moq']=$qty;$result=PKPW_Product_Validation::validate(11,$record,false);
    $fields=is_wp_error($result)?(array)($result->get_error_data()['fields']??[]):[];
    $rejected=in_array('commercial',$fields,true);$ok=$qty===250?!$rejected:$rejected;
    echo ($ok?'PASS ':'FAIL ')."validation moq=$qty commercial_rejected=".($rejected?'yes':'no')."\n";$failed+=!$ok;
}else{echo "FAIL missing candidate for validation\n";$failed++;}
$rows=$wpdb->get_results("SELECT revision,record_json FROM {$wpdb->prefix}pkpw_snapshots ORDER BY revision",ARRAY_A);
foreach($rows as $row){
    $data=json_decode($row['record_json'],true);$value=$data['commercial']['moq']??null;
    $projection=PKPW_Product_Validation::public_projection($data,(int)$row['revision']);
    $encoded=wp_json_encode([$projection['faqs']??[],$projection['seo']??[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $ok=$value===250 && ($projection['commercial']['moq']??null)===250 && strpos($encoded,'The current MOQ is 500 pieces per product / design.')===false && strpos($encoded,'MOQ 500 pieces per product/design;')===false;
    echo ($ok?'PASS ':'FAIL ').'snapshot revision='.$row['revision'].' moq='.json_encode($value)." projection_normalized=".($ok?'yes':'no')."\n";$failed+=!$ok;
}
if (!$rows){echo "FAIL no snapshots\n";$failed++;}
if ($failed) WP_CLI::error("$failed MOQ checks failed");
