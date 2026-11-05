<?php
/**
 * Front page v4 — same IA as the live site, new presentation.
 * Sections: hero · categories · how it works · price transparency · materials · journal · FAQ · CTA
 */
get_header();

$img = get_stylesheet_directory_uri() . '/assets/img';

// Card titles come from each page's reviewed buyer title, never from a local label.
$home_specs = [
    ['product-drawer-jewelry-box','Jewelry boxes'],
    ['product-lid-and-base-jewelry-box','Jewelry boxes'],
    ['product-jewelry-box-with-insert','Jewelry boxes'],
    ['product-window-lid-rigid-gift-box','Gift boxes'],
    ['product-hinged-nonmagnetic-rigid-box','Gift boxes'],
    ['product-corrugated-roll-end-hinged-lid','Mailer boxes'],
    ['product-euro-tote-paper-carrier','Paper bags'],
    ['product-sos-paper-gift-bag','Paper bags'],
];
$home_products = [];
$home_250_prices = [];
foreach ($home_specs as [$slug,$group]) {
    $page = get_page_by_path($slug);
    if (!$page || $page->post_status !== 'publish') continue;
    $cover = get_the_post_thumbnail_url($page,'medium_large') ?: get_post_meta($page->ID,'_packrivo_article_cover_url',true);
    $price = pk_from_price($page->ID);
    if ($cover && $price !== null) {
        $home_products[] = ['product'=>$page,'group'=>$group,'title'=>wp_strip_all_tags(get_the_title($page)),'price'=>$price];
        $price_250 = pk_from_price($page->ID, 250);
        if ($price_250 !== null) $home_250_prices[] = $price_250;
    }
    if (count($home_products) === 8) break;
}
$contact = get_page_by_path('contact');
$contact_url = $contact && $contact->post_status === 'publish' ? get_permalink($contact) : home_url('/get-a-quote/');
$steps = [
    ['01', 'Brief', 'Send the pack shot, target quantity and where it ships. We reply with a structure and a price range.'],
    ['02', 'Sample', 'A physical sample in your artwork, produced in 5–10 days. Nothing is committed before you hold it.'],
    ['03', 'Produce', 'Artwork goes to plate, the run is scheduled, and we send press sheets before packing.'],
    ['04', 'Deliver', 'DDP or FOB, palletised, with photos and the material declaration per order.'],
];
$step_photos = [
    ['brief',1600,1200,'Supplier sample room with shelves of rigid gift boxes and jewelry boxes','Supplier sample room, Dongguan. Structures are chosen from real samples before artwork starts.'],
    ['sample',1600,1200,'Operator feeding a printed proof sheet into a wide-format digital proofing printer','Digital proof printing at a supplier in Dongguan.'],
    ['produce',1600,1200,'Multi-colour sheet-fed offset printing press in a packaging workshop','Sheet-fed offset press used for production runs.'],
    ['deliver',1040,780,'Finished jewelry boxes packed in export cartons before shipping','Finished boxes packed into cartons, Shantou.'],
];

$faq_price_answer = 'The quote builder shows the full curve for your size.';
if (pk_pricing_tables_ready()) {
    $pk_faq_common = ['print'=>'full-color','finish'=>'matte','quantity'=>500,'rush'=>false];
    $pk_faq_rigid = PKQ_Pricing::price(array_merge($pk_faq_common, ['style'=>'rigid','material'=>'rigidboard','length'=>9.5,'width'=>9.5,'height'=>4]), PKQ_Plugin::settings());
    $pk_faq_mailer = PKQ_Pricing::price(array_merge($pk_faq_common, ['style'=>'mailer','material'=>'kraft','length'=>30,'width'=>22,'height'=>12]), PKQ_Plugin::settings());
    if (!is_wp_error($pk_faq_rigid) && !is_wp_error($pk_faq_mailer)) {
        $faq_price_answer = sprintf('At 500 pieces, a 9.5×9.5×4 cm rigid jewelry box is about $%s per unit and a 30×22×12 cm printed mailer box about $%s. The unit price drops as quantity goes up — the quote builder shows the full curve for your size.', number_format((float)$pk_faq_rigid['unit'],2), number_format((float)$pk_faq_mailer['unit'],2));
    }
}
$faq = [
    ['What is the minimum order?', 'MOQ 250 pieces per design for printed boxes. Unprinted stock ships from 100 pieces. Runs of 100–249 are quoted on request.'],
    ['How much does a custom box cost?', $faq_price_answer],
    ['How long does production take?', 'Stock styles ship in 1–15 days. Printed custom orders are scheduled after sample approval, usually 7–30 days depending on structure and finishing.'],
    ['Do you handle the whole supply chain?', 'We coordinate sampling, production, inspection and freight with vetted suppliers, and charge an 8–15% sourcing fee on the order value, with a $150 minimum per order. Instant quotes include the fee at 15%; your final rate is confirmed on the written quote.'],
    ['Where do you ship from?', 'Shenzhen, China. DDP to the US and EU is standard; FOB is available if you consolidate.'],
];
$pk33a_ltr_values = static function ($template, array $values) {
    $translated = function_exists('pkv4_i18n_t') ? pkv4_i18n_t($template, ['text','block']) : $template;
    $tokens = [];
    foreach ($values as $i => $value) {
        $token = count($values) === 1 ? '%s' : '%' . ($i + 1) . '$s';
        if (substr_count($translated, $token) !== 1) $translated = $template;
        $tokens[$token] = '<bdi dir="ltr">' . esc_html($value) . '</bdi>';
    }
    return strtr(esc_html($translated), $tokens);
};
$pk_hero_prices = [];
$pk_curve_default = null;
if (pk_pricing_tables_ready()) {
    $pk_hero_spec = ['style'=>'rigid','material'=>'rigidboard','print'=>'full-color','finish'=>'matte','length'=>9.5,'width'=>9.5,'height'=>4];
    foreach ([250, 1000] as $quantity) {
        $result = PKQ_Pricing::price(array_merge($pk_hero_spec, ['quantity'=>$quantity]), PKQ_Plugin::settings());
        if (!is_wp_error($result)) $pk_hero_prices[$quantity] = $result;
    }
    $pk_curve_default = PKQ_Pricing::price(['style'=>'mailer','material'=>'kraft','print'=>'full-color','finish'=>'matte','quantity'=>1000,'length'=>30,'width'=>22,'height'=>12], PKQ_Plugin::settings());
    if (is_wp_error($pk_curve_default)) $pk_curve_default = null;
    // Enqueue the plugin's own calculator and configuration without its UI.
    PKQ_Plugin::render_shortcode();
    wp_dequeue_style('pkq-quote');
    wp_enqueue_script('pk33a-curve', get_stylesheet_directory_uri().'/assets/33a-curve.js', ['pkq-quote'], PKV4_VERSION, true);
}
?>
<main id="main" class="pk-main">
<section class="pk-hero">
  <div class="pk-wrap pk-hero-grid">
    <div class="pk-hero-copy">
      <p class="pk-eyebrow">Custom packaging · MOQ 250 · 11 languages</p>
      <h1 class="pk-display"><?php
      $pk_home_h1_translation = function_exists('packrivo_lang') && packrivo_lang() !== null && function_exists('packrivo_i18n_lookup')
          ? packrivo_i18n_lookup('Custom packaging for jewelry, candles & gifts.', ['block', 'text']) : null;
      if ($pk_home_h1_translation !== null) :
          echo esc_html($pk_home_h1_translation);
      else : ?>Custom packaging for <span class="pk-accent">jewelry, candles</span> &amp; gifts.<?php endif; ?></h1>
      <p class="pk-lede">Sampling, production and freight handled end to end. Reference prices published, a cutting template with every quote, and a person on the other end of the quote — not a form that goes nowhere.</p>
      <div class="pk33a-hero-facts"><span><?php echo $pk33a_ltr_values('Samples %s days', ['5–10']); ?></span><span>DDP or FOB</span><span><?php echo $pk33a_ltr_values('Sourcing fee %s', ['8–15%']); ?></span></div>
      <div class="pk-hero-cta">
        <a class="pk-btn" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Build a quote <span aria-hidden="true">→</span></a>
        <a class="pk-btn-ghost" href="<?php echo esc_url(home_url('/samples/')); ?>">Order a sample kit</a>
      </div>
    </div>
    <figure class="pk-hero-media pk33a-showcase">
      <?php // 图里没有内衬、也不是棚拍实物：alt 只写看到的两个奶油色礼盒与棕色缎带。 ?>
      <img src="<?php echo esc_url($img . '/hero.webp'); ?>" srcset="<?php echo esc_attr($img . '/hero-480.webp 480w, ' . $img . '/hero-800.webp 800w, ' . $img . '/hero.webp 1200w'); ?>" sizes="(max-width: 900px) calc(100vw - 48px), 42vw" alt="Two small cream gift boxes with brown satin ribbon, held in open hands" width="1200" height="1200" loading="eager" fetchpriority="high" decoding="async" />
      <figcaption class="pk-hero-card">
        <p class="pk-mono">Example quote · Rigid magnetic box</p>
        <dl class="pk33a-specs">
          <div><dt>Size</dt><dd><bdi dir="ltr">9.5×9.5×4 cm</bdi></dd></div>
          <div><dt>Board</dt><dd>Rigid greyboard</dd></div>
          <?php foreach ([1000,250] as $quantity) : if (!isset($pk_hero_prices[$quantity])) continue; ?>
          <div><dt><?php echo $pk33a_ltr_values('%s pieces, sourcing fee included', [number_format_i18n($quantity)]); ?></dt><dd><bdi dir="ltr">$<?php echo esc_html(number_format($pk_hero_prices[$quantity]['unit'],2)); ?> / unit</bdi></dd></div>
          <?php endforeach; ?>
          <div><dt>Samples</dt><dd><?php echo $pk33a_ltr_values('%s days', ['5–10']); ?></dd></div>
          <div><dt>MOQ</dt><dd><bdi dir="ltr">250 / design</bdi></dd></div>
        </dl>
      </figcaption>
    </figure>
    <dl class="pk-stats">
      <?php if ($home_250_prices) : ?><div><dt>From · 250 pcs</dt><dd><bdi dir="ltr">$<?php echo esc_html(number_format(min($home_250_prices),2)); ?> / unit</bdi></dd></div><?php endif; ?>
      <div><dt>MOQ</dt><dd><bdi dir="ltr">250 / design</bdi></dd></div>
      <div><dt>Production</dt><dd><bdi dir="ltr">7–30 days</bdi></dd></div>
      <div><dt>Sourcing fee</dt><dd><bdi dir="ltr">8–15%</bdi></dd></div>
    </dl>
  </div>
</section>

<section class="pk-section pk-bordered">
  <div class="pk-wrap">
    <div class="pk-section-head">
      <div>
        <p class="pk-eyebrow">What we make</p>
        <h2 class="pk-h2">Custom jewelry boxes, pouches and gift boxes with your logo</h2>
      </div>
      <a class="pk-more" href="<?php echo esc_url(home_url('/products/')); ?>">All products →</a><?php if (pk31b_is_english()) : ?><a class="pk-more" href="<?php echo esc_url(home_url('/bags-pouches/')); ?>">Bags &amp; pouches</a><?php endif; echo "\n"; ?>
    </div>
    <div class="pk-cards pk-cards-4">
      <?php foreach ($home_products as $card) : get_template_part('template-parts/product-card', null, $card); endforeach; ?>
    </div>
  </div>
</section>

<section class="pk-section pk-dark">
  <div class="pk-wrap">
    <div class="pk-section-head">
      <div>
        <p class="pk-eyebrow pk-eyebrow-light">How it works</p>
        <h2 class="pk-h2 pk-on-dark">From brief to pallet in four steps</h2>
      </div>
    </div>
    <ol class="pk-steps">
      <?php foreach ($steps as $pk_step_index => [$num, $title, $body]) : [$photo,$width,$height,$alt,$caption]=$step_photos[$pk_step_index]; $base=$img.'/31b-process/'.$photo.'-'; ?>
        <li><figure class="pk-step-photo" style="margin:0 0 1rem"><img src="<?php echo esc_url($base.'960.webp'); ?>" srcset="<?php echo esc_attr($base.'480.webp 480w, '.$base.'960.webp 960w, '.$base.$width.'.webp '.$width.'w'); ?>" sizes="(max-width: 600px) 100vw, (max-width: 1000px) 50vw, 25vw" width="<?php echo (int)$width; ?>" height="<?php echo (int)$height; ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy" decoding="async" style="display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover"><figcaption style="font-size:.75rem;line-height:1.4;color:#e2e9e3;opacity:1;margin-top:.5rem;min-height:clamp(0px,calc(100vw - 1100px),5.6em)"><?php echo esc_html($caption); ?></figcaption></figure>
          <span class="pk-step-num"><?php echo esc_html($num); ?></span>
          <span class="pk-step-title"><?php echo esc_html($title); ?></span>
          <span class="pk-step-text"><?php echo esc_html($body); ?></span>
          <small class="pk33a-milestone"><?php echo esc_html(sprintf('Milestone: %s', $title)); ?></small>
        </li>
      <?php endforeach; ?>
    </ol><p class="pk-step-photo-note" style="font-size:.75rem;line-height:1.4;color:#e2e9e3;margin-top:.5rem">Supplier photos, used with permission.</p>
  </div>
</section>

<section class="pk-section">
  <div class="pk-wrap pk-split">
    <div>
      <p class="pk-eyebrow">Price transparency</p>
      <h2 class="pk-h2">A price curve, not a contact form</h2>
      <p class="pk-lede">Every structure is priced against the same model: board, size, print, finish and quantity. Change the quantity and the unit price moves — so you can see where the break-even sits before you commit to a tooling cost.</p>
      <ul class="pk-list">
        <?php $pk_tables = pk_pricing_tables_ready() ? PKQ_Pricing::tables() : null; ?>
        <?php if (isset($pk_tables['rules']['freePrintSetupFrom'], $pk_tables['rules']['freeDieFrom'])) : ?><li><?php echo $pk33a_ltr_values('Print setup is waived from %1$s pieces; die setup is waived from %2$s pieces.', [number_format_i18n($pk_tables['rules']['freePrintSetupFrom']), number_format_i18n($pk_tables['rules']['freeDieFrom'])]); ?></li><?php endif; ?>
        <li>Freight &amp; duties quoted per destination</li>
        <li><strong>No tooling surprise</strong> — one-time plate and die cost, shown before you submit.</li>
      </ul>
      <a class="pk-btn" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Open the quote builder</a>
    </div>
    <div class="pk-table-card pk33a-curve" data-pk33a-curve>
      <p class="pk-mono pk-muted">Mailer box · 30×22×12 cm · kraft · full colour</p>
      <?php if ($pk_curve_default) : ?>
      <label for="pk33a-quantity">Quantity</label>
      <output class="pk33a-quantity" for="pk33a-quantity" data-curve-quantity><bdi dir="ltr">1,000</bdi></output>
      <input id="pk33a-quantity" type="range" min="250" max="10000" step="250" value="1000" disabled>
      <div class="pk33a-ticks"><span><bdi dir="ltr">250</bdi></span><span><bdi dir="ltr"><?php echo esc_html(number_format_i18n($pk_tables['rules']['freePrintSetupFrom'])); ?></bdi> · <?php echo esc_html(pkv4_i18n_t('print setup waived')); ?></span><span><bdi dir="ltr"><?php echo esc_html(number_format_i18n($pk_tables['rules']['freeDieFrom'])); ?></bdi> · <?php echo esc_html(pkv4_i18n_t('die setup waived')); ?></span><span><bdi dir="ltr">10,000</bdi></span></div>
      <div class="pk33a-curve-result" aria-live="polite">
        <dl>
          <div><dt>Goods</dt><dd data-curve-goods><bdi dir="ltr">$<?php echo esc_html(number_format($pk_curve_default['goods_total'],2)); ?></bdi></dd></div>
          <div><dt>Sourcing fee</dt><dd data-curve-fee><bdi dir="ltr">$<?php echo esc_html(number_format($pk_curve_default['fee'],2)); ?></bdi></dd></div>
          <div><dt>Unit price, sourcing fee included</dt><dd data-curve-unit><bdi dir="ltr">$<?php echo esc_html(number_format($pk_curve_default['unit'],2)); ?></bdi></dd></div>
          <div><dt>Order total</dt><dd data-curve-total><bdi dir="ltr">$<?php echo esc_html(number_format($pk_curve_default['total'],2)); ?></bdi></dd></div>
        </dl>
        <p>Freight &amp; duties quoted per destination</p>
      </div>
      <?php endif; ?>
      <table class="pk-table">
        <thead><tr><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
        <tbody>
          <?php
          // Same engine the configurator uses, so the table cannot drift from it.
          $ladder = [];
          if (pk_pricing_tables_ready()) {
              $ladder = PKQ_Pricing::ladder([
                  'style' => 'mailer',
                  'material' => 'kraft',
                  'print' => 'full-color',
                  'finish' => 'matte',
                  'length' => 30,
                  'width' => 22,
                  'height' => 12,
              ], PKQ_Plugin::settings());
          }
          foreach (array_slice($ladder, 0, 5) as $row) :
              ?>
            <tr>
              <td><?php echo esc_html(number_format_i18n($row['quantity'])); ?></td>
              <td><?php echo esc_html('$' . number_format((float) $row['unit'], 2)); ?></td>
              <td><?php echo esc_html('$' . number_format((float) $row['total'], 0)); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php $by_qty = []; foreach ($ladder as $r) $by_qty[(int)$r['quantity']] = $r['unit']; ?>
      <?php if (isset($by_qty[500],$by_qty[5000]) && $by_qty[500] > 0) : ?><p class="pk-fine"><?php echo esc_html(str_replace('%s', (string)round(100 * (1 - $by_qty[5000]/$by_qty[500])), pkv4_i18n_t('Freight & duties quoted per destination. At 5,000 pieces the unit price is %s% lower than at 500. Final price confirmed after dieline review.'))); ?></p><?php endif; ?>
    </div>
  </div>
</section>

<section class="pk-section pk-bordered">
  <div class="pk-wrap pk-split">
    <div>
      <p class="pk-eyebrow">Materials</p>
      <h2 class="pk-h2">Say what the box is made of, in numbers</h2>
      <p class="pk-lede">Recycled content, board weight, coating and end-of-life route are published per structure — because “eco-friendly” is not a specification.</p>
      <a class="pk-more" href="<?php echo esc_url(home_url('/customization/')); ?>">Materials and sustainability →</a>
    </div>
    <table class="pk-table pk-table-plain">
      <thead><tr><th>Board</th><th>Recycled</th><th>Use</th></tr></thead>
      <tbody>
        <tr><td>Kraft corrugated · E/B/F/EB/BC</td><td><bdi dir="ltr">70–90%</bdi></td><td>Mailers, shippers</td></tr>
        <tr><td>White corrugated</td><td><bdi dir="ltr">60%</bdi></td><td>Printed retail cartons</td></tr>
        <tr><td><?php echo $pk33a_ltr_values('Rigid greyboard · %1$s coated paper over %2$s greyboard', ['157g','600–2000g']); ?></td><td><?php echo $pk33a_ltr_values('%s recycled greyboard', ['100%']); ?></td><td>Rigid, wrapped</td></tr>
        <tr><td><?php echo $pk33a_ltr_values('Folding carton · %s', ['300–400 gsm']); ?></td><td>—</td><td>Folding boxes</td></tr>
        <tr><td>Molded pulp</td><td><bdi dir="ltr">100%</bdi></td><td>Inserts, trays · on request, mould quoted separately</td></tr>
        <tr><td>EVA / PU</td><td>—</td><td>Protective inserts</td></tr>
      </tbody>
    </table>
  </div>
</section>

<?php
$posts = get_posts(['numberposts' => 6, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC']);
if ($posts) :
    $post_count = (int) wp_count_posts('post')->publish;
?>
<section class="pk-section">
  <div class="pk-wrap">
    <div class="pk-section-head">
      <div>
        <p class="pk-eyebrow">Journal · <?php echo $post_count; ?> articles</p>
        <h2 class="pk-h2">Packaging notes from the floor</h2>
      </div>
      <a class="pk-more" href="<?php echo esc_url(get_permalink((int) get_option('page_for_posts'))); ?>">All <?php echo $post_count; ?> articles →</a>
    </div>
    <div class="pk-posts">
      <?php foreach ($posts as $post) : setup_postdata($post); ?>
        <a class="pk-post" href="<?php the_permalink(); ?>">
          <?php if (has_post_thumbnail()) : ?>
            <span class="pk-post-thumb"><?php the_post_thumbnail('medium', ['loading' => 'lazy']); ?></span>
          <?php endif; ?>
          <span class="pk-post-meta pk-mono">
            <?php
            $cat = get_the_category();
            echo esc_html(($cat ? $cat[0]->name . ' · ' : '') . get_the_date('M j, Y'));
            ?>
          </span>
          <span class="pk-post-title"><?php the_title(); ?></span>
        </a>
      <?php endforeach; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="pk-section pk-dark pk-samples">
  <div class="pk-wrap pk-split"><div><p class="pk-eyebrow pk-eyebrow-light">Samples</p><h2 class="pk-h2 pk-on-dark">Hold the board before you approve the board.</h2><p class="pk-lede pk-on-dark-muted">See the structure, board and finish in hand before you commit to a run.</p><a class="pk-btn pk-btn-light" href="<?php echo esc_url(home_url('/samples/')); ?>">Order a sample</a> <a class="pk-more pk-on-dark" href="<?php echo esc_url($contact_url); ?>">Ask a question</a></div><div class="pk-sample-grid"><div>Ring &amp; necklace boxes / Rigid, magnetic, drawer</div><div>Jewelry pouches / Velvet, cotton, suede</div><div>Gift boxes / Lid-and-base, window</div><div>Mailer boxes / Printed inside and out</div></div></div>
</section>

<section class="pk-section pk-bordered">
  <div class="pk-wrap pk-split">
    <div>
      <p class="pk-eyebrow">Questions</p>
      <h2 class="pk-h2">Before you ask</h2>
      <a class="pk-more" href="<?php echo esc_url($contact_url); ?>">Ask a specific question →</a>
    </div>
    <div class="pk-faq">
      <?php foreach ($faq as [$q, $a]) : ?>
        <details><summary><?php echo esc_html($q); ?></summary><p><?php echo esc_html($a); ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="pk-cta-band">
  <div class="pk-wrap pk-cta-inner">
    <div>
      <h2 class="pk-h2 pk-on-dark">Start with the size you already know.</h2>
      <p class="pk-lede pk-on-dark-muted">Five steps, live pricing, one business day reply.</p>
    </div>
    <a class="pk-btn pk-btn-light" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Build a quote</a>
  </div>
</section>

</main>
<script type="application/ld+json" class="pk33a-faq-data">
<?php
// Organization is already emitted by the parent (remediation-functions.php, with an
// @id and the real contact email). Emitting a second one here created two competing
// entities, so only FAQPage is added from this template.
echo wp_json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'FAQPage',
            // 外语页的可见问答是译文，schema 也必须给同样的译文，否则结构与页面不一致。
            'mainEntity' => array_map(function ($row) {
                $q = function_exists('pkv4_schema_text') ? pkv4_schema_text($row[0]) : $row[0];
                $a = function_exists('pkv4_schema_text') ? pkv4_schema_text($row[1]) : $row[1];
                return [
                    '@type' => 'Question',
                    'name' => $q,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
                ];
            }, $faq),
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
?>
</script>

<?php get_footer(); ?>
