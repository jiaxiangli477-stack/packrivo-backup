<?php
/** Shared product card. Pass product as the `product` template argument. */
$p = $args['product'] ?? null;
if (!$p) return;
$p = get_post($p);
if (!$p) return;
$card_title = $args['title'] ?? wp_strip_all_tags($p->post_title);
$style = pk_engine_style_for($p->ID);
$cover = get_the_post_thumbnail_url($p, 'medium_large') ?: get_post_meta($p->ID, '_packrivo_article_cover_url', true);
$caption = get_post_meta($p->ID, '_packrivo_card_photo_caption', true);
$thumb_id = get_post_thumbnail_id($p);
$small_id = (int) get_post_meta($p->ID, '_packrivo_card_800_id', true);
$photo_alt = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
$srcset = '';
if ($caption && $thumb_id && $small_id) {
    $large_url = wp_get_attachment_url($thumb_id);
    $small_url = wp_get_attachment_url($small_id);
    $large_url = function_exists('pk33a_product_image') ? pk33a_product_image($large_url) : $large_url;
    $small_url = function_exists('pk33a_product_image') ? pk33a_product_image($small_url) : $small_url;
    if ($large_url && $small_url) { $cover=$large_url; $srcset=$small_url.' 800w, '.$large_url.' 1200w'; }
}
$cover = function_exists('pk33a_product_image') ? pk33a_product_image($cover) : $cover;
$desc = get_post_meta($p->ID, '_packrivo_description', true);
// 卡片上的价格行是拼出来的（数字每次都变），输出缓冲按整串匹配替换不到，这里主动过词典。
$pkv4_t = static function ($s) {
    return function_exists('pkv4_i18n_t') ? pkv4_i18n_t($s, ['text', 'block', 'attr']) : $s;
};
?>
<article class="pk-product-card">
  <a class="pk-product-card-main" href="<?php echo esc_url(get_permalink($p)); ?>">
    <span class="pk-product-card-image"><?php if ($cover) : ?><img src="<?php echo esc_url($cover); ?>" <?php if ($srcset) : ?>srcset="<?php echo esc_attr($srcset); ?>" sizes="(max-width:560px) 100vw, 25vw"<?php endif; ?> alt="<?php echo esc_attr($photo_alt ?: $card_title); ?>" loading="lazy" /><?php else : ?><span class="pk33a-product-placeholder" aria-hidden="true"></span><?php endif; ?><span class="pk-product-moq"><?php echo esc_html($pkv4_t('MOQ 250')); ?></span></span>
    <span class="pk-product-card-copy"><span class="pk-product-card-group"><?php echo esc_html($args['group'] ?? ''); ?></span><span class="pk-card-title"><?php echo esc_html($card_title); ?></span><span class="pk-product-card-desc"><?php echo esc_html(wp_strip_all_tags($desc ?: $p->post_excerpt)); ?></span></span>
  </a>
  <?php if ($caption) : ?><small class="pk-product-photo-caption"><?php echo esc_html($caption); ?></small><?php endif; ?>
  <?php if (function_exists('pk33a_product_spec_html')) echo pk33a_product_spec_html($p->ID); ?>
  <?php if (function_exists('pk33a_product_footer')) echo pk33a_product_footer($p); ?>
</article>
