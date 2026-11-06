<?php
const R1_FIELD_TEST_ONLY=true;require __DIR__.'/../deploy/r1-field-projection.php';
$map=['candle'=>['Old 500 pieces.'=>'Old order quantity.'],'other'=>['Different'=>'New'],'jewelry-box-manufacturer'=>['Jewelry Box Manufacturer: What 500 Units Actually Costs'=>'Jewelry Box Manufacturer: A Cost Example']];
if(r1_field_project('candle','Old 500 pieces.',$map)!=='Old order quantity.')exit(1);
if(r1_field_project('other','Old 500 pieces.',$map)!=='Old 500 pieces.')exit(2);
$html='<h1 class="title">Jewelry Box Manufacturer: What 500 Units Actually Costs</h1><p>500 pieces retained.</p>';
$actual=r1_field_h1_project('jewelry-box-manufacturer',$html,$map);
if($actual!=='<h1 class="title">Jewelry Box Manufacturer: A Cost Example</h1><p>500 pieces retained.</p>')exit(3);
if(r1_field_h1_project('other',$html,$map)!==$html)exit(4);
echo "R1_FIELD_PROJECTION_TEST cases=4 PASS\n";
