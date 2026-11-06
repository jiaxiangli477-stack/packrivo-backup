<?php
/**
 * Plugin Name: Packrivo Language Selector
 * Description: Accessible language selector for Packrivo localized routes.
 * Version: 1.0.10
 */
if (!defined('ABSPATH')) { exit; }
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('packrivo-language-selector', plugins_url('selector.css', __FILE__), [], (string) filemtime(__DIR__ . '/selector.css'));
    wp_enqueue_script('packrivo-language-selector', plugins_url('selector-v1.0.7.js', __FILE__), [], '1.0.10', true);

    $aliases = [
        'de' => 'Germany Deutschland Austria Switzerland',
        'fr' => 'France Canada Belgium Switzerland',
        'es' => 'Spain Mexico Latin America',
        'it' => 'Italy',
        'nl' => 'Netherlands Belgium Holland',
        'pt' => 'Brazil Portugal',
        'ar' => 'Saudi Arabia UAE Gulf Egypt',
        'ja' => 'Japan',
        'ko' => 'Korea',
        'zh-CN' => 'China Simplified Chinese',
        'zh-TW' => 'Taiwan Hong Kong Traditional Chinese',
    ];
    $rows = [[
        'code' => 'en', 'label' => 'English', 'english' => 'English',
        'dir' => 'ltr', 'aliases' => 'USA UK United States International',
    ]];
    $ready = function_exists('packrivo_i18n_ready') ? packrivo_i18n_ready() : [];
    foreach ($ready as $code => $meta) {
        $rows[] = [
            'code' => (string) $code,
            'label' => (string) ($meta['label'] ?? $code),
            'english' => (string) ($meta['english'] ?? $code),
            'dir' => (string) ($meta['dir'] ?? 'ltr'),
            'aliases' => (string) ($aliases[$code] ?? ''),
        ];
    }
    $selector_data = [
        'languages' => $rows,
        'paths' => function_exists('packrivo_i18n_paths') ? packrivo_i18n_paths() : ['/'],
        'current' => function_exists('packrivo_lang') ? (packrivo_lang() ?: 'en') : 'en',
    ];
    $inline = 'window.PackrivoLanguageSelector = ' . wp_json_encode(
        $selector_data,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) . ';';
    wp_add_inline_script('packrivo-language-selector', $inline, 'before');
});
add_action('wp_footer', function () {
    ?>
    <div id="packrivo-language-root" class="notranslate" translate="no">
      <button id="packrivo-language-trigger" type="button" aria-haspopup="dialog" aria-controls="packrivo-language-dialog" aria-label="Choose language" aria-expanded="false">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a18 18 0 0 1 0 18 18 18 0 0 1 0-18Z"/></svg>
        <span id="packrivo-language-code">EN</span><svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg>
      </button>
      <dialog id="packrivo-language-dialog" aria-labelledby="packrivo-language-title">
        <div class="plh-heading"><div><h2 id="packrivo-language-title">Choose your language</h2><p>Read Packrivo in your preferred language.</p></div><button type="button" id="packrivo-language-close" aria-label="Close language selector"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15"/></svg></button></div>
        <label for="packrivo-language-search">Find a language or country</label>
        <input id="packrivo-language-search" type="search" placeholder="Search: Français, Germany, 日本語…" autocomplete="off">
        <div id="packrivo-language-list"><?php
          $path = function_exists('packrivo_current_en_path') ? packrivo_current_en_path() : null;
          $path = is_string($path) ? $path : '/';
          $available = function_exists('packrivo_i18n_ready') ? packrivo_i18n_ready() : [];
          echo '<a href="' . esc_url(home_url($path)) . '" hreflang="en" lang="en" data-lang="en">English</a>';
          foreach ($available as $code => $meta) {
              echo '<a href="' . esc_url(home_url('/' . $code . $path)) . '" hreflang="' . esc_attr($code) . '" lang="' . esc_attr($code) . '" data-lang="' . esc_attr($code) . '">' . esc_html($meta['label']) . '</a>';
          }
        ?></div><p id="packrivo-language-empty" hidden>No matching languages. Try another name.</p>
        <div class="plh-footer"><p>Translations may contain errors. Verify names and technical specifications against the English source.</p></div>
      </dialog>
      <p id="packrivo-language-status" role="status" aria-live="polite"></p>
    </div>
    <?php
}, 99);
