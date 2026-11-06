<?php
const R1_LINK_TEST_ONLY=true;require __DIR__.'/../deploy/r1-link-audit.php';
if(nodeurl('https://www.packrivo.com/products/?pg=2')!=='/products/?pg=2')exit(1);
if(nodeurl('https://evil.example/x/')!==null)exit(2);
if(nodeurl('mailto:someone@example.com')!==null)exit(3);
if(nodeurl('/blog/?pg=2&topic&sort=newest')!=='/blog/?pg=2')exit(4);
if(nodeurl('/blog/?page_id=2')!=='/blog/?page_id=2')exit(5);
echo "R1_LINK_URL_TEST cases=5 PASS\n";
