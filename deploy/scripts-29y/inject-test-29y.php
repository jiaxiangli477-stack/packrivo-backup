<?php
/**
 * 29y 负向测试用：往某页正文里注入一句「本族语 + 夹一段英文」，测完再撤掉。
 *   wp eval-file inject-test-29y.php inject <POST_ID> "<句子>"
 *   wp eval-file inject-test-29y.php revert <POST_ID>
 * 注入块带 class="pkq29y-test"，revert 只删这一块，页面其它内容不动。
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "must run under wp\n"); exit(1); }

$args = $args ?? array();
$action = $args[0] ?? '';
$id = isset($args[1]) ? (int) $args[1] : 0;
if (!$id) { fwrite(STDERR, "need post id\n"); exit(1); }

$post = get_post($id);
if (!$post) { fwrite(STDERR, "post $id not found\n"); exit(1); }
$c = $post->post_content;
$has = strpos($c, 'pkq29y-test') !== false;

if ($action === 'inject') {
    $sentence = $args[2] ?? '';
    if ($sentence === '') { fwrite(STDERR, "need sentence\n"); exit(1); }
    if ($has) { echo "SKIP 已有注入块\n"; exit(0); }
    $block = '<p class="pkq29y-test">' . $sentence . '</p>';
    $new = (strpos($c, '</main>') !== false)
        ? str_replace('</main>', $block . '</main>', $c)
        : $c . $block;
    $r = wp_update_post(array('ID' => $id, 'post_content' => $new), true);
    if (is_wp_error($r)) { fwrite(STDERR, "update failed: " . $r->get_error_message() . "\n"); exit(1); }
    printf("INJECTED 长度 %d -> %d：%s\n", strlen($c), strlen(get_post($id)->post_content), $sentence);
    exit(0);
}

if ($action === 'revert') {
    if (!$has) { echo "SKIP 没有注入块\n"; exit(0); }
    $new = preg_replace('~<p class="pkq29y-test">.*?</p>~s', '', $c, 1);
    $r = wp_update_post(array('ID' => $id, 'post_content' => $new), true);
    if (is_wp_error($r)) { fwrite(STDERR, "update failed: " . $r->get_error_message() . "\n"); exit(1); }
    $after = get_post($id)->post_content;
    printf("REVERTED 长度 %d，仍有注入块: %s\n", strlen($after),
        strpos($after, 'pkq29y-test') !== false ? 'YES' : 'NO');
    exit(0);
}

fwrite(STDERR, "unknown action $action\n");
exit(1);
