<?php
/**
 * 查 nl 那 7 条术语在页面上到底出现在哪种位置：正文表格单元格 / 表单选项值 / 属性 / href / JSON-LD。
 * 用法：php nl-term-locate.php <urls.txt> <prefix> 
 */
$list = $argv[1] ?? '/tmp/nl-urls.txt';
$gw = '172.19.0.1';
$NEEDLES = ['Klep F1', 'Vulling A1', 'Versteviging P1', 'Vak P1', 'Kwaliteit F1', 'Etiketten', 'Thermogevormde tray'];
$EN = ['Flap F1', 'Pad A1', 'Patch P1', 'Pocket P1', 'Grade F1', 'Labels', 'Thermoformed tray'];

function fetch(string $u, string $g): array
{
    $ch = curl_init($u);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ["www.packrivo.com:443:$g"], CURLOPT_TIMEOUT => 40,
        CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_ENCODING => '', CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'packrivo-29y-term-locate/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return [$code, is_string($body) ? $body : ''];
}

function classify(string $html, int $pos): string
{
    // 往回找最近的 '<'，判断这个词落在标签内（属性）还是标签外（文本）
    $lt = strrpos(substr($html, 0, $pos), '<');
    $gt = strrpos(substr($html, 0, $pos), '>');
    if ($lt !== false && ($gt === false || $lt > $gt)) {
        $tag = substr($html, $lt, $pos - $lt);
        if (preg_match('~<option[^>]*$~i', $tag)) return 'OPTION 标签内（可能是选项值/显示值）';
        if (preg_match('~<input[^>]*$~i', $tag)) return 'INPUT 属性内（表单值）';
        if (preg_match('~<a[^>]*$~i', $tag)) return 'A 属性内（链接/aria）';
        if (preg_match('~<img[^>]*$~i', $tag)) return 'IMG 属性内（alt）';
        return '标签内：' . substr($tag, 0, 60);
    }
    // 文本：看最近的开标签
    $before = substr($html, max(0, $pos - 400), 400);
    if (preg_match_all('~<(td|th|li|p|h1|h2|h3|span|div|button|label|option)\b[^>]*>~i', $before, $m)) {
        $last = end($m[1]);
        return 'TEXT 文本（最近的开标签 <' . strtolower($last) . '>）';
    }
    return 'TEXT 文本（无近邻标签）';
}

$lines = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$hits = 0;
foreach ($lines as $line) {
    $p = preg_split('/\s+/', trim($line), 2);
    if (count($p) < 2) continue;
    [$lang, $url] = $p;
    if ($lang !== 'nl') continue;
    [$code, $html] = fetch($url, $gw);
    if ($code !== 200 || $html === '') continue;
    foreach ($NEEDLES as $i => $needle) {
        $off = 0;
        $found = 0;
        while (($pos = mb_strpos($html, $needle, $off)) !== false && $found < 2) {
            $found++;
            $off = $pos + 1;
            $ctx = preg_replace('~\s+~u', ' ', mb_substr($html, max(0, $pos - 90), 190));
            printf("%s | %s | 英文源 %s | %s\n    上下文: %s\n",
                $url, $needle, $EN[$i], classify($html, $pos), $ctx);
            $hits++;
        }
    }
}
echo "共命中 {$hits} 处\n";
