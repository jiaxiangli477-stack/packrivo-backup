<?php
// Run with wp eval-file after backing up the database. Safe to repeat.
$settings = get_option('pkq_settings', null);
if (!is_array($settings)) {
    WP_CLI::error('pkq_settings is missing or not an array; no options changed.');
}
$titles = get_option('wpseo_titles', null);
if (!is_array($titles)) {
    WP_CLI::error('wpseo_titles is missing or not an array; no options changed.');
}
$before = $settings;
$settings['min_qty'] = 250;
if (!array_key_exists('fee_percent', $settings)) $settings['fee_percent'] = 15;
if (!array_key_exists('min_fee', $settings)) $settings['min_fee'] = 150;
if ($settings !== $before) update_option('pkq_settings', $settings);
$old_title = $titles['title-404-wpseo'] ?? null;
if ($old_title !== 'Page not found | Packrivo') {
    $titles['title-404-wpseo'] = 'Page not found | Packrivo';
    update_option('wpseo_titles', $titles);
}
WP_CLI::line('pkq_settings: ' . ($settings === $before ? 'unchanged' : 'updated'));
WP_CLI::line('title-404-wpseo: ' . ($old_title === 'Page not found | Packrivo' ? 'unchanged' : 'updated'));
