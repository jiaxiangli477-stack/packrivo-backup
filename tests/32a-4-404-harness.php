<?php
define('ABSPATH','/');
function wp_get_upload_dir(){return ['basedir'=>getenv('TEST_UPLOAD_BASE')];}
function trailingslashit($s){return rtrim($s,'/\\').'/';}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$_SERVER['REQUEST_URI']='/de/unknown-32a4/';
http_response_code(404);
require __DIR__.'/../deploy/32a/mu-plugins/001-packrivo-32a-404-title.php';
echo '<!DOCTYPE html><title>404 — page not translated</title><p>unchanged</p>';
