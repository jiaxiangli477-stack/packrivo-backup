<?php
/**
 * 29y：把指定页面的英文文本节点按「完整长度」导出来（extract-en-nodes 会截断到 600 字符，
 * 超长节点拿截断串当词典键永远换不掉，只能走这个）。
 *
 * 跑法：php dump-full-nodes-29y.php <urls.txt> <gateway>
 * 输出每行 JSON：{"url":..,"node":"..","words":[..]}
 */
$list = $argv[1] ?? '';
$gw   = $argv[2] ?? '172.19.0.1';

// Windows 那边的 Set-Content -Encoding utf8 会带 BOM，带进 lang 里就成了 "锘縟e"；
// 警告也不能走 stdout，否则混进 jsonl 让下游 json.loads 直接炸。
ini_set('display_errors', 'stderr');

$EN = ['the','and','is','are','your','our','we','you','with','for','this','that','from','by','to','be','not','or','it',
       'as','at','on','will','can','need','each','per','has','have','of','in','a','an','if','than','then','when','what',
       'how','why','who','all','any','more','most','one','two','three','no','yes','do','does','did','was','were','been',
       'they','their','them','there','here','which','so','but','into','over','under','about','after','before','between',
       'should','would','could','may','must','also','only','both','up','out','off','same','other','such','very','well',
       'keep','used','use','made','make','way','part','level','type','form','state','case','point','line','area','side'];
$LOCAL = ['de' => ['der','die','das','und','ist','mit','für','fur','nicht','ein','eine','einen','auf','von','zu','den','dem','des','sich','werden','auch','wir','sie','ihre','kann','nach','aus','bei','oder','wie','was','mehr','wenn','dann','dass','ohne','über','uber','je','pro','zum','zur','im','am','als','sind','hat','haben','wird','stück','stuck','menge','preis','kosten','schachtel','verpackung']];

foreach (file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $p = preg_split('/\s+/', ltrim(trim($line), "\xEF\xBB\xBF"), 2);
    if (count($p) < 2) continue;
    [$lang, $url] = $p;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ["www.packrivo.com:443:$gw"], CURLOPT_TIMEOUT => 60,
        CURLOPT_ENCODING => '', CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'packrivo-29y-full/1.0',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if (!is_string($body) || $body === '') { fwrite(STDERR, "  空响应 $url\n"); continue; }
    if (!preg_match('~<main\b[^>]*>(.*?)</main>~si', $body, $m)) { fwrite(STDERR, "  没 main $url\n"); continue; }
    $html = preg_replace('~<script\b[^>]*>.*?</script>~si', ' ', $m[1]);
    $html = preg_replace('~<style\b[^>]*>.*?</style>~si', ' ', $html);
    $html = preg_replace('~<svg\b[^>]*>.*?</svg>~si', ' ', $html);
    $html = preg_replace('~<!--.*?-->~s', ' ', $html);
    preg_match_all('~>([^<>]{2,})<~u', $html, $nodes);
    $en = array_flip($EN);
    $loc = array_flip($LOCAL[$lang] ?? []);
    $seen = [];
    foreach ($nodes[1] as $raw) {
        $t = trim(preg_replace('~\s+~u', ' ', html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($t === '' || isset($seen[$t])) continue;
        $seen[$t] = true;
        preg_match_all('~[A-Za-z][A-Za-z\'\-]*~', $t, $wm);
        $words = array_map('strtolower', $wm[0] ?: []);
        if (!$words) continue;
        $enHits = 0; $locHits = 0;
        foreach ($words as $w) { if (isset($en[$w])) $enHits++; if (isset($loc[$w])) $locHits++; }
        if ($enHits < 2 || ($LOCAL[$lang] && $enHits <= $locHits)) continue;
        echo json_encode(['url' => $url, 'node' => $t], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
    }
}
