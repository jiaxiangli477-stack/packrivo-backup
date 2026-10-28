<?php
/**
 * 合并生产字典与本地字典，生成上线用的字典目录。
 *
 * 规则（见 deploy/RUNBOOK-launch.md 第 2 步）：
 *   - 以当天新读的生产字典为底；
 *   - 同一个键两边都有值时，生产值优先，并记进 conflicts.json 供人工过目；
 *   - 生产没有、本地有的键，补进去（这一轮的 27g / 27i / 28k / 28m / 28n 内容都在本地）；
 *   - paths.json 取并集，并确保必填路径在里面。
 *
 * 用法：
 *   php merge-i18n.php --prod=/path/prod --local=/path/deploy/i18n-merged --out=/path/out \
 *       [--require=/get-a-quote/,/products/,...]
 *   php merge-i18n.php --self-test      # 用临时目录造两份字典验证三种情况
 */

const KINDS = ['title', 'attr', 'block', 'text'];

function arg(string $name, ?string $default = null): ?string
{
    foreach ($_SERVER['argv'] as $a) {
        if (str_starts_with($a, "--{$name}=")) {
            return substr($a, strlen($name) + 3);
        }
    }
    return $default;
}

function read_dict(string $file): array
{
    if (!is_file($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        $raw = substr($raw, 3);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fwrite(STDERR, "不是合法 JSON: {$file}\n");
        exit(2);
    }
    return $data;
}

function norm_value($v): string
{
    return trim(preg_replace('/\s+/u', ' ', (string) $v));
}

/**
 * @return array{0: array, 1: array} 合并后的字典、冲突列表
 */
function merge_dict(array $prod, array $local): array
{
    $out = [];
    $conflicts = [];
    foreach (KINDS as $kind) {
        $p = isset($prod[$kind]) && is_array($prod[$kind]) ? $prod[$kind] : [];
        $l = isset($local[$kind]) && is_array($local[$kind]) ? $local[$kind] : [];
        $merged = $l;                       // 本地先落地（本轮新内容）
        foreach ($p as $key => $pval) {
            if (array_key_exists($key, $l)) {
                if (norm_value($l[$key]) !== norm_value($pval)) {
                    $conflicts[] = [
                        'kind'   => $kind,
                        'key'    => $key,
                        'prod'   => $pval,
                        'local'  => $l[$key],
                        'used'   => $pval,       // 生产优先
                    ];
                }
                $merged[$key] = $pval;           // 生产值优先
            } else {
                $merged[$key] = $pval;           // 生产独有的键也保留
            }
        }
        ksort($merged, SORT_STRING);
        $out[$kind] = $merged;
    }
    return [$out, $conflicts];
}

function encode(array $data): string
{
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
    $json = json_encode($data, $flags);
    if ($json === false) {
        fwrite(STDERR, '写不出 JSON: ' . json_last_error_msg() . "\n");
        exit(2);
    }
    return $json . "\n";
}

function run(string $prodDir, string $localDir, string $outDir, array $require): void
{
    if (!is_dir($localDir)) {
        fwrite(STDERR, "本地字典目录不存在: {$localDir}\n");
        exit(2);
    }
    if (!is_dir($outDir) && !mkdir($outDir, 0775, true)) {
        fwrite(STDERR, "建不了输出目录: {$outDir}\n");
        exit(2);
    }
    $langs = [];
    foreach (glob($localDir . '/*-dict.json') ?: [] as $f) {
        $langs[] = basename($f, '-dict.json');
    }
    sort($langs);
    if (!$langs) {
        fwrite(STDERR, "本地目录里没有 *-dict.json: {$localDir}\n");
        exit(2);
    }

    $allConflicts = [];
    foreach ($langs as $lang) {
        $prodFile = $prodDir . '/' . $lang . '-dict.json';
        $localFile = $localDir . '/' . $lang . '-dict.json';
        [$merged, $conflicts] = merge_dict(read_dict($prodFile), read_dict($localFile));
        file_put_contents($outDir . '/' . $lang . '-dict.json', encode($merged));
        $counts = [];
        foreach (KINDS as $k) {
            $counts[] = $k . '=' . count($merged[$k]);
        }
        printf(
            "%-6s 生产%s → 合并后 %s，冲突 %d\n",
            $lang,
            is_file($prodFile) ? '有' : '无',
            implode(' ', $counts),
            count($conflicts)
        );
        foreach ($conflicts as $c) {
            $allConflicts[] = ['lang' => $lang] + $c;
        }
    }

    file_put_contents($outDir . '/conflicts.json', encode($allConflicts));

    // paths.json：并集 + 必填
    $prodPaths = read_dict($prodDir . '/paths.json');
    $localPaths = read_dict($localDir . '/paths.json');
    $paths = [];
    foreach ([$prodPaths, $localPaths] as $src) {
        foreach ($src as $p) {
            if (!is_string($p) || $p === '') {
                continue;
            }
            if (!str_starts_with($p, '/')) {
                $p = '/' . $p;
            }
            if ($p !== '/' && !str_ends_with($p, '/')) {
                $p .= '/';
            }
            $paths[$p] = true;
        }
    }
    $added = [];
    foreach ($require as $p) {
        if ($p === '') {
            continue;
        }
        if (!str_starts_with($p, '/')) {
            $p = '/' . $p;
        }
        if ($p !== '/' && !str_ends_with($p, '/')) {
            $p .= '/';
        }
        if (!isset($paths[$p])) {
            $added[] = $p;
            $paths[$p] = true;
        }
    }
    $final = array_keys($paths);
    sort($final);
    file_put_contents($outDir . '/paths.json', encode($final));

    printf(
        "paths.json：生产 %d + 本地 %d → 合并 %d（本轮补 %d：%s）\n",
        count($prodPaths),
        count($localPaths),
        count($final),
        count($added),
        $added ? implode(' ', $added) : '无'
    );
    printf("冲突清单：%d 条 → %s/conflicts.json\n", count($allConflicts), $outDir);
    if ($allConflicts) {
        echo "  有冲突，装之前必须人工看过 conflicts.json（生产值已被采用）。\n";
    }
}

function self_test(): void
{
    $root = sys_get_temp_dir() . '/merge-i18n-selftest-' . getmypid();
    foreach (['prod', 'local', 'out'] as $d) {
        @mkdir($root . '/' . $d, 0775, true);
    }
    $prod = [
        'title' => ['Same' => 'gleich', 'OnlyProd' => 'nur-prod', 'Diff' => 'PROD'],
        'text'  => ['A' => 'a-prod'],
    ];
    $local = [
        'title' => ['Same' => 'gleich', 'Diff' => 'LOCAL', 'OnlyLocal' => 'nur-lokal'],
        'text'  => ['A' => 'a-local', 'B' => 'b-local'],
    ];
    file_put_contents($root . '/prod/de-dict.json', encode($prod));
    file_put_contents($root . '/local/de-dict.json', encode($local));
    file_put_contents($root . '/prod/paths.json', encode(['/', '/products/']));
    file_put_contents($root . '/local/paths.json', encode(['/', '/about/']));

    run($root . '/prod', $root . '/local', $root . '/out', ['/get-a-quote/']);

    $merged = read_dict($root . '/out/de-dict.json');
    $conf = read_dict($root . '/out/conflicts.json');
    $paths = read_dict($root . '/out/paths.json');
    $ok = true;
    $check = function (string $label, bool $cond) use (&$ok) {
        printf("  %s %s\n", $cond ? 'OK  ' : 'FAIL', $label);
        $ok = $ok && $cond;
    };
    $check('生产值优先（Diff=PROD）', ($merged['title']['Diff'] ?? '') === 'PROD');
    $check('生产值优先（text A）', ($merged['text']['A'] ?? '') === 'a-prod');
    $check('本地独有键保留（OnlyLocal）', ($merged['title']['OnlyLocal'] ?? '') === 'nur-lokal');
    $check('生产独有键保留（OnlyProd）', ($merged['title']['OnlyProd'] ?? '') === 'nur-prod');
    $check('两边相同不记冲突（Same）', !array_filter($conf, fn($c) => $c['key'] === 'Same'));
    $check('两个真冲突都记下来', count($conf) === 2);
    $check('paths 取并集', $paths === ['/', '/about/', '/get-a-quote/', '/products/']);
    echo $ok ? "自检通过\n" : "自检失败\n";
    exit($ok ? 0 : 1);
}

if (in_array('--self-test', $_SERVER['argv'], true)) {
    self_test();
}

$prodDir = arg('prod');
$localDir = arg('local');
$outDir = arg('out');
if (!$prodDir || !$localDir || !$outDir) {
    fwrite(STDERR, "用法: php merge-i18n.php --prod=DIR --local=DIR --out=DIR [--require=/a/,/b/]\n");
    exit(64);
}
$require = array_filter(explode(',', (string) arg('require', '')));
run($prodDir, $localDir, $outDir, $require);
