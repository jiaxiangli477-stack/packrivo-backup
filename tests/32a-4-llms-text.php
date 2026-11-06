<?php
require __DIR__.'/../deploy/32a4-llms-text.php';
$html='<html><head><title>Example</title></head><body><nav>MOQ 500</nav><main><h1>Box</h1><p>MOQ 250 pieces.</p><p>At 500 pieces, $2.50.</p><script>MOQ 500</script><p hidden>MOQ 500</p></main><footer>MOQ 500</footer></body></html>';
$p=pk32a4_page_text($html);if($p['title']!=='Example'||pk32a4_old_moq($p['text'])||strpos($p['text'],'At 500 pieces')===false)exit(1);
foreach(['MOQ: 500 pieces per design','Minimum order quantity: 500 pieces','Our 500-piece minimum','500 piecesMOQ per design','starting quantity is 500'] as $s)if(!pk32a4_old_moq($s))exit(2);
echo "PASS extract visible page text and preserve 500-piece quote example\nPASS detect five stale MOQ forms\n";
