<?php
const R1_REDLINE_TEST_ONLY=true;require __DIR__.'/../deploy/r1-redline-scan.php';
foreach(['500-piece','500 Stück','500 pièces','500 件','500個','500개'] as $s)if(!r1_has_500($s))exit(1);
foreach(['250 件','1,000 pieces','fifty'] as $s)if(r1_has_500($s))exit(2);
if(!r1_is_allowed_1094('/2026/10/10/fsc-packaging-label-permission/'))exit(3);
if(r1_is_allowed_1094('/other/'))exit(4);
if(r1_validate_origin('http://127.0.0.1:8080')!=='http://127.0.0.1:8080')exit(5);
try{r1_validate_origin('https://u:p@example.com/path');exit(6);}catch(InvalidArgumentException $e){}
if(!r1_header_noindex(['googlebot: noindex, nofollow']))exit(7);
if(r1_header_noindex(['index, follow']))exit(8);
echo "R1_REDLINE_TEST cases=8 PASS\n";
