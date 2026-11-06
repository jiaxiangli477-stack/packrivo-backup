<?php
const R1_PATHS_TEST_ONLY=true;require __DIR__.'/../deploy/r1-build-scan-list.php';
$paths=r1_build_paths(['/','/about/'],['/','/2026/10/01/post/'],['de','fr']);
foreach(['/','/about/','/de/','/de/about/','/fr/','/fr/about/','/2026/10/01/post/'] as $x)if(!in_array($x,$paths,true))exit(1);
if(count($paths)!==7)exit(2);
$pg=r1_build_paths(['/'],['/blog/?pg=2'],['de']);if(!in_array('/blog/?pg=2',$pg,true))exit(5);
try{r1_build_paths(['//evil'],[],['de']);exit(3);}catch(InvalidArgumentException $e){}
try{r1_build_paths(['/'],[],['../../x']);exit(4);}catch(InvalidArgumentException $e){}
if(r1_scan_blog_pages('/blog/',25)!==['/blog/?pg=2','/blog/?pg=3'])exit(6);
echo "R1_SCAN_PATHS_TEST cases=6 PASS\n";
