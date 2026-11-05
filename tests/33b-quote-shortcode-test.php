<?php
/** Isolated shortcode contract test; no WordPress database or network. */
define('ABSPATH', __DIR__ . '/');
function plugin_dir_path($p) { return dirname($p) . '/'; }
function plugin_dir_url($p) { return 'http://localhost/plugin/'; }
function add_action(...$a) {} function register_activation_hook(...$a) {} function register_deactivation_hook(...$a) {}
function get_option($k,$default=null) { return $k === 'admin_email' ? 'admin@example.test' : $default; }
function wp_enqueue_style(...$a) {} function wp_enqueue_script(...$a) {} function wp_localize_script(...$a) {}
function esc_url_raw($s) { return $s; } function rest_url($s) { return 'http://localhost/wp-json/'.$s; }
function wp_create_nonce($s) { return 'test'; } function home_url($s) { return 'http://localhost'.$s; }
function shortcode_atts($defaults,$actual,$shortcode='') { return array_merge($defaults, array_intersect_key($actual,$defaults)); }
function sanitize_text_field($s) { return (string)$s; } function wp_unslash($s) { return $s; }
function sanitize_title($s) { return strtolower((string)$s); } function __($s,$domain='') { return $s; }
function esc_attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function apply_filters($name,$value,...$args) {
    if ($name === 'pkq_reference_spec') return $GLOBALS['test_spec'] ?? $value;
    if ($name === 'pkq_required_fields') return $GLOBALS['test_required'] ?? $value;
    if ($name === 'pkq_product_style') return $GLOBALS['test_resolved_style'] ?? $value;
    return $value;
}
$GLOBALS['test_scripts']=(object)['registered'=>['pkq-quote'=>(object)['ver'=>'1.0.1-33a']]];
function wp_scripts() { return $GLOBALS['test_scripts']; }
require __DIR__ . '/../packrivo-wp/packrivo-quote/packrivo-quote.php';
function check($value,$message) { if (!$value) { fwrite(STDERR,"FAIL $message\n"); exit(1); } echo "PASS $message\n"; }
$GLOBALS['test_spec']=['length'=>9.5,'width'=>9.5,'height'=>4,'material'=>'rigidboard','print'=>'none','finish'=>'none'];
$_GET=[];
$legacy=PKQ_Plugin::render_shortcode(['style'=>'rigid','qty'=>'250','product'=>'product-test']);
$expected='<div class="pkq-root" data-style="rigid" data-qty="250" data-product="product-test" data-length="9.5" data-width="9.5" data-height="4" data-material="rigidboard" data-print="none" data-finish="none"><noscript>This configurator needs JavaScript. Email hello@packrivo.com and we will quote manually.</noscript></div>';
check($legacy===$expected,'legacy shortcode output unchanged');
check(wp_scripts()->registered['pkq-quote']->ver==='1.0.1-33a','legacy script asset version unchanged');
$GLOBALS['test_spec']=[];
$guard=PKQ_Plugin::render_shortcode(['style'=>'rigid','qty'=>'250','product'=>'product-test','require_size'=>'1','require_material'=>'1']);
check(strpos($guard,'data-require-size="1"')!==false && strpos($guard,'data-require-material="1"')!==false && strpos($guard,'data-length=')===false && strpos($guard,'data-material=')===false,'shortcode flags guard missing fields');
check(wp_scripts()->registered['pkq-quote']->ver==='1.0.1-33a-33b-guard','guarded script uses distinct cache version');
check(strpos($guard,'<noscript>')!==false && strpos($guard,'dimensions and material')!==false,'guarded no-JavaScript fallback');
$_GET=['require_size'=>'1','require_material'=>'1'];
wp_scripts()->registered['pkq-quote']->ver='1.0.1-33a';
$query=PKQ_Plugin::render_shortcode(['style'=>'rigid','qty'=>'250','product'=>'product-test']);
check(strpos($query,'data-require-size="1"')!==false && strpos($query,'data-require-material="1"')!==false,'GET flags propagate to quote page');
check(wp_scripts()->registered['pkq-quote']->ver==='1.0.1-33a-33b-guard','GET guard also varies script cache version');
$_GET=['require_size'=>'0','require_material'=>'0'];
$forced=PKQ_Plugin::render_shortcode(['style'=>'rigid','qty'=>'250','product'=>'product-test','require_size'=>'1','require_material'=>'1']);
check(strpos($forced,'data-require-size="1"')!==false && strpos($forced,'data-require-material="1"')!==false,'GET cannot weaken shortcode flags');
$_GET=['require_size'=>['1'],'require_material'=>'<script>'];
$invalid=PKQ_Plugin::render_shortcode(['style'=>'rigid','qty'=>'250','product'=>'product-test']);
check(strpos($invalid,'data-require-size')===false && strpos($invalid,'data-require-material')===false,'invalid GET flags ignored safely');
$GLOBALS['test_spec']=['length'=>5,'width'=>5,'height'=>3.5,'material'=>'rigidboard'];
$_GET=[];
$known=PKQ_Plugin::render_shortcode(['style'=>'rigid','qty'=>'250','product'=>'product-test','require_size'=>'1','require_material'=>'1']);
check(strpos($known,'data-length="5"')!==false && strpos($known,'data-height="3.5"')!==false && strpos($known,'data-material="rigidboard"')!==false,'known dimensions and material remain prefilled');
$GLOBALS['test_required']=['size'=>true,'material'=>true];
wp_scripts()->registered['pkq-quote']->ver='1.0.1-33a';
$derived=PKQ_Plugin::render_shortcode(['style'=>'rigid','qty'=>'250','product'=>'product-test']);
check(strpos($derived,'data-require-size="1"')!==false && strpos($derived,'data-require-material="1"')!==false,'derived product guard protects legacy entry links');
check(wp_scripts()->registered['pkq-quote']->ver==='1.0.1-33a-33b-guard','derived guard varies script cache version');
$GLOBALS['test_required']=[]; $GLOBALS['test_resolved_style']='rigid';
$resolved=PKQ_Plugin::render_shortcode(['qty'=>'250','product'=>'product-test']);
check(strpos($resolved,'data-style="rigid"')!==false,'structured product can resolve missing style');
