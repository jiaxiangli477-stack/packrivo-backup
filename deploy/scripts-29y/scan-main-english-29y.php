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
 * v4.2（2026-11-03，负责人批）：CJK 语种（ja / ko / zh-CN / zh-TW）走分支——
 *   CJK 正文没有空格分词，`ascii_words()` 会把整段日文/中文当成一个 token，
 *   于是「词数」指标失效（/ja/ 首页 85 个"词"里 60+ 是日文串）。
 *   CJK 分支改为：
 *     R1 计分：连续 ≥3 个英文词成段（白名单、本族词过滤照旧）→ 页 PASS 判据 = 段数 0
 *     R2 报备：连续 2 个英文词的对，单列成 "pairs" 清单，每条必须有人工结论（译掉 / 进 allow-<lang>）
 *     整句判英文对 CJK 关闭（没有拉丁本族词可比，enHits>=2 必然误判）
 *     只排除 <span class="pk-post-title">（博客文章标题，博客只有英文版是既定期望）
 *   拉丁语种逻辑一行未动。
 *
 * 跑法：php scan-main-english-29y.php <urls.txt> <out.jsonl> [gateway] [local-stop.json]
 */
$list = $argv[1] ?? '';
$out  = $argv[2] ?? '';
$gw   = $argv[3] ?? '172.19.0.1';
$locf = $argv[4] ?? '';
$lexf = $argv[5] ?? '';
$allowdir = $argv[6] ?? '';
if ($list === '' || !is_readable($list) || $out === '') {
    fwrite(STDERR, "usage: php scan-main-english-29y.php <urls.txt> <out.jsonl> [gateway] [local-stop.json] [en-lexicon.json] [allow-dir]\n");
    exit(2);
}

/* 与本族词表同形的英文词：只允许抵消孤立的单个词，不能拿它们把整句判成非英文。
   真源是 local-stop.json 的 "_homo" 键（{"_homo":{"fr":[…],"it":[…]}}），下面这份只是读不到时的兜底。 */
$HOMO = [
    'it' => ['all', 'come', 'in', 'per', 'a'],
    'pt' => ['as', 'do', 'no', 'a'],
    'de' => ['an', 'in', 'so', 'was', 'will', 'die', 'war', 'man', 'also'],
    'nl' => ['in', 'is', 'over', 'was', 'want'],
    'fr' => ['on', 'or', 'but', 'plus',
             'action', 'articles', 'configuration', 'direction', 'eva', 'expose', 'journal', 'large',
             'motif', 'notes', 'production', 'protection', 'simple', 'structure', 'visible', 'zone'],
    'es' => ['no', 'a'],
];

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
/* CJK 语种名单：从 local-stop.json 的 "_cjk" 读，脚本里不硬编码语种名。 */
$CJK = [];
if ($locf !== '' && is_readable($locf)) {
    $j = json_decode((string) file_get_contents($locf), true);
    if (is_array($j)) {
        foreach ($j as $lg => $arr) {
            if (strpos($lg, '_') === 0 || !is_array($arr)) continue;
            $LOCAL[$lg] = array_values(array_unique(array_map('strtolower', $arr)));
        }
        // 同形词表：只从 "_homo" 读，脚本里不硬编码
        if (isset($j['_homo']) && is_array($j['_homo'])) {
            foreach ($j['_homo'] as $lg => $arr) {
                if (!is_array($arr)) continue;
                $HOMO[$lg] = array_values(array_unique(array_map('strtolower', $arr)));
            }
        }
        // CJK 语种名单：这些语种走「只认段」的计数分支
        if (isset($j['_cjk']) && is_array($j['_cjk'])) {
            foreach ($j['_cjk'] as $lg) $CJK[(string) $lg] = true;
        }
    }
}

$ALLOW = array_flip([
    'packrivo','shenzhen','china','chinese','guangdong','asia','europe','european','germany','france','spain','italy',
    'netherlands','portugal','japan','korea','ddp','fob','cif','exw','moq','cbm','lcl','fcl','hs','iso','fsc','gsm','cmyk',
    'pantone','pdf','url','http','https','www','com','html','php','seo','geo','ai','sku','upc','ean','jpeg','jpg','png',
    'webp','svg','whatsapp','wechat','alibaba','amazon','shopify','cm','mm','kg','ml','pcs','pc',
    'mib','gib','kib','mhz','ghz',
]);

/* 按语种豁免表：只从 deploy/verification-29y/i18n/allow-<lang>.json 读，脚本里不硬编码任何豁免词。
   两种粒度：
     {"phrase":"Pinch Bottom Bags","type":"专有名词","reason":"…","pages":["/it/…/"]}  —— 只在 pages 列出的页面、
        且整个短语完整出现时跳过；短语以外的单词（bags/bottom/cover/america…）照常算残留。
     {"word":"packaging","type":"借词","reason":"…"}                                    —— 全站按单词豁免，每语种最多 15 个。
   两种写法混在 entries 里，靠有没有 phrase 字段区分。 */
function allow_of(string $dir, string $lang): array
{
    static $cache = [];
    if (isset($cache[$lang])) return $cache[$lang];
    $p = rtrim($dir, '/\\') . '/allow-' . $lang . '.json';
    $res = ['words' => [], 'phrases' => []];
    if ($dir !== '' && is_readable($p)) {
        $j = json_decode((string) file_get_contents($p), true);
        foreach (($j['entries'] ?? []) as $e) {
            $type = (string) ($e['type'] ?? '');
            if (isset($e['phrase']) && trim((string) $e['phrase']) !== '') {
                $ph = trim((string) $e['phrase']);
                $pages = array_values(array_filter(array_map(
                    static fn($x) => trim((string) $x), (array) ($e['pages'] ?? [])
                ), static fn($x) => $x !== ''));
                $res['phrases'][] = ['phrase' => $ph, 'type' => $type, 'pages' => $pages];
                continue;
            }
            $w = mb_strtolower(trim((string) ($e['word'] ?? '')), 'UTF-8');
            if ($w !== '') $res['words'][$w] = $type;
        }
    }
    return $cache[$lang] = $res;
}

/** 短语是否适用当前页面：pages 为空 = 不适用；否则按路径完全匹配或前缀匹配（含 /it/ 这种首页）。 */
function allow_phrase_applies(array $ph, string $path): bool
{
    foreach ($ph['pages'] as $pp) {
        if ($pp === $path) return true;
        $a = rtrim($pp, '/');
        $b = rtrim($path, '/');
        if ($a === $b) return true;
        if ($a !== '' && strpos($b . '/', $a . '/') === 0) return true;
    }
    return false;
}

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
    // 按「完整词」切，不能按 ASCII 字母切：'referência' 会被切成 'refer'+'ncia'，
    // 而 'refer' 恰好在英文词表里 -> 葡语/法语页凭空多出英文票。\p{L} 把带重音的字母留在词内。
    preg_match_all('~[\p{L}][\p{L}\'’\-]*~u', $s, $m);
    return array_map('strtolower', $m[0] ?: []);
}

$fh = fopen($out, 'w');
$lines = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$total = count($lines);
$n = 0; $flagged = 0; $bad = 0;
$enSet = array_flip($EN);

/* 长连续段判定用的英文词表：从站点英文原文（词典键）抽出来的 3k+ 词，取不到就退回功能词表 */
$LEX = $enSet;
if ($lexf !== '' && is_readable($lexf)) {
    $jx = json_decode((string) file_get_contents($lexf), true);
    if (is_array($jx) && isset($jx['words']) && is_array($jx['words']) && count($jx['words']) > 200) {
        $LEX = array_flip($jx['words']);
    } else {
        fwrite(STDERR, "WARN 英文词表读不动（$lexf），长连续段判定退回功能词表\n");
    }
} else {
    fwrite(STDERR, "WARN 未给 --en-lexicon，长连续段判定退回功能词表（会漏报）\n");
}

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

    $isCjk = isset($CJK[$lang]);
    if ($isCjk) {
        /* 只排除博客文章标题那一个 span：博客只有英文版、列表页显示英文标题是既定期望。
           分类名 / Journal 标签 / All articles 这类界面文字照常扫、照常翻译。 */
        $m[1] = (string) preg_replace('~<span\b[^>]*\bclass="[^"]*\bpk-post-title\b[^"]*"[^>]*>.*?</span>~si', ' ', $m[1]);
    }
    $text = text_of($m[1]);
    $loc = array_flip($LOCAL[$lang] ?? []);
    $isLatinLocal = ($LOCAL[$lang] ?? []) !== [];
    $alwCfg = allow_of($allowdir, $lang);   // 本语种豁免表：单词（全站）+ 短语（限页）
    $alw = $alwCfg['words'];
    $alwUsed = [];
    $upath = (string) parse_url($url, PHP_URL_PATH);
    if ($upath === '') $upath = '/';
    foreach ($alwCfg['phrases'] as $ph) {
        if (!allow_phrase_applies($ph, $upath)) continue;
        $cnt = 0;
        // 整短语命中才豁免：用句号替换，既消掉这些 token，也断开它们与相邻词的连续段
        $text = (string) preg_replace('~' . preg_quote($ph['phrase'], '~') . '~iu', '. ', $text, -1, $cnt);
        if ($cnt > 0) $alwUsed[$ph['phrase']] = $ph['type'] . '（短语，' . $cnt . ' 处）';
    }

    /* 逐句判英文（v2 口径），把命中词按原顺序摊平成一条流，供残留词数统计用。
       CJK 语种整块跳过：没有拉丁本族词可比，enHits>=2 会把任何含两个英文词的日文/中文句判成英文。 */
    $stream = [];      // ['w' => word, 'local' => bool]
    $segments = [];
    $homo = $HOMO[$lang] ?? [];            // 与英文同形的本族词：只抵消孤立单词，不参与整句判英文
    foreach (($isCjk ? [] : sentences($text)) as $s) {
        $words = ascii_words($s);
        if (!$words) continue;
        $words = array_map(static fn($w) => mb_strtolower($w, 'UTF-8'), $words); // 大小写不敏感：Le/le 同词
        $enHits = 0; $locHits = 0;
        foreach ($words as $w) {
            if (isset($enSet[$w])) $enHits++;
            if (isset($loc[$w]) && !isset($homo[$w])) $locHits++;
        }
        $isEn = $enHits >= 2 && ($isLatinLocal ? $enHits > $locHits : true);
        if (!$isEn) continue;
        $hit = false;
        foreach ($words as $w) {
            if (isset($alw[$w])) { $alwUsed[$w] = $alw[$w]; continue; }   // 本语种豁免表
            if (isset($ALLOW[$w])) continue;          // 品牌/单位/代码不算
            if (strlen($w) < 2) continue;
            if (preg_match('~^\d+~', $w)) continue;
            $stream[] = ['w' => $w, 'local' => isset($loc[$w])];
            $hit = true;
        }
        if ($hit) $segments[] = mb_substr($s, 0, 240, 'UTF-8');
    }

    /* 连续段（v4 口径）：在**整页正文**上切，不受「整句判成英文」限制。
       规则：连续 >=3 个 token 都在英文词表里，且其中至少 1 个不在本语种 LOCAL_STOP 里，就算一段。
       品牌/单位/代码、单字符、纯数字、不在英文词表里的词都会打断一段。
       v4.1（2026-11-02 负责人）：整段都由本语种同形词（"_homo"）组成时不算一段。 */
    $runsOf = [];
    $runSamples = [];
    $pairs = [];                                    // CJK 专有：连续 2 个英文词的对（R2 清单）
    $flushRun = static function (array $cur, array &$runsOf, array &$runSamples) use ($loc, $homo) {
        if (count($cur) < 3) return;
        /* 整段都是本语种同形词（die we in / open is is / of contact die …）→ 不是英文残留。
           负责人 2026-11-02 规则：段里只要混进一个真正的英文词，照常算残留。 */
        $allHomo = true;
        foreach ($cur as $w) {
            if (!isset($homo[$w])) { $allHomo = false; break; }
        }
        if ($allHomo) return;
        $hasNonLocal = false;
        foreach ($cur as $w) {
            if (!isset($loc[$w])) { $hasNonLocal = true; break; }
        }
        if (!$hasNonLocal) return;
        $runsOf[] = $cur;
        if (count($runSamples) < 3) $runSamples[] = implode(' ', array_slice($cur, 0, 12));
    };
    /* 先切出「极大连续英文词序列」，再按长度分流：
       ≥3 → 计分段（R1）；==2 → CJK 的报备清单（R2）；==1 → 不管。 */
    $seqs = [];
    foreach (sentences($text) as $s) {
        $cur = [];
        foreach (ascii_words($s) as $w) {
            $w = mb_strtolower($w, 'UTF-8');
            if (isset($alw[$w])) { $alwUsed[$w] = $alw[$w]; }
            $isLex = isset($LEX[$w]) && !isset($ALLOW[$w]) && !isset($alw[$w]) && strlen($w) >= 2 && !preg_match('~^\d+~', $w);
            if ($isLex) {
                $cur[] = $w;
            } else {
                if ($cur) $seqs[] = $cur;
                $cur = [];
            }
        }
        if ($cur) $seqs[] = $cur;
    }
    foreach ($seqs as $seq) {
        $flushRun($seq, $runsOf, $runSamples);
        if ($isCjk && count($seq) === 2) $pairs[] = implode(' ', $seq);
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
    if ($isCjk) {
        /* CJK：词数指标不可用（整段日文/中文没有空格，会被切成一个 token），只按段计分。
           residual_count = 段内英文词（去重）；residual_tokens = 段内英文词总数；
           pairs = 连续 2 词的对，进证据文件，每条必须有人工结论（译掉 / 进 allow-<lang>）。 */
        $rw = [];
        $rt = 0;
        foreach ($runsOf as $r) {
            foreach ($r as $w) { $rw[$w] = true; $rt++; }
        }
        $pairU = array_values(array_unique($pairs));
        $rec['residual_raw'] = count($rw);
        $rec['residual_raw_tokens'] = $rt;
        $rec['residual_count'] = count($rw);
        $rec['residual_tokens'] = $rt;
        $rec['local_stop_removed'] = 0;
        $rec['long_runs'] = count($runsOf);
        $rec['long_run_words'] = $rt;
        $rec['long_run_samples'] = $runSamples;
        $rec['pairs'] = array_slice($pairU, 0, 40);
        $rec['pair_count'] = count($pairU);
        $rec['allowed_used'] = $alwUsed;
        $rec['residual_words'] = array_keys($rw);
        $rec['english_segments'] = [];
        $rec['segment_count'] = 0;
        if ($runsOf !== []) $flagged++;
    } else {
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
        $rec['allowed_used'] = $alwUsed;              // 本页用到哪些豁免词（审计用）
        $rec['residual_words'] = array_keys($kept);
        $rec['english_segments'] = array_slice($segments, 0, 4);
        $rec['segment_count'] = count($segments);
        if ($filteredCount > 5) $flagged++;
    }

    fwrite($fh, json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    if ($n % 50 === 0) {
        fwrite(STDERR, sprintf("  %d/%d  扣过本族词后>5 的页面 %d  异常 %d\n", $n, $total, $flagged, $bad));
        fflush($fh);
    }
}
fclose($fh);
fwrite(STDERR, sprintf("完成 %d 条，扣过本族词后>5 的页面 %d，异常 %d\n", $n, $flagged, $bad));
