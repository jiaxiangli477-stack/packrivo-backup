<?php
require __DIR__.'/../deploy/32a-workspace-moq-lib.php';
$fixtures=[
    '{"outside":{"moq":500},"commercial":{"moq":500,"moq_basis":"per_product_design","extra":[{"moq":500}]},"tail":"MOQ 500"}',
    "{ \"commercial\" : { \"text\": \"escaped \\\"commercial\\\": {hello}\", \"moq\" : 500, \"unit\":\"pieces\" }, \"x\":500 }",
];
foreach($fixtures as $i=>$json){
    $info=pkpw32a_token($json);$new=pkpw32a_swap($json,500,250);
    if($info['moq']!==500 || pkpw32a_token($new)['moq']!==250 || pkpw32a_stable_hash($json)!==pkpw32a_stable_hash($new) || pkpw32a_swap($new,250,500)!==$json) {fwrite(STDERR,"FAIL fixture=$i\n");exit(1);}
    echo "PASS fixture=$i only-moq-and-roundtrip\n";
}
foreach(['{"commercial":{"moq":500,"moq":250}}','{"commercial":{"moq":"500"}}','{"commercial":{"moq":999}}','{"commercial":{"moq":500},"commercial":{"moq":250}}'] as $json){
    try {pkpw32a_token($json);fwrite(STDERR,"FAIL accepted invalid JSON contract\n");exit(1);} catch (RuntimeException $e) {echo "PASS reject invalid/duplicate MOQ\n";}
}
