<?php
$lib=__DIR__.'/../deploy/32a-workspace-moq-lib.php';
require_once is_file($lib)?$lib:__DIR__.'/32a-workspace-moq-lib.php';
if(empty($args[0]))WP_CLI::error('Backup CSV path required');
$backup=pkpw32a_csv_rows($args[0]);$rows=pkpw32a_rows();$failed=0;
$phase=$args[1]??'after';if(!in_array($phase,['before','after'],true))WP_CLI::error('Invalid phase');
foreach($rows as $row){
    $b=$backup[(string)$row['revision']]??null;$json=$row['record_json'];
    $ok=$b && hash_equals($b['stable_sha256'],pkpw32a_stable_hash($json)) && hash_equals($b[$phase.'_sha256'],hash('sha256',$json));
    echo ($ok?'PASS ':'FAIL ').'revision='.$row['revision'].' '.$phase."-hash-and-non-MOQ-bytes\n";$failed+=!$ok;
}
if(count($rows)!==count($backup))$failed++;
global $wpdb;$head=$wpdb->get_row("SELECT page_id,candidate_revision,public_revision FROM {$wpdb->prefix}pkpw_heads WHERE page_id=11",ARRAY_A);
echo 'HEAD '.wp_json_encode($head)."\n";
if($failed)WP_CLI::error("$failed snapshot integrity checks failed");
