<?php
const R1_MOQ_TEST_ONLY=true;
require __DIR__.'/../deploy/r1-moq-meta.php';
$spec=r1_moq_spec();$before=[];$after=[];foreach($spec as $key=>$v){$before[$key]=$v['old'];$after[$key]=$v['new'];}
if(r1_moq_state($before)!=='before')exit(1);
if(r1_moq_state($after)!=='after')exit(2);
$mixed=$before;$mixed['448:_packrivo_document_title']=$after['448:_packrivo_document_title'];
try{r1_moq_state($mixed);exit(3);}catch(RuntimeException $e){}
$wrong=$before;$wrong['2884:_packrivo_description'].=' unexpected';
try{r1_moq_state($wrong);exit(4);}catch(RuntimeException $e){}
echo "R1_MOQ_PLAN_TEST cases=4 PASS\n";
