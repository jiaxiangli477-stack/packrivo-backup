<?php
/** English product specification sheet. The saved legacy post remains intact. */
if (!defined('ABSPATH')) exit;
$product=pk33b_current_product();
if (!$product) { get_template_part('page'); return; }
$post_id=(int)get_queried_object_id(); $post=get_post($post_id); $slug=$post->post_name;
$raw_size=$_GET['size'] ?? '0';
$size_index=is_scalar($raw_size) && preg_match('/\A\d{1,3}\z/', (string)$raw_size) ? (int)$raw_size : 0;
if (!isset($product['sizes'][$size_index])) $size_index=0;
$dimensions=pk33b_dimensions($product,$size_index);
$style=(string)$product['engine_style']; $material=(string)$product['engine_material'];
$price_input=pk33b_price_input($product,$size_index); $rows=pk33b_price_rows($product,$size_index);
$settings=class_exists('PKQ_Plugin') ? PKQ_Plugin::settings() : [];
if (strtoupper((string)($settings['currency'] ?? '')) !== 'USD') $rows=[];
$configured_min=$settings['min_qty'] ?? null;
$qty=is_numeric($configured_min) && (int)$configured_min > 0 ? (int)$configured_min : 250;
$quantities=array_keys($rows);
if (!$quantities && class_exists('PKQ_Pricing')) {
    foreach (PKQ_Pricing::tables()['tiers'] ?? [] as $tier) {
        $candidate=(int)($tier[0] ?? 0);
        if ($candidate >= $qty && $candidate <= 25000) $quantities[]=$candidate;
    }
}
$require_size=$dimensions ? '0' : '1'; $require_material=$material !== '' ? '0' : '1';
$hub=!empty($product['category_hub']) ? get_page_by_path($product['category_hub'], OBJECT, 'page') : null;
if ($hub && $hub->post_status !== 'publish') $hub=null;
$images=[];
foreach ($product['images'] as $image) {
    $relative=pk33b_product_image_path($slug,$image['file']);
    if (!$relative || !is_readable(get_stylesheet_directory().'/'.$relative)) continue;
    $images[]=['url'=>get_stylesheet_directory_uri().'/'.$relative,'alt'=>$image['alt'],'caption'=>$image['caption']];
}
$specs=pk33b_visible_specs($product); $structure=[]; $review=[];
$visible_faq=array_values(array_filter($product['faq'], static function ($entry) {
    return trim($entry['question']) !== '' && trim($entry['answer']) !== '';
}));
foreach ($specs as $spec) {
    if (preg_match('/sample|production|print review|approval|inspection|order documents|photo/i',$spec['label'])) $review[]=$spec;
    else $structure[]=$spec;
}
$finish_tables=class_exists('PKQ_Pricing') ? (PKQ_Pricing::tables()['finishes'] ?? []) : [];
$finish_groups=['surface'=>[], 'logo'=>[]];
foreach ($product['finishes'] as $finish) {
    if ($finish === 'none' || !isset($finish_tables[$finish])) continue;
    $group=in_array($finish,['foil','emboss','spot-uv'],true) ? 'logo' : 'surface';
    $finish_groups[$group][]=['key'=>$finish,'label'=>(string)($finish_tables[$finish]['label'] ?? $finish),'delta'=>$rows ? pk33b_finish_delta($product,$finish,$size_index,1000) : null];
}
$insert_labels=['eva'=>'EVA insert','pu'=>'PU insert','card'=>'Paperboard insert','corrugated'=>'Corrugated insert','molded-pulp-on-request'=>'Molded pulp insert','film-frame'=>'Clear film frame'];
$quote_fields=static function ($quantity) use ($slug,$style,$size_index,$require_size,$require_material) {
    $fields=['product'=>$slug,'style'=>$style,'qty'=>(string)$quantity,'size'=>(string)$size_index];
    if ($require_size==='1') $fields['require_size']='1';
    if ($require_material==='1') $fields['require_material']='1';
    foreach ($fields as $name=>$value) if ($value !== '') echo '<input type="hidden" name="'.esc_attr($name).'" value="'.esc_attr($value).'">';
};
$quote_form=static function ($label,$quantity,$class='pk-btn') use ($quote_fields) {
    echo '<form class="pk33b-quote-form" method="get" action="'.esc_url(home_url('/get-a-quote/')).'">';
    $quote_fields($quantity);
    echo '<button class="'.esc_attr($class).'" type="submit">'.esc_html($label).'</button></form>';
};
$money=static function($value) { return '$'.number_format((float)$value,2); };
get_header();
?>
<main id="main" class="pk-main pk33b-product">
 <div class="pk-wrap">
  <nav class="pk33b-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a><?php if ($hub) : ?><span aria-hidden="true"> / </span><a href="<?php echo esc_url(get_permalink($hub)); ?>"><?php echo esc_html(get_the_title($hub)); ?></a><?php endif; ?><span aria-hidden="true"> / </span><span aria-current="page"><?php echo esc_html($product['h1']); ?></span></nav>
  <section class="pk33b-hero">
   <div class="pk33b-visual">
    <div class="pk33b-gallery" data-pk33b-gallery>
      <span class="pk33b-moq">MOQ <bdi dir="ltr"><?php echo esc_html(number_format_i18n($qty)); ?></bdi></span>
      <?php if ($images) : ?>
        <figure class="pk33b-main-figure"><img data-pk33b-main src="<?php echo esc_url($images[0]['url']); ?>" alt="<?php echo esc_attr($images[0]['alt']); ?>" width="1200" height="1200" fetchpriority="high"><figcaption data-pk33b-caption><?php echo esc_html($images[0]['caption']); ?></figcaption></figure>
        <div class="pk33b-thumbnails" aria-label="Product images"><?php foreach ($images as $index=>$image) : ?><a href="<?php echo esc_url($image['url']); ?>" data-pk33b-image data-alt="<?php echo esc_attr($image['alt']); ?>" data-caption="<?php echo esc_attr($image['caption']); ?>" aria-label="<?php echo esc_attr(sprintf('View image %s', (string)($index+1))); ?>"><img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" loading="lazy" width="144" height="144"></a><?php endforeach; ?></div>
      <?php else : ?><div class="pk33b-image-placeholder" aria-hidden="true"></div><?php endif; ?>
    </div>
    <div class="pk33b-benefits"><div>Samples <bdi dir="ltr">5–10 days</bdi></div><div>DDP or FOB</div><div>Dieline with every quote</div></div>
   </div>
   <div class="pk33b-overview">
    <?php if ($hub) : ?><p class="pk33b-kicker"><?php echo esc_html(get_the_title($hub)); ?></p><?php endif; ?>
    <h1 class="pk-display"><?php echo esc_html($product['h1']); ?></h1>
    <?php if ($product['lede'] !== '') : ?><p class="pk-lede"><?php echo esc_html($product['lede']); ?></p><?php endif; ?>
    <?php if ($product['sizes']) : ?><fieldset class="pk33b-sizes"><legend>Reference size</legend><div><?php foreach ($product['sizes'] as $index=>$size) : ?><form method="get" action="<?php echo esc_url(get_permalink($post_id)); ?>"><button type="submit" name="size" value="<?php echo (int)$index; ?>" <?php echo $index===$size_index ? 'aria-current="true"' : ''; ?>><bdi dir="ltr"><?php echo esc_html($size['l_mm'].' × '.$size['w_mm'].' × '.$size['h_mm'].' mm'); ?></bdi><small><?php echo esc_html($size['label']); ?></small></button></form><?php endforeach; ?></div></fieldset><?php endif; ?>
    <?php if ($rows) : ?><div class="pk33b-price-panel"><p>Estimate, sourcing fee included</p><div class="pk33b-price-pair"><?php if (isset($rows[1000])) : ?><div><small>1,000 pieces</small><strong><bdi dir="ltr"><?php echo esc_html($money($rows[1000]['unit'])); ?></bdi> / unit</strong></div><?php endif; ?><?php if (isset($rows[250])) : ?><div><small>From 250 pieces</small><strong><bdi dir="ltr"><?php echo esc_html($money($rows[250]['unit'])); ?></bdi> / unit</strong></div><?php endif; ?></div><p>Reference estimate: unprinted, no finish; inserts are quoted on request.</p><p>Freight &amp; duties quoted per destination</p></div><?php else : ?><div class="pk33b-price-panel"><p>Share the missing specifications for a tailored estimate.</p></div><?php endif; ?>
    <div class="pk33b-timing"><p>Sample <bdi dir="ltr">5–10 days</bdi></p><p>Production <bdi dir="ltr">7–30 days</bdi> after sample approval</p></div>
    <div class="pk33b-actions"><?php $quote_form('Configure this box →',$qty); $quote_form('Get the dieline with your quote',$qty,'pk-btn-ghost'); ?><a class="pk-btn-ghost" href="<?php echo esc_url(home_url('/samples/')); ?>">Order a sample</a></div>
    <?php if ($product['construction']) : ?><div class="pk33b-construction"><h2>Construction details to define</h2><ul><?php foreach (array_slice($product['construction'],0,3) as $item) : if (trim($item)==='') continue; ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
   </div>
  </section>

  <?php if ($quantities) : $first=$rows ? reset($rows) : null; ?>
  <section class="pk33b-section" aria-labelledby="pk33b-tier-heading"><div class="pk33b-section-title"><p class="pk33b-kicker">Estimate</p><h2 id="pk33b-tier-heading">Quantity and price</h2><p>Prices include the sourcing fee. Freight &amp; duties quoted per destination.</p></div>
   <?php if (!$rows) : ?><p>Complete dimensions and material for an estimate.</p><?php endif; ?>
   <div class="pk33b-scroll" tabindex="0" role="region" aria-label="Scrollable price table"><table class="pk33b-table"><thead><tr><th>Quantity</th><th>Unit price, fee included</th><th>Savings against minimum</th><th>Setup</th><th>Goods total</th><th>Freight &amp; duties</th><th></th></tr></thead><tbody>
    <?php foreach ($quantities as $quantity) : $row=$rows[$quantity] ?? null; $saving=$row && (float)$first['unit']>0 ? 100*(1-(float)$row['unit']/(float)$first['unit']) : null; ?><tr><td><bdi dir="ltr"><?php echo esc_html(number_format_i18n($quantity)); ?></bdi></td><td><bdi dir="ltr"><?php echo $row ? esc_html($money($row['unit'])) : '—'; ?></bdi></td><td><bdi dir="ltr"><?php echo $row ? esc_html(number_format($saving,1)).'%' : '—'; ?></bdi></td><td><bdi dir="ltr"><?php echo $row ? esc_html((float)$row['setup'] === 0.0 ? 'Waived' : $money($row['setup'])) : '—'; ?></bdi></td><td><bdi dir="ltr"><?php echo $row ? esc_html($money($row['goods_total'])) : '—'; ?></bdi></td><td>Quoted per destination</td><td><?php $quote_form('Select',$quantity,'pk33b-select'); ?></td></tr><?php endforeach; ?>
   </tbody></table></div>
  </section>
  <?php endif; ?>

  <?php if ($product['finishes'] || $product['inserts']) : ?>
  <section class="pk33b-section" aria-labelledby="pk33b-options-heading"><div class="pk33b-section-title"><p class="pk33b-kicker">Options</p><h2 id="pk33b-options-heading">Customization options</h2></div><div class="pk33b-options">
   <?php foreach (['surface'=>'Surface finishes','logo'=>'Logo finishes'] as $key=>$label) : ?><div class="pk33b-panel"><p class="pk33b-kicker"><?php echo $key==='surface' ? '01' : '02'; ?></p><h3><?php echo esc_html($label); ?></h3><?php if ($finish_groups[$key]) : ?><ul><?php foreach ($finish_groups[$key] as $finish) : ?><li><span><?php echo esc_html($finish['label']); ?></span><?php if ($finish['delta']!==null) : ?><strong><bdi dir="ltr"><?php echo $finish['delta']>=0 ? '+' : '−'; echo esc_html($money(abs($finish['delta']))); ?></bdi> / unit</strong><?php endif; ?></li><?php endforeach; ?></ul><small>Estimate at 1,000 pieces, sourcing fee included.</small><?php endif; ?></div><?php endforeach; ?>
   <div class="pk33b-panel"><p class="pk33b-kicker">03</p><h3>Inserts</h3><?php if ($product['inserts']) : ?><ul><?php foreach ($product['inserts'] as $insert) : if (!isset($insert_labels[$insert])) continue; ?><li><span><?php echo esc_html($insert_labels[$insert]); ?></span><small><?php echo esc_html($insert==='molded-pulp-on-request' ? 'on request, mould quoted separately' : 'quoted on request'); ?></small></li><?php endforeach; ?></ul><?php endif; ?></div>
  </div></section><?php endif; ?>

  <?php if ($structure || $review) : ?><section class="pk33b-section" aria-labelledby="pk33b-spec-heading"><div class="pk33b-section-title"><p class="pk33b-kicker">Specification</p><h2 id="pk33b-spec-heading">Specifications and review</h2></div><div class="pk33b-spec-grid"><?php foreach (['Structure and materials'=>$structure,'Sampling and review'=>$review] as $label=>$group) : if (!$group) continue; ?><div class="pk33b-panel"><h3><?php echo esc_html($label); ?></h3><dl class="pk33b-facts"><?php foreach ($group as $spec) : ?><div><dt><?php echo esc_html($spec['label']); ?></dt><dd><?php echo esc_html($spec['value']); ?></dd></div><?php endforeach; ?></dl></div><?php endforeach; ?></div></section><?php endif; ?>

  <section class="pk33b-section pk33b-dieline<?php echo $dimensions ? '' : ' pk33b-dieline-no-image'; ?>"><?php if ($dimensions) : $l=(float)$dimensions['length'];$w=(float)$dimensions['width'];$h=(float)$dimensions['height'];$vw=$l+2*$h;$vh=$w+2*$h; ?><div class="pk33b-illustration"><svg viewBox="0 0 <?php echo esc_attr($vw.' '.$vh); ?>" role="img" aria-label="Illustrative flat layout"><rect x="<?php echo esc_attr($h); ?>" y="<?php echo esc_attr($h); ?>" width="<?php echo esc_attr($l); ?>" height="<?php echo esc_attr($w); ?>"/><rect x="<?php echo esc_attr($h); ?>" y="0" width="<?php echo esc_attr($l); ?>" height="<?php echo esc_attr($h); ?>"/><rect x="<?php echo esc_attr($h); ?>" y="<?php echo esc_attr($h+$w); ?>" width="<?php echo esc_attr($l); ?>" height="<?php echo esc_attr($h); ?>"/><rect x="0" y="<?php echo esc_attr($h); ?>" width="<?php echo esc_attr($h); ?>" height="<?php echo esc_attr($w); ?>"/><rect x="<?php echo esc_attr($h+$l); ?>" y="<?php echo esc_attr($h); ?>" width="<?php echo esc_attr($h); ?>" height="<?php echo esc_attr($w); ?>"/></svg><p>Illustrative only; not a production dieline.</p></div><?php endif; ?><div><p class="pk33b-kicker">Dieline</p><h2>A dieline comes with every quote</h2><?php $quote_form('Get the dieline with your quote',$qty); ?></div></section>

  <section class="pk33b-section" aria-labelledby="pk33b-shipping-heading"><div class="pk33b-section-title"><p class="pk33b-kicker">Delivery</p><h2 id="pk33b-shipping-heading">Shipping options</h2><p>Freight &amp; duties quoted per destination.</p></div><div class="pk33b-options"><div class="pk33b-panel"><h3>DDP to door</h3><p>Destination and terms are confirmed on the written quote.</p></div><div class="pk33b-panel"><h3>FOB Shenzhen</h3><p>FOB is available for shipment from Shenzhen.</p></div><div class="pk33b-panel"><h3>Consolidation</h3><p>FOB is available if you consolidate.</p></div></div></section>

  <section class="pk33b-section" id="pk33b-quote" aria-labelledby="pk33b-quote-heading"><div class="pk33b-section-title"><p class="pk33b-kicker">Your brief</p><h2 id="pk33b-quote-heading">Build your quote</h2><p>Confirm the dimensions and material for your project.</p></div><?php if (shortcode_exists('packrivo_quote')) echo do_shortcode('[packrivo_quote style="'.esc_attr($style).'" qty="'.(int)$qty.'" product="'.esc_attr($slug).'" require_size="'.$require_size.'" require_material="'.$require_material.'"]'); ?></section>

  <?php if ($visible_faq) : ?><section class="pk33b-section" aria-labelledby="pk33b-faq-heading"><div class="pk33b-section-title"><p class="pk33b-kicker">Questions</p><h2 id="pk33b-faq-heading">Frequently asked questions</h2></div><div class="pk33b-faq"><?php foreach ($visible_faq as $entry) : ?><details open><summary><?php echo esc_html($entry['question']); ?></summary><p><?php echo esc_html($entry['answer']); ?></p></details><?php endforeach; ?></div></section><?php endif; ?>
  <?php $related=[]; foreach ($product['related'] as $related_slug) { $related_post=get_page_by_path($related_slug,OBJECT,'page'); if ($related_post && $related_post->post_status==='publish' && (int)$related_post->ID!==$post_id) $related[]=$related_post; } if ($related) : ?><section class="pk33b-section"><div class="pk33b-section-title"><p class="pk33b-kicker">Explore</p><h2>Related products</h2></div><div class="pk-cards pk-cards-4"><?php foreach ($related as $related_post) get_template_part('template-parts/product-card',null,['product'=>$related_post]); ?></div></section><?php endif; ?>
 </div>
</main>
<?php
$product_schema=['@context'=>'https://schema.org','@type'=>'Product','name'=>$product['h1'],'brand'=>['@type'=>'Brand','name'=>'Packrivo']];
if (trim($product['lede']) !== '') $product_schema['description']=$product['lede'];
if ($images) $product_schema['image']=array_values(array_column($images,'url'));
if ($rows && strtoupper((string)($settings['currency'] ?? 'USD')) === 'USD') {
    $units=array_map(static function($row){return (float)$row['unit'];},$rows);
    $product_schema['offers']=['@type'=>'AggregateOffer','priceCurrency'=>'USD','lowPrice'=>min($units),'highPrice'=>max($units),'offerCount'=>count($units)];
}
echo '<script type="application/ld+json" class="pk33b-product-data">'.wp_json_encode($product_schema,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG).'</script>';
$faq_schema=[]; foreach ($visible_faq as $entry) $faq_schema[]=['@type'=>'Question','name'=>$entry['question'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$entry['answer']]];
if ($faq_schema) echo '<script type="application/ld+json" class="pk33b-faq-data">'.wp_json_encode(['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>$faq_schema],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG).'</script>';
get_footer();
