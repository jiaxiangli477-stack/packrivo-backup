<?php
/**
 * Plugin Name:       Packrivo Quote & Order
 * Plugin URI:        https://www.packrivo.com/
 * Description:       五步配置器：结构 → 尺寸 → 材料/工艺 → 数量 → 下单。前端实时报价，服务端重算价格后入库，后台可查单、导出 CSV。
 * Version:           1.0.1
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Packrivo
 * Text Domain:       packrivo-quote
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PKQ_VERSION', '1.0.1');
define('PKQ_FILE', __FILE__);
define('PKQ_DIR', plugin_dir_path(__FILE__));
define('PKQ_URL', plugin_dir_url(__FILE__));

require_once PKQ_DIR . 'includes/class-pkq-pricing.php';
require_once PKQ_DIR . 'includes/class-pkq-rest.php';
require_once PKQ_DIR . 'includes/class-pkq-admin.php';

final class PKQ_Plugin
{
    const OPTION = 'pkq_settings';

    public static function boot()
    {
        add_action('init', [__CLASS__, 'register_order_type']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
        add_shortcode('packrivo_quote', [__CLASS__, 'render_shortcode']);
        add_action('rest_api_init', ['PKQ_REST', 'register_routes']);
        PKQ_Admin::hooks();
    }

    public static function defaults()
    {
        return [
            'currency' => 'USD',
            'admin_email' => get_option('admin_email'),
            'notify_customer' => 0,
            'min_qty' => 500,
            'max_qty' => 250000,
            'setup_print' => 220,
            'setup_die' => 120,
            'rush_percent' => 22,
            // Style bases come from the generated pricing file so the plugin and the
            // Next build never drift apart. Admin overrides still live in the option.
            'styles' => self::generated_styles(),
        ];
    }

    private static function generated_styles()
    {
        $fallback = ['mailer' => ['label' => 'Roll-end mailer', 'base' => 1.42]];
        $file = PKQ_DIR . 'includes/pricing.json';
        if (!is_readable($file)) {
            return $fallback;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || empty($data['styles'])) {
            return $fallback;
        }
        return array_map(function ($row) {
            return ['label' => (string) $row['label'], 'base' => (float) $row['base']];
        }, $data['styles']);
    }

    public static function settings()
    {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        $settings = array_merge(self::defaults(), $saved);
        if (isset($saved['styles']) && is_array($saved['styles'])) {
            $settings['styles'] = $saved['styles'];
        }
        return $settings;
    }

    public static function register_order_type()
    {
        register_post_type('pkq_order', [
            'labels' => [
                'name' => __('Quote orders', 'packrivo-quote'),
                'singular_name' => __('Quote order', 'packrivo-quote'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-cart',
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => ['create_posts' => 'do_not_allow'],
            'map_meta_cap' => true,
        ]);
    }

    public static function register_assets()
    {
        wp_register_style('pkq-quote', PKQ_URL . 'assets/pkq-quote.css', [], PKQ_VERSION);
        wp_register_script('pkq-quote', PKQ_URL . 'assets/pkq-quote.js', [], PKQ_VERSION, true);
    }

    public static function render_shortcode($atts = [])
    {
        $settings = self::settings();
        wp_enqueue_style('pkq-quote');
        wp_enqueue_script('pkq-quote');

        $pricing = PKQ_Pricing::tables();

        wp_localize_script('pkq-quote', 'PKQ', [
            'endpoint' => esc_url_raw(rest_url('packrivo/v1/order')),
            'nonce' => wp_create_nonce('pkq_order'),
            // Logged-in users also have to satisfy rest_cookie_check_errors().
            'wpNonce' => wp_create_nonce('wp_rest'),
            'currency' => $settings['currency'],
            'minQty' => (int) $settings['min_qty'],
            'maxQty' => (int) $settings['max_qty'],
            'setupPrint' => (float) $settings['setup_print'],
            'setupDie' => (float) $settings['setup_die'],
            'rushPercent' => (float) $settings['rush_percent'],
            // The browser must price with the same table the server does, otherwise
            // a config change in pricing.json silently desyncs the two.
            'tiers' => $pricing['tiers'],
            'sizeRef' => $pricing['sizeRef'],
            'rules' => $pricing['rules'],
            'tierFloor' => $pricing['rules']['tierFloor'],
            'materials' => $pricing['materials'],
            'prints' => $pricing['prints'],
            'finishes' => $pricing['finishes'],
            'styles' => array_map(function ($s) {
                return ['label' => (string) $s['label'], 'base' => (float) $s['base']];
            }, $settings['styles']),
            'i18n' => [
                'title' => __('Build your quote', 'packrivo-quote'),
                'sub' => __('Five steps. Live pricing. An engineer confirms the dieline before anything is cut.', 'packrivo-quote'),
                'submit' => __('Request this quote', 'packrivo-quote'),
                'sending' => __('Sending…', 'packrivo-quote'),
                'done' => __('Quote requested', 'packrivo-quote'),
                'error' => __('Something went wrong. Please email us instead.', 'packrivo-quote'),
            ],
        ]);

        $atts = shortcode_atts(['style' => '', 'qty' => '', 'product' => ''], $atts, 'packrivo_quote');
        $style_input = $atts['style'] !== '' ? $atts['style'] : ($_GET['style'] ?? '');
        $qty_input = $atts['qty'] !== '' ? $atts['qty'] : ($_GET['qty'] ?? '');
        $product_input = $atts['product'] !== '' ? $atts['product'] : ($_GET['product'] ?? '');
        $style = is_scalar($style_input) ? sanitize_text_field(wp_unslash($style_input)) : '';
        if (!isset($settings['styles'][$style])) {
            $style = '';
        }
        $qty = is_scalar($qty_input) && $qty_input !== '' ? max((int) $settings['min_qty'], min((int) $settings['max_qty'], (int) $qty_input)) : 0;
        $product = is_scalar($product_input) ? sanitize_title(wp_unslash($product_input)) : '';

        return sprintf(
            '<div class="pkq-root" data-style="%s" data-qty="%d" data-product="%s"><noscript>%s</noscript></div>',
            esc_attr($style),
            $qty,
            esc_attr($product),
            esc_html__('This configurator needs JavaScript. Email hello@packrivo.com and we will quote manually.', 'packrivo-quote')
        );
    }
}

register_activation_hook(__FILE__, function () {
    if (!get_option(PKQ_Plugin::OPTION)) {
        add_option(PKQ_Plugin::OPTION, PKQ_Plugin::defaults());
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('plugins_loaded', ['PKQ_Plugin', 'boot']);
