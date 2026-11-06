<?php
const R1_MOQ_RENDER_TEST_ONLY=true;require __DIR__.'/../deploy/r1-moq-render.php';
$old='Order a sample of your custom box or pouch to check size, print and finish before your 500-piece order. Get a price.';
$new='Order a sample of your custom box or pouch to check size, print and finish before your 250-piece order. Get a price.';
if(r1_moq_social_description(2884,$old,$new)!==$new)exit(1);
if(r1_moq_social_description(2884,$old,$old)!==$old)exit(2);
if(r1_moq_social_description(2884,'different',$new)!=='different')exit(3);
if(r1_moq_social_description(1,$old,$new)!==$old)exit(4);
$graph=[['@type'=>'WebPage','description'=>$old],['@type'=>'WebSite','description'=>'unchanged']];
$updated=r1_moq_schema_graph(2884,$graph,$new);if($updated[0]['description']!==$new||$updated[1]['description']!=='unchanged')exit(5);
if(r1_moq_schema_graph(1,$graph,$new)!==$graph)exit(6);
if(r1_moq_schema_graph(2884,$graph,$old)!==$graph)exit(7);
echo "R1_SOCIAL_PROJECTION_TEST cases=7 PASS\n";
