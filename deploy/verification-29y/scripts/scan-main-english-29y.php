<?php
/**
 * 29y 第 3 项：11 语种页面 <main> 残留英文扫描（v3）。
 *
 * v2 会「按句判英文」，句子被判成英文才统计里面的 ASCII 词。
 * v3 按评审要求改成双口径，并且不让本族常用词把英文短语抵消掉：
 *
 *   口径 A  residual_raw      —— 原始英文词数（v2 的口径，保留以便和基线对比）
 *   口径 B  residual_filtered —— 扣掉本语种功能词（LOCAL_STOP）后的词数  ← 验收看这个
 *
 *   硬规则：连续出现 ≥3 个英文词时，这一段里每个词都算残留，
 *           即使它同时是本语种常用词（an / in / is / was / will / on / no / come …）。
 *           理由：这些词在英文里也是常用词，允许它们「抵消」等于给英文短语开后门。
 *
 * 跑法：php scan-main-english-29y.php <urls.txt> <out.jsonl> [gateway] [local-stop.json]
 */
$list = $argv[1] ?? '';
$out  = $argv[2] ?? '';
$gw   = $argv[3] ?? '172.19.0.1';
$locf = $argv[4] ?? '';
if ($list === '' || !is_readable($list) || $out === '') {
    fwrite(STDERR, "usage: php scan-main-english-29y.php <urls.txt> <out.jsonl> [gateway] [local-stop.json]\n");
    exit(2);
}

$EN = ['the','and','is','are','your','our','we','you','with','for','this','that','from','by','to','be','not','or','it',
       'as','at','on','will','can','need','each','per','has','have','of','in','a','an','if','than','then','when','what',
       'how','why','who','all','any','more','most','one','two','three','no','yes','do','does','did','was','were','been',
       'they','their','them','there','here','which','so','but','into','over','under','about','after','before','between',
       'should','would','could','may','must','also','only','both','up','out','off','same','other','such','very','well'];

/* 本族功能词：优先从 local-stop.json 读（唯一真源），取不到再用内置兜底。 */
$LOCAL = [
  'de' => ['der','die','das','und','ist','mit','für','fur','nicht','ein','eine','einen','einem','einer','auf','von','zu','den','dem','des','sich','werden','auch','wir','sie','ihre','ihren','kann','nach','aus','bei','oder','wie','was','mehr','wenn','dann','dass','ohne','über','uber','je','pro','zwei','vier','zum','zur','im','am','als','sind','hat','haben','wird'],
  'fr' => ['le','la','les','des','du','et','est','pour','avec','vous','nous','votre','notre','une','un','sur','dans','pas','que','qui','au','aux','ce','cette','ces','par','plus','sans','être','etre','sont','avoir'],
  'es' => ['el','la','los','las','de','del','y','es','para','con','su','nuestro','una','un','en','por','que','se','al','como','más','mas','sin','sobre','este','esta'],
  'it' => ['il','lo','la','gli','le','di','del','e','è','per','con','suo','nostro','una','un','in','da','che','si','al','come','più','piu','senza','questo','questa'],
  'nl' => ['de','het','een','en','is','voor','met','uw','ons','van','op','in','te','dat','die','niet','ook','wordt','per','bij','om','als'],
  'pt' => ['o','a','os','as','de','do','da','e','é','para','com','seu','nosso','uma','um','em','por','que','se','ao','como','mais','sem','sobre','este','esta'],
  'ar' => [], 'ja' => [], 'ko' => [], 'zh-CN' => [], 'zh-TW' => [],
];
if ($locf !== '' && is_readable($locf)) {
    $j = json_decode((string) file_get_contents($locf), true);
    if (is_array($j)) {
        foreach ($j as $lg => $arr) {
            if (strpos($lg, '_') === 0 || !is_array($arr)) continue;
            $LOCAL[$lg] = array_values(array_unique(array_map('strtolower', $arr)));
        }
    }
}

$ALLOW = array_flip([
    'packrivo','shenzhen','china','chinese','guangdong','asia','europe','european','germany','france','spain','italy',
    'netherlands','portugal','japan','korea','ddp','fob','cif','exw','moq','cbm','lcl','fcl','hs','iso','fsc','gsm','cmyk',
    'pantone','pdf','url','http','https','www','com','html','php','seo','geo','ai','sku','upc','ean','jpeg','jpg','png',
    'webp','svg','whatsapp','wechat','alibaba','amazon','shopify','cm','mm','kg','ml','pcs','pc',
]);

function text_of(string $html): string
{
    foreach (['script','style','noscript','svg','template'] as $t) {
        for ($i = 0; $i < 3; $i++) {
            $html = preg_replace('~<' . $t . '\b[^>]*>.*?</' . $t . '>~si', ' ', $html);
        }
    }
    $html = preg_replace('~<!--.*?-->~s', ' ', $html);
    $html = preg_replace('~<[^>]+>~', ' ', $html);
    $text = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('~\s+~u', ' ', $text));
}

function sentences(string $text): array
{
    $parts = preg_split('~(?<=[.!?;:。！？；：])\s+~u', $text);
    if (!$parts) return [];
    return array_values(array_filter(array_map('trim', $parts), static fn($s) => $s !== ''));
}

function ascii_words(string $s): array
{
    preg_match_all('~[A-Za-z][A-Za-z\'\-]*~', $s, $m);
    return array_map('strtolower', $m[0] ?: []);
}

$fh = fopen($out, 'w');
$lines = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$total = count($lines);
$n = 0; $flagged = 0; $bad = 0;
$enSet = array_flip($EN);

foreach ($lines as $line) {
    $n++;
    $p = preg_split('/\s+/', trim($line), 2);
    if (count($p) < 2) continue;
    [$lang, $url] = $p;
    $lang = ltrim($lang, "\xEF\xBB\xBF");

    $fetch = static function (string $u, string $g, int $tmo): array {
        $ch = curl_init($u);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_RESOLVE => ["www.packrivo.com:443:$g"], CURLOPT_TIMEOUT => $tmo,
            CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_ENCODING => '', CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'packrivo-29y-scan/3.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $bytes = (int) curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        curl_close($ch);
        return [$code, $body, $bytes];
    };

    [$code, $body, $bytes] = $fetch($url, $gw, 40);
    if (!in_array($code, [200], true)) {
        usleep(1200000);
        [$code, $body, $bytes] = $fetch($url, $gw, 60);
    }

    $rec = ['lang' => $lang, 'url' => $url, 'http' => $code, 'bytes' => $bytes];
    if (!is_string($body) || $body === '') {
        $rec += ['main_found' => false, 'residual_count' => -1, 'residual_raw' => -1, 'error' => 'empty'];
        $bad++;
        fwrite($fh, json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        continue;
    }
    if (!preg_match('~<main\b[^>]*>(.*?)</main>~si', $body, $m)) {
        $rec += ['main_found' => false, 'residual_count' => -1, 'residual_raw' => -1];
        $bad++;
        fwrite($fh, json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        continue;
    }

    $text = text_of($m[1]);
    $loc = array_flip($LOCAL[$lang] ?? []);
    $isLatinLocal = ($LOCAL[$lang] ?? []) !== [];

    /* 逐句判英文（v2 口径），把命中词按原顺序摊平成一条流，供连续段判定用。 */
    $stream = [];      // ['w' => word, 'local' => bool]
    $segments = [];
    foreach (sentences($text) as $s) {
        $words = ascii_words($s);
        if (!$words) continue;
        $enHits = 0; $locHits = 0;
        foreach ($words as $w) {
            if (isset($enSet[$w])) $enHits++;
            if (isset($loc[$w])) $locHits++;
        }
        $isEn = $enHits >= 2 && ($isLatinLocal ? $enHits > $locHits : true);
        if (!$isEn) continue;
        $hit = false;
        foreach ($words as $w) {
            if (isset($ALLOW[$w])) continue;          // 品牌/单位/代码不算
            if (strlen($w) < 2) continue;
            if (preg_match('~^\d+~', $w)) continue;
            $stream[] = ['w' => $w, 'local' => isset($loc[$w])];
            $hit = true;
        }
        if ($hit) $segments[] = mb_substr($s, 0, 240, 'UTF-8');
    }

    /* 连续段：只在「判为英文的句子」内部切。命中词序列里 >=3 个连续英文词算一段，
       品牌/单位/代码与单字符、纯数字会打断一段（它们本来就不算残留）。 */
    $runsOf = [];
    $runSamples = [];
    foreach ($segments as $segText) {
        $cur = [];
        foreach (ascii_words($segText) as $w) {
            $ok = !isset($ALLOW[$w]) && strlen($w) >= 2 && !preg_match('~^\d+~', $w);
            if ($ok) {
                $cur[] = $w;
            } else {
                if (count($cur) >= 3) $runsOf[] = $cur;
                $cur = [];
            }
        }
        if (count($cur) >= 3) $runsOf[] = $cur;
    }

    $localInRun = [];
    $runWords = 0;
    foreach ($runsOf as $r) {
        foreach ($r as $w) {
            $runWords++;
            if (isset($loc[$w])) $localInRun[$w] = true;
        }
        if (count($runSamples) < 3) $runSamples[] = implode(' ', array_slice($r, 0, 12));
    }

    $rawCount = count($stream);
    $filteredCount = 0;
    $kept = [];
    $rawKept = [];
    foreach ($stream as $tok) {
        $rawKept[$tok['w']] = true;
        $isLocal = $tok['local'];
        if (!$isLocal) { $filteredCount++; $kept[$tok['w']] = true; continue; }
        if (isset($localInRun[$tok['w']])) { $filteredCount++; $kept[$tok['w']] = true; }
    }

    $rec['main_found'] = true;
    $rec['main_text_len'] = mb_strlen($text, 'UTF-8');
    // 口径 A：原始英文词数（去重，和 v2 基线同口径可比）
    $rec['residual_raw'] = count($rawKept);
    $rec['residual_raw_tokens'] = $rawCount;
    // 口径 B：扣掉本语种功能词后的词数（去重）—— 验收看这个
    $rec['residual_count'] = count($kept);
    $rec['residual_tokens'] = $filteredCount;
    $rec['local_stop_removed'] = count($rawKept) - count($kept);
    $rec['long_runs'] = count($runsOf);
    $rec['long_run_words'] = $runWords;
    $rec['long_run_samples'] = $runSamples;
    $rec['residual_words'] = array_keys($kept);
    $rec['english_segments'] = array_slice($segments, 0, 4);
    $rec['segment_count'] = count($segments);
    if ($filteredCount > 5) $flagged++;

    fwrite($fh, json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    if ($n % 50 === 0) {
        fwrite(STDERR, sprintf("  %d/%d  扣过本族词后>5 的页面 %d  异常 %d\n", $n, $total, $flagged, $bad));
        fflush($fh);
    }
}
fclose($fh);
fwrite(STDERR, sprintf("完成 %d 条，扣过本族词后>5 的页面 %d，异常 %d\n", $n, $flagged, $bad));
