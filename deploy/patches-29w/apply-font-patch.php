<?php
/**
 * 29w 字体路径补丁的 PHP 兜底。
 *
 * 为什么需要它：`patch` 不是每个 php 容器里都有（本机演练用的镜像就没有，
 * 跑 `patch -p1` 会直接 `patch: not found`）。补丁本体只有一行替换，
 * 这里用 PHP 做同一件事，并把结果哈希对齐到 RUNBOOK §6.5 锁定的值。
 *
 * 用法（容器内）:
 *   php /tmp/apply-font-patch.php            # 就地改
 *   MU=/path/to/packrivo-wave2.php php /tmp/apply-font-patch.php
 *
 * 幂等：已经改过就打印 already applied 并退出 0；锚点找不到或不唯一则退出 1。
 */

$mu  = getenv('MU') ?: '/var/www/html/www.packrivo.com/wordpress/wp-content/mu-plugins/packrivo-wave2.php';
$to  = "define('PACKRIVO_W2_FONT_URL', get_template_directory_uri() . '/packrivo-static/assets/packrivo-inter-latin-v3.woff2');";
$from = "define('PACKRIVO_W2_FONT_URL', get_stylesheet_directory_uri() . '/packrivo-static/assets/packrivo-inter-latin-v3.woff2');";

$want = '73d43ba0262a9d74249b19a46380bc5c338f94bd9e0776baa46eddf074c9f259';

if (!is_file($mu)) {
    fwrite(STDERR, "找不到 $mu\n");
    exit(1);
}

$src = file_get_contents($mu);
if ($src === false) {
    fwrite(STDERR, "读不了 $mu\n");
    exit(1);
}

if (strpos($src, $to) !== false) {
    echo "already applied\n";
} else {
    $n = substr_count($src, $from);
    if ($n !== 1) {
        fwrite(STDERR, "锚点出现 $n 次（应为 1），不动手\n");
        exit(1);
    }
    if (file_put_contents($mu, str_replace($from, $to, $src)) === false) {
        fwrite(STDERR, "写不了 $mu\n");
        exit(1);
    }
    echo "applied\n";
}

$got = hash_file('sha256', $mu);
echo "sha256=$got\n";
echo $got === $want ? "OK 与 §6.5 锁定值一致\n" : "注意：与本手册锁定的 $want 不一致（版本可能不同，先核对再上）\n";
