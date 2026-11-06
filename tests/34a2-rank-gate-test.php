<?php
$source=file_get_contents(dirname(__DIR__).'/deploy/34a-keyword-map.php');
$start=strpos($source,'function pk34a_norm(');$end=strpos($source,'function pk34a_path(',$start);
if ($start===false || $end===false) throw new RuntimeException('Cannot locate rank helpers.');
eval(substr($source,$start,$end-$start));
$cases=array(
    array('New MOQ 250 | Packrivo','New MOQ 500 | Packrivo','New MOQ 250 | Packrivo',true,true),
    array('New MOQ 250 | Packrivo','New MOQ 250 | Packrivo','Changed rank title',true,false),
    array('Price at 250 units | Packrivo','Price at 500 units | Packrivo','',false,false),
    array('New MOQ 250 | Packrivo','New MOQ 250 | Packrivo','',true,true),
);
foreach ($cases as $i=>$case) {
    $result=pk34a_rank_matches($case[0],$case[1],$case[2],$case[3]);
    if ($result!==$case[4]) throw new RuntimeException('Rank gate case '.($i+1).' failed');
}
echo 'RANK_GATE_CASES='.count($cases)." PASS\n";
