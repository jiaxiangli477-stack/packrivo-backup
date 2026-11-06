<?php
const R1_SITEMAP_LIST_TEST_ONLY=true;require __DIR__.'/../deploy/r1-sitemap-list.php';
if(r1_sitemap_local_path('https://www.packrivo.com/a/?pg=2','www.packrivo.com')!=='/a/?pg=2')exit(1);
try{r1_sitemap_local_path('https://evil.example/a/','www.packrivo.com');exit(2);}catch(InvalidArgumentException $e){}
try{r1_sitemap_local_path('https://www.packrivo.com/a/#x','www.packrivo.com');exit(3);}catch(InvalidArgumentException $e){}
echo "R1_SITEMAP_LIST_TEST cases=3 PASS\n";
