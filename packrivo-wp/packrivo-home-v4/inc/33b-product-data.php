<?php
/** Validated product records and an atomic WP-CLI importer for 33b. */
if (!defined('ABSPATH')) exit;

function pk33b_product_error($message) {
    return new WP_Error('pk33b_invalid_product', $message);
}

/** A product image is a filename or a path under that product's own directory. */
function pk33b_product_image_path($slug, $file) {
    if (!is_string($slug) || !preg_match('/\Aproduct-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) || !is_string($file)) return '';
    $prefix = 'assets/img/products/' . $slug . '/';
    if (strpos($file, $prefix) === 0) $file = substr($file, strlen($prefix));
    if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:webp|png|jpe?g|avif)\z/i', $file)) return '';
    if (strpos($file, '..') !== false) return '';
    return $prefix . $file;
}

/** Validate without inventing defaults for facts; optional lists default to empty. */
function pk33b_normalize_product($slug, $input) {
    if (!is_string($slug) || !preg_match('/\Aproduct-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug)) return pk33b_product_error('Invalid product slug.');
    if (!is_array($input) || array_is_list($input) && $input !== []) return pk33b_product_error('Product data must be an object.');
    $allowed = ['h1','lede','category_hub','engine_style','engine_material','sizes','images','construction','finishes','inserts','specs','faq','related','keywords'];
    foreach ($input as $key => $_) if (!in_array($key, $allowed, true)) return pk33b_product_error('Unexpected product field: ' . $key);
    $out = [];
    foreach (['h1','lede','category_hub','engine_style','engine_material'] as $key) {
        $value = $input[$key] ?? '';
        if (!is_string($value)) return pk33b_product_error('Expected string: ' . $key);
        $out[$key] = $value;
    }
    if ($out['category_hub'] !== '' && !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $out['category_hub'])) return pk33b_product_error('Invalid category hub.');
    foreach (['sizes','images','construction','finishes','inserts','specs','faq','related'] as $key) {
        $value = $input[$key] ?? [];
        if (!is_array($value) || !array_is_list($value)) return pk33b_product_error('Expected list: ' . $key);
        $out[$key] = $value;
    }
    $pricing_path = defined('PKQ_DIR') ? PKQ_DIR . 'includes/pricing.json' : '';
    $pricing = $pricing_path && is_readable($pricing_path) ? json_decode((string)file_get_contents($pricing_path), true) : null;
    if (($out['engine_style'] !== '' || $out['engine_material'] !== '' || $out['finishes']) && !is_array($pricing)) return pk33b_product_error('Pricing tables unavailable.');
    if ($out['engine_style'] !== '' && !isset($pricing['styles'][$out['engine_style']])) return pk33b_product_error('Unknown engine style.');
    if ($out['engine_material'] !== '' && !isset($pricing['materials'][$out['engine_material']])) return pk33b_product_error('Unknown engine material.');
    foreach ($out['sizes'] as $size) {
        if (!is_array($size) || !isset($size['label'],$size['source']) || !is_string($size['label']) || trim($size['label']) === '' || !in_array($size['source'], ['supplier-catalogue','quote'], true)) return pk33b_product_error('Invalid size label or source.');
        foreach (['l_mm','w_mm','h_mm'] as $key) {
            if (!isset($size[$key]) || !(is_int($size[$key]) || is_float($size[$key])) || !is_finite((float)$size[$key]) || $size[$key] <= 0) return pk33b_product_error('Invalid size dimension.');
        }
    }
    foreach ($out['images'] as $image) {
        if (!is_array($image) || !isset($image['file'],$image['alt'],$image['caption']) || !is_string($image['file']) || !is_string($image['alt']) || !is_string($image['caption'])) return pk33b_product_error('Invalid image record.');
        if ($image['file'] !== '' && pk33b_product_image_path($slug, $image['file']) === '') return pk33b_product_error('Image path outside the product directory.');
    }
    foreach ($out['construction'] as $item) if (!is_string($item)) return pk33b_product_error('Invalid construction entry.');
    foreach ($out['finishes'] as $item) if (!is_string($item) || !isset($pricing['finishes'][$item])) return pk33b_product_error('Unknown finish.');
    $inserts = ['eva','pu','card','corrugated','molded-pulp-on-request','film-frame'];
    foreach ($out['inserts'] as $item) if (!is_string($item) || !in_array($item, $inserts, true)) return pk33b_product_error('Unknown insert.');
    foreach ($out['specs'] as &$spec) {
        if (!is_array($spec) || !isset($spec['label'],$spec['value']) || !is_string($spec['label'])) return pk33b_product_error('Invalid specification.');
        if (is_int($spec['value']) || is_float($spec['value'])) {
            if (!is_finite((float)$spec['value'])) return pk33b_product_error('Invalid specification value.');
            $spec['value'] = (string)$spec['value'];
        }
        if (!is_string($spec['value'])) return pk33b_product_error('Invalid specification value.');
        if (!isset($spec['source'])) $spec['source'] = '';
        if (!is_string($spec['source']) || !in_array($spec['source'], ['', 'confirmed-fact','supplier-catalogue','xiangfeng-quote'], true)) return pk33b_product_error('Invalid specification source.');
    }
    unset($spec);
    foreach ($out['faq'] as $faq) if (!is_array($faq) || !isset($faq['question'],$faq['answer']) || !is_string($faq['question']) || !is_string($faq['answer'])) return pk33b_product_error('Invalid FAQ entry.');
    // Existing product pages include legacy slugs without the product- prefix.
    foreach ($out['related'] as $related) if (!is_string($related) || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $related)) return pk33b_product_error('Invalid related product slug.');
    $keywords = $input['keywords'] ?? [];
    if (!is_array($keywords) || array_diff(array_keys($keywords), ['primary','secondary'])) return pk33b_product_error('Invalid keywords.');
    $primary = $keywords['primary'] ?? ''; $secondary = $keywords['secondary'] ?? [];
    if (!is_string($primary) || !is_array($secondary) || !array_is_list($secondary)) return pk33b_product_error('Invalid keywords.');
    foreach ($secondary as $keyword) if (!is_string($keyword)) return pk33b_product_error('Invalid secondary keyword.');
    $out['keywords'] = ['primary'=>$primary,'secondary'=>$secondary];
    return $out;
}

/** Empty or unsourced metadata rows are never exposed as specifications. */
function pk33b_visible_specs($product) {
    if (!is_array($product) || empty($product['specs']) || !is_array($product['specs'])) return [];
    return array_values(array_filter($product['specs'], static function ($row) {
        return is_array($row) && in_array($row['source'] ?? '', ['confirmed-fact','supplier-catalogue','xiangfeng-quote'], true)
            && is_string($row['label'] ?? null) && trim($row['label']) !== '' && is_string($row['value'] ?? null) && trim($row['value']) !== '';
    }));
}

/** Invalid/missing records leave the existing product template untouched. */
function pk33b_get_product($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish') return null;
    $raw = get_post_meta($post->ID, 'pk_product', true);
    if (!is_string($raw) || $raw === '') return null;
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) return null;
    $record = pk33b_normalize_product($post->post_name, $decoded);
    return is_wp_error($record) || trim($record['h1']) === '' ? null : $record;
}

/**
 * Import one JSON object, an array, or {"products":[...]} by published slug.
 *
 * ## EXAMPLES
 *     wp pk-product import /path/to/products.json
 */
function pk33b_cli_import($args, $assoc_args) {
    global $wpdb;
    if (count($args) !== 1 || !is_file($args[0]) || !is_readable($args[0])) WP_CLI::error('Pass one readable JSON file.');
    $decoded = json_decode((string)file_get_contents($args[0]), true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) WP_CLI::error('Invalid JSON file.');
    if (isset($decoded['products'])) $records = $decoded['products'];
    elseif (isset($decoded['slug'])) $records = [$decoded];
    else $records = $decoded;
    if (!is_array($records) || !array_is_list($records) || !$records) WP_CLI::error('Expected at least one product object.');
    $rows = []; $seen = [];
    foreach ($records as $record) {
        if (!is_array($record) || !isset($record['slug']) || !is_string($record['slug'])) WP_CLI::error('Every product needs a slug.');
        $slug = $record['slug']; unset($record['slug']);
        if (isset($seen[$slug])) WP_CLI::error('Duplicate product slug: ' . $slug);
        $seen[$slug] = true;
        $normal = pk33b_normalize_product($slug, $record);
        if (is_wp_error($normal)) WP_CLI::error($slug . ': ' . $normal->get_error_message());
        // Resolve by slug rather than URL path so nested pages are supported;
        // a duplicate slug under two parents must never select arbitrarily.
        $posts = $wpdb->get_results($wpdb->prepare("SELECT ID,post_name,post_type,post_status FROM {$wpdb->posts} WHERE post_name=%s AND post_type=%s AND post_status=%s ORDER BY ID", $slug, 'page', 'publish'), ARRAY_A);
        if (!is_array($posts) || count($posts) !== 1) WP_CLI::error('Missing or ambiguous published product slug: ' . $slug);
        $post = $posts[0];
        $meta = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id", $post['ID'], 'pk_product'), ARRAY_A);
        if (!is_array($meta) || count($meta) > 1) WP_CLI::error('Duplicate or unreadable metadata: ' . $slug);
        $encoded = wp_json_encode($normal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) WP_CLI::error('Cannot encode product: ' . $slug);
        $rows[] = ['id'=>(int)$post['ID'],'slug'=>$slug,'meta'=>$meta,'json'=>$encoded];
    }
    // The batch guarantee depends on both tables supporting transactional locks.
    foreach ([$wpdb->posts, $wpdb->postmeta] as $table) {
        $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s', $wpdb->esc_like($table)), ARRAY_A);
        if (!is_array($status) || strcasecmp((string)($status['Engine'] ?? ''), 'InnoDB') !== 0) WP_CLI::error('Product import requires transactional InnoDB tables.');
    }
    if ($wpdb->query('START TRANSACTION') === false) WP_CLI::error('Could not start transaction.');
    $updated=0; $unchanged=0; $changed_ids=[];
    foreach ($rows as $row) {
        $post = $wpdb->get_row($wpdb->prepare("SELECT post_name,post_type,post_status FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE", $row['id']), ARRAY_A);
        $meta = $wpdb->get_results($wpdb->prepare("SELECT meta_id,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id FOR UPDATE", $row['id'], 'pk_product'), ARRAY_A);
        if (!$post || $post['post_name'] !== $row['slug'] || $post['post_type'] !== 'page' || $post['post_status'] !== 'publish' || !is_array($meta) || $meta !== $row['meta']) {
            $wpdb->query('ROLLBACK'); WP_CLI::error('Product or metadata changed concurrently; rolled back.');
        }
        if ($meta && $meta[0]['meta_value'] === $row['json']) { $unchanged++; continue; }
        $result = $meta
            ? $wpdb->update($wpdb->postmeta, ['meta_value'=>$row['json']], ['meta_id'=>(int)$meta[0]['meta_id'],'post_id'=>$row['id']])
            : $wpdb->insert($wpdb->postmeta, ['post_id'=>$row['id'],'meta_key'=>'pk_product','meta_value'=>$row['json']]);
        if ($result !== 1) { $wpdb->query('ROLLBACK'); WP_CLI::error('Metadata write failed; rolled back.'); }
        $updated++; $changed_ids[] = $row['id'];
    }
    if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); WP_CLI::error('Commit failed.'); }
    foreach ($changed_ids as $id) { clean_post_cache($id); wp_cache_delete($id, 'post_meta'); }
    WP_CLI::success('updated=' . $updated . ' unchanged=' . $unchanged);
}

if (defined('WP_CLI') && WP_CLI) WP_CLI::add_command('pk-product import', 'pk33b_cli_import');
