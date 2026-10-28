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
$desc = get_post_meta($p->ID, '_packrivo_description', true);
$slug = $p->post_name;
$query = ['product'=>$slug];
if ($price !== null && $style) $query = ['style'=>$style,'qty'=>500,'product'=>$slug];
?>
<article class="pk-product-card">
  <a class="pk-product-card-main" href="<?php echo esc_url(get_permalink($p)); ?>">
    <span class="pk-product-card-image"><?php if ($cover) : ?><img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($card_title); ?>" loading="lazy" /><?php endif; ?><span class="pk-product-moq">MOQ 500</span></span>
    <span class="pk-product-card-copy"><span class="pk-product-card-group"><?php echo esc_html($args['group'] ?? ''); ?></span><span class="pk-card-title"><?php echo esc_html($card_title); ?></span><span class="pk-product-card-desc"><?php echo esc_html(wp_strip_all_tags($desc ?: $p->post_excerpt)); ?></span></span>
  </a>
  <div class="pk-product-card-footer"><span><?php if ($price !== null && $ref) : ?><strong>from $<?php echo esc_html(number_format($price,2)); ?> / unit</strong><small>at 500 pcs · <?php echo esc_html(implode('×',array_slice($ref,0,3))); ?> cm</small><?php endif; ?></span><a href="<?php echo esc_url(add_query_arg($query,home_url('/get-a-quote/'))); ?>"><?php echo $price !== null ? 'Configure →' : 'Get a price →'; ?></a></div>
</article>
