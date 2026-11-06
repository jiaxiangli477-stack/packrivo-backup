<?php
define('ABSPATH', '/');
$GLOBALS['hooks'] = [];
function add_action($hook, $fn, $priority = 10) { $GLOBALS['hooks'][$hook][$priority][] = $fn; }
function add_filter($hook, $fn, $priority = 10) {}
function packrivo_lang() { return 'zh-CN'; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function home_url($path = '/') { return 'http://localhost:8081' . $path; }
function wp_safe_redirect($url, $status) { throw new RuntimeException('unexpected redirect: ' . $url); }
require '/theme/inc/seo-31a.php';

$html = '<a href="/catalog-Box-ABC/">existing</a>';
$failed = false;
if (pkv4_31a_catalog_links($html) !== $html) { echo "FAIL translated content href changed\n"; $failed = true; }
if (pkv4_31a_catalog_url('/catalog-Box-ABC/') !== '/catalog-Box-ABC/') { echo "FAIL translated page link changed\n"; $failed = true; }
$_SERVER['REQUEST_URI'] = '/ABOUT/'; // The existing language router rewrites the original language path.
try { $GLOBALS['hooks']['template_redirect'][-1][0](); }
catch (RuntimeException $e) { echo "FAIL translated route redirected\n"; $failed = true; }
if (!$failed) echo "PASS translated content and rewritten route unchanged\n";
exit($failed ? 1 : 0);
