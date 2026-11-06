<?php
/** Expose the force option through WP-CLI without eval-file swallowing it. */
if (!defined('WP_CLI') || !WP_CLI) return;

// WordPress can refresh unrelated theme-pattern transient rows at bootstrap.
// For this CLI request only, treat timeouts as unexpired and suppress writes;
// if a data row is absent, use an empty sentinel so add_option is avoided.
$pk34a_guard_timeout = static function ($value, $option, $old_value) {
    $option=(string)$option;
    return (str_starts_with($option, '_site_transient_timeout_wp_theme_files_patterns-') || str_starts_with($option, '_site_transient_wp_theme_files_patterns-')) ? $old_value : $value;
};
$pk34a_guard_expiry = static function ($value, $option, $default) {
    $option=(string)$option;
    if ($value!==false) return $value;
    if (str_starts_with($option, '_site_transient_timeout_wp_theme_files_patterns-')) return PHP_INT_MAX;
    if (str_starts_with($option, '_site_transient_wp_theme_files_patterns-')) {
        global $wpdb;
        if (!is_object($wpdb) || empty($wpdb->options)) WP_CLI::error('Theme-cache read guard is unavailable.');
        $existing=$wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s",$option));
        if (!empty($wpdb->last_error)) WP_CLI::error('Theme-cache read guard query failed.');
        return $existing===null ? '' : $value;
    }
    return $value;
};
if (function_exists('add_filter')) {
    add_filter('pre_update_option', $pk34a_guard_timeout, PHP_INT_MAX, 3);
    add_filter('pre_option', $pk34a_guard_expiry, PHP_INT_MAX, 3);
} else {
    $GLOBALS['wp_filter']['pre_update_option'][PHP_INT_MAX][] = array('function'=>$pk34a_guard_timeout,'accepted_args'=>3);
    $GLOBALS['wp_filter']['pre_option'][PHP_INT_MAX][] = array('function'=>$pk34a_guard_expiry,'accepted_args'=>3);
}

WP_CLI::add_command('pk34a migrate', static function ($positionals, $options) {
    $args = array($positionals[0]);
    if (isset($options['dry-run'])) $args[] = 'dry-run';
    if (isset($options['force-url'])) $args[] = '--force-url=' . $options['force-url'];
    require __DIR__ . '/34a-keyword-map.php';
}, array(
    'shortdesc' => 'Apply or inspect the reviewed 34a keyword map.',
    'synopsis' => array(
        array('type'=>'positional','name'=>'map','description'=>'Path to the keyword CSV.'),
        array('type'=>'flag','name'=>'dry-run','optional'=>true,'description'=>'Plan without database writes.'),
        array('type'=>'assoc','name'=>'force-url','optional'=>true,'description'=>'Only the explicitly approved redirect path may be forced.'),
    ),
));
