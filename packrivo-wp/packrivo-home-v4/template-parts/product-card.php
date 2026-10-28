<?php
/** Shared product card. Pass product as the `product` template argument. */
$p = $args['product'] ?? null;
if (!$p) return;
$p = get_post($p);
if (!$p) return;
$card_title = $args['title'] ?? wp_strip_all_tags($p->post_title);
$style = pk_engine_style_for($p->ID);
$price = pk_from_price($p->ID);
$ref = pk_card_reference($style);
$cover = get_the_post_thumbnail_url($p, 'medium_large') ?: get_post_meta($p->ID, '_packrivo_article_cover_url', true);
$caption = get_post_meta($p->ID, '_packrivo_card_photo_caption', true);
$thumb_id = get_post_thumbnail_id($p);
$small_id = (int) get_post_meta($p->ID, '_packrivo_card_800_id', true);
$photo_alt = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
$srcset = '';
if ($caption && $thumb_id && $small_id) {
    $large_url = wp_get_attachment_url($thumb_id);
    $small_url = wp_get_attachment_url($small_id);
    if ($large_url && $small_url) { $cover=$large_url; $srcset=$small_url.' 800w, '.$large_url.' 1200w'; }
}
$desc = get_post_meta($p->ID, '_packrivo_description', true);
$slug = $p->post_name;
$query = ['product'=>$slug];
if ($price !== null && $style) $query = ['style'=>$style,'qty'=>500,'product'=>$slug];
?>
<article class="pk-product-card">
  <a class="pk-product-card-main" href="<?php echo esc_url(get_permalink($p)); ?>">
    <span class="pk-product-card-image"><?php if ($cover) : ?><img src="<?php echo esc_url($cover); ?>" <?php if ($srcset) : ?>srcset="<?php echo esc_attr($srcset); ?>" sizes="(max-width:560px) 100vw, 25vw"<?php endif; ?> alt="<?php echo esc_attr($photo_alt ?: $card_title); ?>" loading="lazy" /><?php endif; ?><span class="pk-product-moq">MOQ 500</span></span>
    <span class="pk-product-card-copy"><span class="pk-product-card-group"><?php echo esc_html($args['group'] ?? ''); ?></span><span class="pk-card-title"><?php echo esc_html($card_title); ?></span><span class="pk-product-card-desc"><?php echo esc_html(wp_strip_all_tags($desc ?: $p->post_excerpt)); ?></span></span>
  </a>
  <?php if ($caption) : ?><small class="pk-product-photo-caption"><?php echo esc_html($caption); ?></small><?php endif; ?>
  <div class="pk-product-card-footer"><span><?php if ($price !== null && $ref) : ?><strong>from $<?php echo esc_html(number_format($price,2)); ?> / unit</strong><small>at 500 pcs · <?php echo esc_html(implode('×',array_slice($ref,0,3))); ?> cm</small><?php endif; ?></span><a href="<?php echo esc_url(add_query_arg($query,home_url('/get-a-quote/'))); ?>"><?php echo $price !== null ? 'Configure →' : 'Get a price →'; ?></a></div>
</article>
