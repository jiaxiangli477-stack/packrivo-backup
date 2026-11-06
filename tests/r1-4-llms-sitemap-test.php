<?php
require __DIR__.'/../deploy/32a4-llms-text.php';
if(pk32a4_sitemap_path('https://www.packrivo.com/product-a/','www.packrivo.com')!=='/product-a/')exit(1);
if(pk32a4_sitemap_path('https://www.packrivo.com/blog/?pg=2','www.packrivo.com')!==null)exit(2);
foreach(['https://www.packrivo.com/blog/?pg=1','https://www.packrivo.com/blog/?pg=2&x=1','https://www.packrivo.com/other/?pg=2','https://evil.example/blog/?pg=2','https://www.packrivo.com/de/about/'] as $url){try{pk32a4_sitemap_path($url,'www.packrivo.com');exit(3);}catch(RuntimeException $e){}}
echo "R1_LLMS_SITEMAP_TEST cases=7 PASS\n";
