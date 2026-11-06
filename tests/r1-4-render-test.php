<?php
const R1_MOQ_RENDER_TEST_ONLY=true;require __DIR__.'/../deploy/r1-moq-render.php';
$old='Order a sample of your custom box or pouch to check size, print and finish before your 500-piece order. Get a price.';
$new='Order a sample of your custom box or pouch to check size, print and finish before your 250-piece order. Get a price.';
if(r1_moq_social_description(2884,$old,$new)!==$new)exit(1);
if(r1_moq_social_description(2884,$old,$old)!==$old)exit(2);
if(r1_moq_social_description(2884,'different',$new)!=='different')exit(3);
if(r1_moq_social_description(1,$old,$new)!==$old)exit(4);
echo "R1_SOCIAL_PROJECTION_TEST cases=4 PASS\n";
