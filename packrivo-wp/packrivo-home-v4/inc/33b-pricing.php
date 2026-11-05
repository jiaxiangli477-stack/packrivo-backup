<?php
/** Product-specific, source-bound inputs to the existing quote engine. */
if (!defined('ABSPATH')) exit;

/** Return null when exact dimensions cannot be represented in both engines. */
function pk33b_dimensions($product, $size_index = 0) {
    if (!is_array($product) || empty($product['sizes']) || !is_array($product['sizes'])) return null;
    if (!is_int($size_index) || !isset($product['sizes'][$size_index])) return null;
    $size = $product['sizes'][$size_index];
    if (!is_array($size) || !in_array($size['source'] ?? '', ['supplier-catalogue','quote'], true)) return null;
    $dims = [];
    foreach (['l_mm','w_mm','h_mm'] as $key) {
        $mm = $size[$key] ?? null;
        if (!(is_int($mm) || is_float($mm)) || !is_finite((float)$mm)) return null;
        $cm = (float)$mm / 10;
        // The quote widget rounds to 0.1cm and requires at least 2cm on every
        // axis. Never silently price a different rounded or clamped size.
        if ($cm < 2 || $cm > 120 || abs($cm - round($cm,1)) > 0.000001) return null;
        $dims[] = $cm;
    }
    return ['length'=>$dims[0],'width'=>$dims[1],'height'=>$dims[2]];
}

function pk33b_price_input($product, $size_index = 0) {
    if (!is_array($product) || empty($product['engine_style']) || empty($product['engine_material'])) return null;
    $dims=pk33b_dimensions($product, $size_index);
    if (!$dims) return null;
    return array_merge(['style'=>$product['engine_style'],'material'=>$product['engine_material'],
        'print'=>'none','finish'=>'none','rush'=>false], $dims);
}

/** Compute every displayed number by the same server-side engine. */
function pk33b_price_rows($product, $size_index = 0) {
    if (!class_exists('PKQ_Pricing') || !class_exists('PKQ_Plugin')) return [];
    $input = pk33b_price_input($product, $size_index);
    if (!$input) return [];
    $settings = PKQ_Plugin::settings(); $rows=[];
    foreach (PKQ_Pricing::ladder($input, $settings) as $tier) {
        $q=(int)($tier['quantity'] ?? 0);
        if ($q < 250 || $q > 25000) continue;
        $result=PKQ_Pricing::price(array_merge($input, ['quantity'=>$q]), $settings);
        if (is_wp_error($result)) return [];
        $rows[$q]=$result;
    }
    return $rows;
}

/** Fee-inclusive finish change for the same reference size and quantity. */
function pk33b_finish_delta($product, $finish, $size_index = 0, $quantity = 1000) {
    if (!class_exists('PKQ_Pricing') || !class_exists('PKQ_Plugin') || !in_array($finish, $product['finishes'] ?? [], true)) return null;
    $input=pk33b_price_input($product,$size_index);
    if (!$input) return null;
    $base=PKQ_Pricing::price(array_merge($input,['quantity'=>$quantity]),PKQ_Plugin::settings());
    $selected=PKQ_Pricing::price(array_merge($input,['quantity'=>$quantity,'finish'=>$finish]),PKQ_Plugin::settings());
    if (is_wp_error($base) || is_wp_error($selected)) return null;
    return round((float)$selected['unit']-(float)$base['unit'],2);
}

/** Keep 33a cards and sorting aligned with a structured product's real inputs. */
function pk33b_card_price($post_id, $quantity = 250) {
    $product=function_exists('pk33b_get_product') ? pk33b_get_product($post_id) : null;
    if (!$product || (function_exists('packrivo_lang') && packrivo_lang() !== null)) return pk_from_price($post_id,$quantity);
    $input=pk33b_price_input($product,0);
    if (!$input || !class_exists('PKQ_Pricing') || !class_exists('PKQ_Plugin')) return null;
    $settings=PKQ_Plugin::settings();
    if (strtoupper((string)($settings['currency'] ?? 'USD')) !== 'USD') return null;
    $result=PKQ_Pricing::price(array_merge($input,['quantity'=>(int)$quantity]),$settings);
    return is_wp_error($result) ? null : (float)$result['unit'];
}

/** Resolve a missing style only when an English structured product supplies it. */
function pk33b_quote_product_style($style, $slug) {
    if ($style !== '' || (function_exists('packrivo_lang') && packrivo_lang() !== null)) return $style;
    if (!function_exists('pk33b_get_product') || !is_string($slug) || $slug === '') return $style;
    $posts=get_posts(['post_type'=>'page','post_status'=>'publish','name'=>$slug,'numberposts'=>2]);
    if (count($posts)!==1 || $posts[0]->post_name!==$slug) return $style;
    $product=pk33b_get_product($posts[0]->ID);
    return $product && !empty($product['engine_style']) ? $product['engine_style'] : $style;
}
add_filter('pkq_product_style', 'pk33b_quote_product_style', 20, 2);

/** Apply only to a valid structured product, leaving every legacy preset intact. */
function pk33b_quote_reference_spec($spec, $slug, $style) {
    if (function_exists('packrivo_lang') && packrivo_lang() !== null) return $spec;
    if (!function_exists('pk33b_get_product') || !is_string($slug) || $slug === '') return $spec;
    $posts=get_posts(['post_type'=>'page','post_status'=>'publish','name'=>$slug,'numberposts'=>2]);
    $post=count($posts) === 1 && $posts[0]->post_name === $slug ? $posts[0] : null;
    $product=$post ? pk33b_get_product($post->ID) : null;
    if (!$product) return $spec;
    if ($style !== '' && $style !== $product['engine_style']) return ['print'=>'none','finish'=>'none'];
    $raw=$_GET['size'] ?? '0';
    $index=is_scalar($raw) && preg_match('/\A\d{1,3}\z/', (string)$raw) ? (int)$raw : 0;
    $dims=pk33b_dimensions($product,$index);
    // Return only confirmed fields; never fall back to the generic 33a size for
    // a structured product whose actual dimensions are still unknown.
    $out=['print'=>'none','finish'=>'none'];
    if ($dims) $out=array_merge($out,$dims);
    if (!empty($product['engine_material'])) $out['material']=$product['engine_material'];
    return $out;
}
add_filter('pkq_reference_spec', 'pk33b_quote_reference_spec', 20, 3);

/** Guard old catalogue entry points too, not only the new template's forms. */
function pk33b_quote_required_fields($required, $slug, $style, $spec) {
    if (!is_array($required)) $required=[];
    if (function_exists('packrivo_lang') && packrivo_lang() !== null) return $required;
    if (!function_exists('pk33b_get_product') || !is_string($slug) || $slug === '') return $required;
    $posts=get_posts(['post_type'=>'page','post_status'=>'publish','name'=>$slug,'numberposts'=>2]);
    if (count($posts)!==1 || $posts[0]->post_name!==$slug) return $required;
    $product=pk33b_get_product($posts[0]->ID);
    if (!$product) return $required;
    // All structured products use the guarded widget. Known fields arrive as
    // prefilled data and remain immediately quotable; the guard prevents a
    // click on the already-selected style from replacing them with defaults.
    // If a different style is chosen the widget clears both fields instead.
    $required['size']=true;
    $required['material']=true;
    return $required;
}
add_filter('pkq_required_fields', 'pk33b_quote_required_fields', 20, 4);
