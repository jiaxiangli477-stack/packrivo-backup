<?php
const R1_HTTP_SNAPSHOT_TEST_ONLY=true;require __DIR__.'/../deploy/r1-html-snapshot.php';
$r=r1_html_record('/x/',200,'abc','');if($r['sha256']!==hash('sha256','abc')||$r['status']!==200)exit(1);
$r=r1_html_record('/OLD/',301,'','https://www.packrivo.com/old/');if($r['location']!=='https://www.packrivo.com/old/')exit(2);
echo "R1_HTML_SNAPSHOT_TEST cases=2 PASS\n";
