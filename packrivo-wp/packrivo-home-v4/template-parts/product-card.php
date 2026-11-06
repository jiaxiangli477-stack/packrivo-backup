<?php
/** Shared product card. Pass product as the `product` template argument. */
$p = $args['product'] ?? null;
if (!$p) return;
$p = get_post($p);
if (!$p) return;
$card_title = $args['title'] ?? wp_strip_all_tags($p->post_title);
$style = pk_engine_style_for($p->ID);
$card_qty = 250;
$price = pk_from_price($p->ID, $card_qty);
$ref = pk_card_reference($style);
$cover = get_the_post_thumbnail_url($p, 'medium_large') ?: get_post_meta($p->ID, '_packrivo_article_cover_url', true);
$caption = get_post_meta($p->ID, '_packrivo_card_photo_caption', true);
$thumb_id = get_post_thumbnail_id($p);
$small_id = (int) get_post_meta($p->ID, '_packrivo_card_800_id', true);
$photo_alt = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
if ($caption && $thumb_id && $small_id) {
    $large_url = wp_get_attachment_url($thumb_id);
    if ($large_url) { $cover=$large_url; }
}
// 卡片图统一走 -960 / -480 两档：3 倍屏手机也只能拿到 960，不会选中 1536。
// 只认 -1536.webp 结尾的才派生两档；其它文件名（-1200 等）保持原 src、不加 srcset，避免派生出 404。
$card_src = $cover;
$card_srcset = '';
if ($cover && preg_match('~^(.*)-1536\.webp$~', $cover, $cm)) {
    $base = $cm[1];
    $u480 = $base . '-480.webp';
    $u960 = $base . '-960.webp';
    $card_src = $u960;
    $card_srcset = $u480 . ' 480w, ' . $u960 . ' 960w';
}
// 显示框是 CSS 的 aspect-ratio:4/3 + object-fit:cover，width/height 按 4:3 写，防 CLS。
$card_sizes = '(max-width:560px) calc(100vw - 48px), (max-width:900px) calc(50vw - 32px), (max-width:1100px) calc(33vw - 30px), 300px';
$desc = get_post_meta($p->ID, '_packrivo_description', true);
$slug = $p->post_name;
$query = ['product'=>$slug];
if ($price !== null && $style) $query = ['style'=>$style,'qty'=>$card_qty,'product'=>$slug];
// 卡片上的价格行是拼出来的（数字每次都变），输出缓冲按整串匹配替换不到，这里主动过词典。
$pkv4_t = static function ($s) {
    return function_exists('pkv4_i18n_t') ? pkv4_i18n_t($s, ['text', 'block', 'attr']) : $s;
};
?>
<article class="pk-product-card">
  <a class="pk-product-card-main" href="<?php echo esc_url(get_permalink($p)); ?>">
    <span class="pk-product-card-image"><?php if ($cover) : ?><img src="<?php echo esc_url($card_src); ?>" <?php if ($card_srcset) : ?>srcset="<?php echo esc_attr($card_srcset); ?>" sizes="<?php echo esc_attr($card_sizes); ?>"<?php endif; ?> alt="<?php echo esc_attr($photo_alt ?: $card_title); ?>" width="400" height="300" loading="lazy" /><?php endif; ?><span class="pk-product-moq"><?php echo esc_html($pkv4_t('MOQ 250')); ?></span></span>
    <span class="pk-product-card-copy"><span class="pk-product-card-group"><?php echo esc_html($args['group'] ?? ''); ?></span><span class="pk-card-title"><?php echo esc_html($card_title); ?></span><span class="pk-product-card-desc"><?php echo esc_html(wp_strip_all_tags($desc ?: $p->post_excerpt)); ?></span></span>
  </a>
  <?php if ($caption) : ?><small class="pk-product-photo-caption"><?php echo esc_html($caption); ?></small><?php endif; ?>
  <div class="pk-product-card-footer"><span><?php if ($price !== null && $ref) : ?><strong><?php echo esc_html(sprintf($pkv4_t('from $%s / unit'), number_format($price,2))); ?></strong><small><?php echo esc_html(sprintf($pkv4_t('at %s pcs · %s cm'), (string) $card_qty, implode('×',array_slice($ref,0,3)))); ?></small><?php endif; ?></span><a href="<?php echo esc_url(add_query_arg($query,home_url('/get-a-quote/'))); ?>"><?php echo $price !== null ? 'Configure →' : 'Get a price →'; ?></a></div>
</article>
