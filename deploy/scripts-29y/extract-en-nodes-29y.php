<?php
/**
 * 29y 抽取「正文里仍是英文的文本节点」（供翻译入库用）。
 *   php extract-en-nodes-29y.php <urls.txt> <lang> <out.jsonl> [gateway] [local-stop.json]
 * 每个节点输出 {url, lang, tag, text}；同一 text 只保留一次（附首个出处 url）。
 */
$list = $argv[1] ?? '';
$lang = $argv[2] ?? '';
$out  = $argv[3] ?? '';
$gw   = $argv[4] ?? '172.19.0.5';
$locf = $argv[5] ?? '';
if ($list === '' || $lang === '' || $out === '') {
    fwrite(STDERR, "usage: php extract-en-nodes-29y.php <urls.txt> <lang> <out.jsonl> [gateway] [local-stop.json]\n");
    exit(2);
}

$EN = ['the','and','is','are','your','our','we','you','with','for','this','that','from','by','to','be','not','or','it',
       'as','at','on','will','can','need','each','per','has','have','of','in','a','an','if','than','then','when','what',
       'how','why','who','all','any','more','most','one','two','three','no','yes','do','does','did','was','were','been',
       'they','their','them','there','here','which','so','but','into','over','under','about','after','before','between',
       'should','would','could','may','must','also','only','both','up','out','off','same','other','such','very','well'];
$enSet = array_flip($EN);

$LOCAL = ['it' => ['il','lo','la','gli','le','di','del','e','è','per','con','suo','nostro','una','un','in','da','che','si','al','come','più','piu','senza','questo','questa']];
if ($locf !== '' && is_readable($locf)) {
    $j = json_decode((string) file_get_contents($locf), true);
    if (is_array($j) && isset($j[$lang]) && is_array($j[$lang])) {
        $LOCAL[$lang] = array_values(array_unique(array_map('strtolower', $j[$lang])));
    }
}
$loc = array_flip($LOCAL[$lang] ?? []);
$isLatinLocal = ($LOCAL[$lang] ?? []) !== [];

$ALLOW = array_flip(['packrivo','shenzhen','china','chinese','ddp','fob','cif','exw','moq','cbm','lcl','fcl','hs','iso',
    'fsc','gsm','cmyk','pantone','pdf','url','http','https','www','com','html','php','seo','geo','ai','sku','upc','ean',
    'jpeg','jpg','png','webp','svg','whatsapp','wechat','alibaba','amazon','shopify','cm','mm','kg','ml','pcs','pc']);

$fetch = static function (string $u, string $g) {
    $ch = curl_init($u);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ["www.packrivo.com:443:$g"], CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => 'packrivo-29y-extract/1.0',
    ]);
    $b = curl_exec($ch);
    curl_close($ch);
    return is_string($b) ? $b : '';
};

$isEnText = static function (string $t) use ($enSet, $loc, $isLatinLocal, $ALLOW): bool {
    if (!preg_match_all('~[A-Za-z][A-Za-z\'\-]*~', $t, $m)) return false;
    $en = 0; $lo = 0;
    foreach ($m[0] as $w) {
        $lw = strtolower($w);
        if (isset($enSet[$lw])) $en++;
        if (isset($loc[$lw])) $lo++;
    }
    if ($en < 2) return false;
    return $isLatinLocal ? $en > $lo : true;
};

$fh = fopen($out, 'w');
$seen = [];
$urls = array_values(array_filter(array_map('trim', file($list))));
foreach ($urls as $i => $url) {
    $html = $fetch($url, $gw);
    if ($html === '' || !preg_match('~<main\b[^>]*>(.*?)</main>~si', $html, $mm)) {
        fwrite(STDERR, "  skip $url\n");
        continue;
    }
    $main = preg_replace('~<(script|style|noscript|svg|template)\b[^>]*>.*?</\1>~si', ' ', $mm[1]);
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?><div id="r">' . $main . '</div>');
    libxml_clear_errors();
    $xp = new DOMXPath($doc);
    $n = 0;
    foreach ($xp->query('//text()') as $node) {
        $t = trim(preg_replace('~\s+~u', ' ', $node->nodeValue));
        if (mb_strlen($t, 'UTF-8') < 8) continue;
        if (isset($seen[$t])) continue;
        if (!$isEnText($t)) continue;
        $seen[$t] = $url;
        $tag = $node->parentNode ? strtolower($node->parentNode->nodeName) : '?';
        fwrite($fh, json_encode(['url' => $url, 'lang' => $lang, 'tag' => $tag, 'text' => $t],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        $n++;
    }
    fwrite(STDERR, sprintf("  [%d/%d] %s 新增节点 %d，累计 %d\n", $i + 1, count($urls), $url, $n, count($seen)));
}
fclose($fh);
fwrite(STDERR, "DONE 唯一节点 " . count($seen) . " -> $out\n");
