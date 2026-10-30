<?php
/**
 * Header v4 — clickable top-level links + separately toggleable panels.
 * The live theme's bug: the top-level word only opened on hover, so a click did nothing.
 */
$shop_url = home_url('/products/');
// 语种页面上 mu-plugin 已把 REQUEST_URI 改写成英文路径，home_url() 只会给出英文地址，
// 所以报价按钮的语种前缀要在这里自己加。
$pk_lang_prefix = function_exists('packrivo_lang') && packrivo_lang() !== null ? '/' . packrivo_lang() : '';
$quote_url = home_url($pk_lang_prefix . '/get-a-quote/');
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php wp_head(); ?>
</head>
<body <?php body_class('pk-body'); ?>>
<?php wp_body_open(); ?>

<a class="pk-skip" href="#main">Skip to content</a>

<header class="pk-header" data-pk-header>
  <div class="pk-hd-inner">

    <a class="pk-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> home">
      <img class="pk-brand-mark" src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/mark.svg'); ?>" alt="" width="30" height="30" />
      <span class="pk-brand-word">Packrivo</span>
    </a>

    <button class="pk-burger" type="button" aria-expanded="false" aria-controls="pk-nav" data-pk-burger>
      <span class="pk-burger-lines" aria-hidden="true"></span>
      <span class="pk-burger-text">Menu</span>
    </button>

    <nav class="pk-nav" id="pk-nav" aria-label="Primary">
      <ul class="pk-nav-list">

        <li class="pk-has-panel">
          <a class="pk-nav-link" href="<?php echo esc_url($shop_url); ?>">Products</a>
          <button class="pk-caret" type="button" aria-expanded="false" aria-controls="pk-panel-products" aria-label="Open Products menu" data-pk-toggle>
            <svg width="10" height="6" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
          </button>
          <div class="pk-panel" id="pk-panel-products" hidden>
            <div class="pk-panel-grid">
              <?php foreach (pkv4_product_groups() as $group) : ?>
                <div class="pk-panel-col">
                  <p class="pk-panel-title"><?php echo esc_html($group['title']); ?></p>
                  <ul>
                    <?php foreach ($group['links'] as [$label, $path]) : ?>
                      <li><a href="<?php echo esc_url(home_url($path)); ?>"><?php echo esc_html($label); ?></a></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endforeach; ?>
            </div>
            <p class="pk-panel-note">Every structure is priced from 500 units. Dielines included.</p>
          </div>
        </li>

        <li><a class="pk-nav-link" href="<?php echo esc_url(home_url('/samples/')); ?>">Samples</a></li>
        <li><a class="pk-nav-link" href="<?php echo esc_url(home_url('/customization/')); ?>">Customization</a></li>
        <li><a class="pk-nav-link" href="<?php echo esc_url(home_url('/services/')); ?>">Services</a></li>

        <li class="pk-has-panel">
          <a class="pk-nav-link" href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a>
          <button class="pk-caret" type="button" aria-expanded="false" aria-controls="pk-panel-blog" aria-label="Open Blog menu" data-pk-toggle>
            <svg width="10" height="6" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
          </button>
          <div class="pk-panel pk-panel-sm" id="pk-panel-blog" hidden>
            <?php
            // Slugs must come from the database — the previous version built them
            // from category names, which matched no term and returned zero posts.
            $pk_blog = (int) get_option('page_for_posts');
            $pk_base = $pk_blog ? get_permalink($pk_blog) : home_url('/blog/');
            ?>
            <ul class="pk-panel-list">
              <li>
                <a href="<?php echo esc_url($pk_base); ?>">
                  All articles <span><?php echo (int) wp_count_posts('post')->publish; ?></span>
                </a>
              </li>
              <?php foreach (get_categories(['hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC']) as $pk_cat) : ?>
                <li>
                  <a href="<?php echo esc_url(add_query_arg('topic', $pk_cat->slug, $pk_base)); ?>">
                    <?php echo esc_html($pk_cat->name); ?> <span><?php echo (int) $pk_cat->count; ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </li>

        <li class="pk-has-panel">
          <a class="pk-nav-link" href="<?php echo esc_url(home_url('/blog/')); ?>">Resources</a>
          <button class="pk-caret" type="button" aria-expanded="false" aria-controls="pk-panel-resources" aria-label="Open Resources menu" data-pk-toggle>
            <svg width="10" height="6" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
          </button>
          <div class="pk-panel pk-panel-sm" id="pk-panel-resources" hidden>
            <ul class="pk-panel-list">
              <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Guides</a></li>
              <li><a href="<?php echo esc_url(home_url('/compare/')); ?>">Compare formats</a></li>
              <li><a href="<?php echo esc_url(home_url('/get-a-quote/')); ?>">Price calculator</a></li>
            </ul>
          </div>
        </li>

        <li><a class="pk-nav-link" href="<?php echo esc_url(home_url('/about/')); ?>">About</a></li>
      </ul>
    </nav>

    <div class="pk-actions">
      <a class="pk-icon-btn" href="<?php echo esc_url(home_url('/?s=')); ?>" aria-label="Search">
        <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M11 11l3.5 3.5" stroke="currentColor" stroke-width="1.4"/></svg>
      </a>
      <span class="pk-lang">EN</span>
      <a class="pk-text-link" href="<?php echo esc_url(home_url('/samples/')); ?>">Sample kit</a>
      <a class="pk-text-link" href="<?php echo esc_url($quote_url); ?>">Instant quote</a>
      <a class="pk-cta" href="<?php echo esc_url($quote_url); ?>">Build a quote</a>
    </div>
  </div>
</header>
