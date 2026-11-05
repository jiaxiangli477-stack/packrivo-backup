<?php
/** Activate the new product template only for valid English product records. */
if (!defined('ABSPATH')) exit;

function pk33b_current_product() {
    if (is_admin() || !is_page() || (function_exists('packrivo_lang') && packrivo_lang() !== null)) return null;
    $id=(int)get_queried_object_id();
    $post=$id ? get_post($id) : null;
    if (!$post || strpos($post->post_name, 'product-') !== 0 || !function_exists('pk33b_get_product')) return null;
    return pk33b_get_product($id);
}

add_filter('template_include', static function ($template) {
    if (!pk33b_current_product()) return $template;
    $candidate=get_stylesheet_directory().'/template-product-33b.php';
    return is_readable($candidate) ? $candidate : $template;
}, 99);

add_action('wp_enqueue_scripts', static function () {
    if (!pk33b_current_product()) return;
    $base=get_stylesheet_directory_uri().'/assets/';
    wp_enqueue_style('pk33b-product', $base.'33b-product.css', ['pk33a-site'], '33b.1');
    wp_enqueue_script('pk33b-gallery', $base.'33b-product.js', [], '33b.1', true);
}, 30);

/** Reuse Yoast's single BreadcrumbList and make it match the visible trail. */
add_filter('wpseo_breadcrumb_links', static function ($crumbs) {
    $product=pk33b_current_product();
    if (!$product) return $crumbs;
    $items=[['url'=>home_url('/'),'text'=>'Home']];
    $hub=!empty($product['category_hub']) ? get_page_by_path($product['category_hub'], OBJECT, 'page') : null;
    if ($hub && $hub->post_status === 'publish') $items[]=['url'=>get_permalink($hub),'text'=>wp_strip_all_tags(get_the_title($hub))];
    $items[]=['url'=>get_permalink(get_queried_object_id()),'text'=>wp_strip_all_tags($product['h1'])];
    return $items;
}, 100);
