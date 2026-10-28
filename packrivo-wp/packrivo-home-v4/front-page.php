<?php
/**
 * Front page v4 — same IA as the live site, new presentation.
 * Sections: hero · categories · how it works · price transparency · materials · journal · FAQ · CTA
 */
get_header();

$img = get_stylesheet_directory_uri() . '/assets/img';

$home_specs = [
    ['product-drawer-jewelry-box','Jewelry boxes','Custom Drawer Jewelry Boxes'],
    ['product-magnetic-jewelry-box','Jewelry boxes','Custom Magnetic Jewelry Boxes'],
    ['product-lid-and-base-jewelry-box','Jewelry boxes','Custom Jewelry Boxes'],
    ['product-window-lid-rigid-gift-box','Gift boxes','Custom Window Gift Boxes'],
    ['product-corrugated-roll-end-hinged-lid','Mailer boxes','Custom Mailer Boxes'],
    ['product-sheet-labels','Labels','Custom Sheet Labels'],
];
$home_products = [];
foreach ($home_specs as [$slug,$group,$label]) {
    $page = get_page_by_path($slug);
    if (!$page || $page->post_status !== 'publish') continue;
    $cover = get_the_post_thumbnail_url($page,'medium_large') ?: get_post_meta($page->ID,'_packrivo_article_cover_url',true);
    $price = pk_from_price($page->ID);
    if ($cover && $price !== null) $home_products[] = ['product'=>$page,'group'=>$group,'title'=>$label,'price'=>$price];
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
    ['What is the minimum order?', 'MOQ 500 pieces per design for printed boxes. Unprinted stock ships from 100 pieces.'],
    ['How much does a custom box cost?', $faq_price_answer],
    ['How long does production take?', 'Stock styles ship in 1–15 days. Printed custom orders are scheduled after sample approval, usually 7–30 days depending on structure and finishing.'],
    ['Do you handle the whole supply chain?', 'We coordinate sampling, production, inspection and freight with vetted suppliers, and charge an 8–15% sourcing fee on the order value.'],
    ['Where do you ship from?', 'Shenzhen, China. DDP to the US and EU is standard; FOB is available if you consolidate.'],
];
?>
<main id="main" class="pk-main">
<section class="pk-hero">
  <div class="pk-wrap pk-hero-grid">
    <div>
      <p class="pk-eyebrow">Custom packaging · MOQ 500 · 11 languages</p>
      <h1 class="pk-display">Custom packaging for <span class="pk-accent">jewelry, candles</span> &amp; gifts.</h1>
      <p class="pk-lede">Sampling, production and freight handled end to end. Reference prices published, dielines included, and a person on the other end of the quote — not a form that goes nowhere.</p>
      <div class="pk-hero-cta">
        <a class="pk-btn" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Build a quote <span aria-hidden="true">→</span></a>
        <a class="pk-btn-ghost" href="<?php echo esc_url(home_url('/samples/')); ?>">Order a sample kit</a>
      </div>
      <dl class="pk-stats">
        <?php if ($home_products) : ?><div><dt>From</dt><dd>$<?php echo esc_html(number_format(min(array_column($home_products,'price')),2)); ?> / unit</dd></div><?php endif; ?>
        <div><dt>MOQ</dt><dd>500 / design</dd></div>
        <div><dt>Production</dt><dd>7–30 days</dd></div>
        <div><dt>Sourcing fee</dt><dd>8–15%</dd></div>
      </dl>
    </div>
    <figure class="pk-hero-media">
      <img src="<?php echo esc_url($img . '/hero.webp'); ?>" alt="Rigid gift box with insert, studio shot" width="1200" height="1200" loading="eager" />
      <figcaption class="pk-hero-card">
        <p class="pk-mono">Rigid magnetic box · 9.5×9.5×4 cm</p>
        <?php
        // Priced by the same engine as the configurator, at 1,000 units.
        $pk_hero_unit = null;
        if (pk_pricing_tables_ready()) {
            $pk_hero = PKQ_Pricing::price([
                'style' => 'rigid',
                'material' => 'rigidboard',
                'print' => 'full-color',
                'finish' => 'matte',
                'quantity' => 1000,
                'length' => 9.5,
                'width' => 9.5,
                'height' => 4,
            ], PKQ_Plugin::settings());
            if (!is_wp_error($pk_hero)) {
                $pk_hero_unit = (float) $pk_hero['unit'];
            }
        }
        ?>
        <?php if ($pk_hero_unit !== null) : ?><p class="pk-price">$<?php echo esc_html(number_format($pk_hero_unit, 2)); ?></p><?php endif; ?>
        <p class="pk-mono pk-muted">per unit at 1,000 pieces</p>
      </figcaption>
    </figure>
  </div>
</section>

<section class="pk-section pk-bordered">
  <div class="pk-wrap">
    <div class="pk-section-head">
      <div>
        <p class="pk-eyebrow">What we make</p>
        <h2 class="pk-h2">Custom jewelry boxes, pouches and gift boxes with your logo</h2>
      </div>
      <a class="pk-more" href="<?php echo esc_url(home_url('/products/')); ?>">All products →</a>
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
      <?php foreach ($steps as [$num, $title, $body]) : ?>
        <li>
          <span class="pk-step-num"><?php echo esc_html($num); ?></span>
          <span class="pk-step-title"><?php echo esc_html($title); ?></span>
          <span class="pk-step-text"><?php echo esc_html($body); ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
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
        <?php if (isset($pk_tables['rules']['freePrintSetupFrom'])) : ?><li><strong>Setup is itemised</strong> — and waived above <?php echo esc_html(number_format_i18n($pk_tables['rules']['freePrintSetupFrom'])); ?> printed pieces.</li><?php endif; ?>
        <li><strong>Freight is separate</strong> — quoted per shipment, never buried in the unit price.</li>
        <li><strong>No tooling surprise</strong> — one-time plate and die cost, shown before you submit.</li>
      </ul>
      <a class="pk-btn" href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Open the quote builder</a>
    </div>
    <div class="pk-table-card">
      <p class="pk-mono pk-muted">Mailer box · 30×22×12 cm · kraft · full colour</p>
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
      <?php if (isset($by_qty[500],$by_qty[5000]) && $by_qty[500] > 0) : ?><p class="pk-fine">Indicative, excludes freight. At 5,000 pieces the unit price is <?php echo esc_html(round(100 * (1 - $by_qty[5000]/$by_qty[500]))); ?>% lower than at 500. Final price confirmed after dieline review.</p><?php endif; ?>
    </div>
  </div>
</section>

<section class="pk-section pk-bordered">
  <div class="pk-wrap pk-split">
    <div>
      <p class="pk-eyebrow">Materials</p>
      <h2 class="pk-h2">Say what the box is made of, in numbers</h2>
      <p class="pk-lede">Recycled content, board weight, coating and end-of-life route are published per structure — because “eco-friendly” is not a specification.</p>
      <a class="pk-more" href="<?php echo esc_url(home_url('/sustainability/')); ?>">Materials and sustainability →</a>
    </div>
    <table class="pk-table pk-table-plain">
      <thead><tr><th>Board</th><th>Recycled</th><th>Use</th></tr></thead>
      <tbody>
        <tr><td>Kraft E-flute</td><td>70–90%</td><td>Mailers, shippers</td></tr>
        <tr><td>White E-flute</td><td>60%</td><td>Printed retail cartons</td></tr>
        <tr><td>Greyboard 2 mm</td><td>100%</td><td>Rigid, wrapped</td></tr>
        <tr><td>Molded pulp</td><td>100%</td><td>Inserts, trays</td></tr>
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
  <div class="pk-wrap pk-split"><div><p class="pk-eyebrow pk-eyebrow-light">Samples</p><h2 class="pk-h2 pk-on-dark">Hold the board before you approve the board.</h2><p class="pk-lede pk-on-dark-muted">See the structure, board and finish in hand before you commit to a run.</p><a class="pk-btn pk-btn-light" href="<?php echo esc_url(home_url('/samples/')); ?>">Order a sample</a> <a class="pk-more pk-on-dark" href="<?php echo esc_url($contact_url); ?>">Ask a question</a></div><div class="pk-sample-grid"><div>Rigid / Greyboard 2 mm</div><div>Mailer / Kraft E-flute</div><div>Drawer / Rigid board</div><div>Pouch / Fabric</div></div></div>
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

<script type="application/ld+json">
<?php
// Organization is already emitted by the parent (remediation-functions.php, with an
// @id and the real contact email). Emitting a second one here created two competing
// entities, so only FAQPage is added from this template.
echo wp_json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(function ($row) {
                return [
                    '@type' => 'Question',
                    'name' => $row[0],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $row[1]],
                ];
            }, $faq),
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
</script>
</main>

<?php get_footer(); ?>
