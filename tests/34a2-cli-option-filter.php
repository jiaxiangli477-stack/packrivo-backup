<?php
/** Verify the bootstrap guard is narrowly scoped and preserves other options. */
define('WP_CLI',true);
class WP_CLI { public static function add_command($name,$callback,$options=array()) {} }
require dirname(__DIR__).'/deploy/34a-cli.php';
$callback=$GLOBALS['wp_filter']['pre_update_option'][PHP_INT_MAX][0]['function'];
if ($callback('new','_site_transient_timeout_wp_theme_files_patterns-example','old')!=='old') throw new RuntimeException('Theme timeout not guarded.');
if ($callback('new','some_other_option','old')!=='new') throw new RuntimeException('Unrelated option was blocked.');
$read=$GLOBALS['wp_filter']['pre_option'][PHP_INT_MAX][0]['function'] ?? null;
if (!is_callable($read) || $read(false,'_site_transient_timeout_wp_theme_files_patterns-example',false)!==PHP_INT_MAX) throw new RuntimeException('Expired theme timeout not protected.');
if ($read(false,'some_other_option',false)!==false) throw new RuntimeException('Unrelated option read was changed.');
if ($callback('new','_site_transient_wp_theme_files_patterns-example','old')!=='old') throw new RuntimeException('Theme pattern data update not guarded.');
class FakeWpdb {
    public $options='wp_options';public $last_error='';public $value=null;
    public function prepare($sql,$arg) { return $sql; }
    public function get_var($query) { return $this->value; }
}
$wpdb=new FakeWpdb();
if ($read(false,'_site_transient_wp_theme_files_patterns-example',false)!=='') throw new RuntimeException('Missing theme cache not short-circuited.');
$wpdb->value='existing serialized cache';
if ($read(false,'_site_transient_wp_theme_files_patterns-example',false)!==false) throw new RuntimeException('Existing theme cache read was changed.');
echo "CLI_OPTION_FILTER_CASES=7 PASS\n";
