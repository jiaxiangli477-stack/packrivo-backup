<?php
define('ABSPATH', '/');
function add_action($hook, $fn, $priority = 10) {}
function add_filter($hook, $fn, $priority = 10) {}
function packrivo_lang() { return null; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function home_url($path = '/') { return 'https://www.packrivo.com' . $path; }
require '/theme/inc/seo-31a.php';
$cases = [
    '<a href="/catalog-Box-ABC/?x=KeepCase">x</a>' => '<a href="/catalog-box-abc/?x=KeepCase">x</a>',
    '<a data-href="/catalog-Box-ABC/">x</a>' => '<a data-href="/catalog-Box-ABC/">x</a>',
    '<a href="https://example.com/catalog-Box-ABC/">x</a>' => '<a href="https://example.com/catalog-Box-ABC/">x</a>',
    '<a href="https://www.packrivo.com/catalog-Box-ABC/">x</a>' => '<a href="https://www.packrivo.com/catalog-box-abc/">x</a>',
];
$failed = 0;
foreach ($cases as $input => $expected) {
    $actual = pkv4_31a_catalog_links($input);
    if ($actual !== $expected) { echo "FAIL ", $input, " => ", $actual, "\n"; ++$failed; }
}
if (!$failed) echo "PASS catalogue anchor normalization and boundaries\n";
exit($failed ? 1 : 0);
