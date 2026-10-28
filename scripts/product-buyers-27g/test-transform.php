<?php
if (is_file(__DIR__ . '/lib.php')) require __DIR__ . '/lib.php';
if (!function_exists('pk27g_replace_h1')) { echo "FAIL: H1 transform not implemented\n"; exit(1); }
$old='<section data-x="a"><h1 class="hero">Old <em>title</em></h1><p>Keep \\slashes &amp; exact bytes.</p></section>';
$new=pk27g_replace_h1($old,'New & reviewed title');
$expected='<section data-x="a"><h1 class="hero">New &amp; reviewed title</h1><p>Keep \\slashes &amp; exact bytes.</p></section>';
if($new!==$expected) { echo "FAIL: body changed\n"; exit(1); }
foreach(['<p>No heading</p>','<h1>A</h1><h1>B</h1>'] as $bad){try{pk27g_replace_h1($bad,'New');echo "FAIL: ambiguous heading accepted\n";exit(1);}catch(RuntimeException $e){}}
echo "PASS: exact H1 replacement preserves other bytes and rejects ambiguous markup\n";
