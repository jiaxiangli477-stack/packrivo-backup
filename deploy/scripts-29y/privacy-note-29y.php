<?php
/**
 * 29y 隐私页：正文顶部加本语种说明 + 回滚。用法：
 *   wp eval-file privacy-note-29y.php probe  <ID>
 *   wp eval-file privacy-note-29y.php apply  <ID>
 *   wp eval-file privacy-note-29y.php revert <ID>
 * 说明句以英文原文入库（i18n mu-plugin 按整串替换成各语种，
 * 译文在 i18n/delta/<lang>-add.json 的 text 桶里）。
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "must run under wp\n"); exit(1); }

const NOTE_EN = 'This privacy policy is provided in English only. The English version is the authoritative one.';
const NOTE_HTML = '<p class="privacy-lang-note">' . NOTE_EN . '</p>';
const ANCHOR = '<main id="main">';

$args = $args ?? array();
$action = $args[0] ?? 'probe';
$id = isset($args[1]) ? (int) $args[1] : 0;
if (!$id) { fwrite(STDERR, "need post id\n"); exit(1); }

$post = get_post($id);
if (!$post) { fwrite(STDERR, "post $id not found\n"); exit(1); }
$c = $post->post_content;

$has_note = strpos($c, 'privacy-lang-note') !== false;
$anchor_pos = strpos($c, ANCHOR);
$main_pos = strpos($c, '<main');

printf("post %d slug=%s type=%s 状态=%s 长度=%d\n", $id, $post->post_name, $post->post_type, $post->post_status, strlen($c));
printf("已有说明块: %s\n", $has_note ? 'YES' : 'NO');
printf("锚点 %s 位置: %s\n", ANCHOR, $anchor_pos === false ? '未找到' : (string) $anchor_pos);
printf("第一个 <main 位置: %s\n", $main_pos === false ? '未找到' : (string) $main_pos);

if ($action === 'probe') {
    if ($main_pos !== false) {
        echo "--- <main> 起 260 字符 ---\n" . substr($c, $main_pos, 260) . "\n";
    }
    exit(0);
}

if ($action === 'apply') {
    if ($has_note) { echo "SKIP 已经有说明块\n"; exit(0); }
    $pos = $anchor_pos !== false ? $anchor_pos + strlen(ANCHOR) : ($main_pos !== false ? $main_pos : null);
    if ($pos === null) {
        // 既没有 <main id="main"> 也没有 <main>，退到 <body ...> 之后
        if (preg_match('/<body[^>]*>/i', $c, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
        } else {
            $pos = 0;
        }
    }
    $new = substr($c, 0, $pos) . NOTE_HTML . substr($c, $pos);
    $r = wp_update_post(array('ID' => $id, 'post_content' => $new), true);
    if (is_wp_error($r)) { fwrite(STDERR, "update failed: " . $r->get_error_message() . "\n"); exit(1); }
    $after = get_post($id)->post_content;
    printf("插入位置 %d，写后长度 %d，包含说明块: %s\n", $pos, strlen($after),
        strpos($after, 'privacy-lang-note') !== false ? 'YES' : 'NO');
    exit(0);
}

if ($action === 'revert') {
    if (!$has_note) { echo "SKIP 没有说明块\n"; exit(0); }
    $new = preg_replace('/<p class="privacy-lang-note">.*?<\/p>/s', '', $c, 1);
    $r = wp_update_post(array('ID' => $id, 'post_content' => $new), true);
    if (is_wp_error($r)) { fwrite(STDERR, "update failed: " . $r->get_error_message() . "\n"); exit(1); }
    $after = get_post($id)->post_content;
    printf("回滚后长度 %d，仍有说明块: %s\n", strlen($after),
        strpos($after, 'privacy-lang-note') !== false ? 'YES' : 'NO');
    exit(0);
}

fwrite(STDERR, "unknown action $action\n");
exit(1);
