<?php
/**
 * 29y 第 3 项：把残留英文定位到「文本节点」，不是句子。
 *
 * 词典是整串替换，能命中的单位是 HTML 里两个标签之间的那段文字。句子切分出来的是
 * 跨标签拼接的结果（面包屑 + 标题 + 段落），拿它当键永远换不掉。所以补词必须按节点干。
 *
 * 跑法：php extract-en-nodes-29y.php <urls.txt> <out.jsonl> [gateway]
 * 输出每行：{"lang":..,"url":..,"http":..,"node":"..","words":[..],"count":N}
 */

$list = $argv[1] ?? '';
$out  = $argv[2] ?? '';
$gw   = $argv[3] ?? '172.19.0.1';
// 第 4 个参数：判定英文句所需的英文功能词命中数。默认 2 会把 "per ... giorni" 这类
// 意大利语节点误判成英文（per/a 都在英文表里），提到 3 可压掉大部分误报。
$minEn = (int) ($argv[4] ?? 2);
if ($list === '' || !is_readable($list) || $out === '') {
    fwrite(STDERR, "usage: php extract-en-nodes-29y.php <urls.txt> <out.jsonl> [gateway]\n");
    exit(2);
}

$EN = ['the','and','is','are','your','our','we','you','with','for','this','that','from','by','to','be','not','or','it',
       'as','at','on','will','can','need','each','per','has','have','of','in','a','an','if','than','then','when','what',
       'how','why','who','all','any','more','most','one','two','three','no','yes','do','does','did','was','were','been',
       'they','their','them','there','here','which','so','but','into','over','under','about','after','before','between',
       'should','would','could','may','must','also','only','both','up','out','off','same','other','such','very','well',
       'keep','used','use','made','make','way','part','level','type','form','state','case','point','line','area','side'];

$LOCAL = [
  'de' => ['der','die','das','und','ist','mit','für','fur','nicht','ein','eine','einen','auf','von','zu','den','dem','des','sich','werden','auch','wir','sie','ihre','kann','nach','aus','bei','oder','wie','was','mehr','wenn','dann','dass','ohne','über','uber','je','pro','zum','zur','im','am','als','sind','hat','haben','wird','stück','stuck','menge','preis','kosten','schachtel','verpackung'],
  'fr' => ['le','la','les','des','du','et','est','pour','avec','vous','nous','votre','notre','une','un','sur','dans','pas','que','qui','au','aux','ce','cette','ces','par','plus','sans','être','etre','sont','avoir','prix','boîte','boite','emballage','quantité','quantite'],
  'es' => ['el','la','los','las','de','del','y','es','para','con','su','nuestro','una','un','en','por','que','se','al','como','más','mas','sin','sobre','este','esta','precio','caja','embalaje','cantidad','pedido'],
  'it' => ['il','lo','la','i','gli','le','di','del','della','e','è','sono','per','con','suo','nostro','una','un','in','per','che','si','al','come','più','piu','senza','questo','questa','prezzo','scatola','imballaggio','quantità','quantita','ordine'],
  'nl' => ['de','het','een','en','is','zijn','voor','met','uw','onze','van','op','niet','dat','die','dit','deze','om','als','meer','zonder','prijs','doos','verpakking','aantal','bestelling'],
  'pt' => ['o','a','os','as','de','do','da','e','é','são','sao','para','com','seu','nossa','uma','um','em','por','que','se','ao','como','mais','sem','este','esta','preço','preco','caixa','embalagem','quantidade','pedido'],
  'ar' => [], 'ja' => [], 'ko' => [], 'zh-CN' => [], 'zh-TW' => [],
];

$ALLOW = [];
foreach (['packrivo','shenzhen','china','moq','pet','pp','pe','pvc','eva','cmyk','pantone','iso','fsc','gsm','mm','cm','kg','usd','usd.','s','t','xl','pdf','ai','eps'] as $w) {
    $ALLOW[$w] = true;
}

function text_of(string $html): string
{
    $html = preg_replace('~<script\b[^>]*>.*?</script>~si', ' ', $html);
    $html = preg_replace('~<style\b[^>]*>.*?</style>~si', ' ', $html);
    $html = preg_replace('~<noscript\b[^>]*>.*?</noscript>~si', ' ', $html);
    $html = preg_replace('~<svg\b[^>]*>.*?</svg>~si', ' ', $html);
    $html = preg_replace('~<!--.*?-->~s', ' ', $html);
    $html = preg_replace('~<[^>]+>~', ' ', $html);
    return trim(preg_replace('~\s+~u', ' ', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function nodes_of(string $html): array
{
    $html = preg_replace('~<script\b[^>]*>.*?</script>~si', ' ', $html);
    $html = preg_replace('~<style\b[^>]*>.*?</style>~si', ' ', $html);
    $html = preg_replace('~<noscript\b[^>]*>.*?</noscript>~si', ' ', $html);
    $html = preg_replace('~<svg\b[^>]*>.*?</svg>~si', ' ', $html);
    $html = preg_replace('~<!--.*?-->~s', ' ', $html);
    preg_match_all('~>([^<>]{2,})<~u', $html, $m);
    $out = [];
    foreach ($m[1] as $raw) {
        $t = trim(preg_replace('~\s+~u', ' ', html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($t !== '') $out[] = $t;
    }
    return $out;
}

$fh = fopen($out, 'w');
$lines = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$total = count($lines);
$n = 0;
foreach ($lines as $line) {
    $n++;
    $p = preg_split('/\s+/', trim($line), 2);
    if (count($p) < 2) continue;
    [$lang, $url] = $p;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ["www.packrivo.com:443:$gw"], CURLOPT_TIMEOUT => 45, CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_ENCODING => '', CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'packrivo-29y-nodes/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($code !== 200 || !is_string($body) || $body === '') {
        fwrite(STDERR, sprintf("  %d/%d %s http=%d 跳过\n", $n, $total, $url, $code));
        continue;
    }
    if (!preg_match('~<main\b[^>]*>(.*?)</main>~si', $body, $m)) continue;

    $en = array_flip($EN);
    $loc = array_flip($LOCAL[$lang] ?? []);
    $seen = [];
    foreach (nodes_of($m[1]) as $node) {
        if (isset($seen[$node])) continue;
        $seen[$node] = true;
        preg_match_all('~[A-Za-z][A-Za-z\'\-]*~', $node, $wm);
        $words = array_map('strtolower', $wm[0] ?: []);
        if (!$words) continue;
        $enHits = 0; $locHits = 0;
        foreach ($words as $w) {
            if (isset($en[$w])) $enHits++;
            if (isset($loc[$w])) $locHits++;
        }
        $isEn = $enHits >= $minEn && (($LOCAL[$lang] ?? []) === [] ? true : $enHits > $locHits);
        if (!$isEn) continue;
        $hits = [];
        foreach ($words as $w) {
            if (isset($ALLOW[$w]) || strlen($w) < 2 || preg_match('~^\d+~', $w)) continue;
            $hits[$w] = true;
        }
        if (!$hits) continue;
        fwrite($fh, json_encode([
            'lang' => $lang, 'url' => $url, 'http' => $code,
            'node' => mb_substr($node, 0, 600, 'UTF-8'),
            'words' => array_keys($hits), 'count' => count($hits),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    }
    if ($n % 25 === 0) {
        fwrite(STDERR, sprintf("  %d/%d\n", $n, $total));
        fflush($fh);
    }
}
fclose($fh);
fwrite(STDERR, sprintf("完成 %d 条\n", $n));
