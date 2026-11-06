<?php
/** Run only in the explicitly selected WP environment: wp eval-file default-category.php */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(1);
$id=(int)get_option('default_category');
$term=get_term($id,'category');
if (!$term || is_wp_error($term)) WP_CLI::error('Default category missing.');
$existing=get_term_by('slug','guides','category');
if ($existing && (int)$existing->term_id!==$id) WP_CLI::error('guides slug belongs to another category; no changes made.');
$result=wp_update_term($id,'category',['name'=>'Guides','slug'=>'guides']);
if (is_wp_error($result)) WP_CLI::error($result->get_error_message());
WP_CLI::success('Default category renamed to Guides (ID '.$id.').');
