<?php
/**
 * Admin: order list columns, detail box, CSV export, settings page.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class PKQ_Admin
{
    public static function hooks()
    {
        add_filter('manage_pkq_order_posts_columns', [__CLASS__, 'columns']);
        add_action('manage_pkq_order_posts_custom_column', [__CLASS__, 'column_values'], 10, 2);
        add_action('add_meta_boxes', [__CLASS__, 'meta_box']);
        add_action('save_post_pkq_order', [__CLASS__, 'save_status'], 10, 2);
        add_action('admin_post_pkq_export', [__CLASS__, 'export_csv']);
        add_action('admin_menu', [__CLASS__, 'settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_post_pkq_save_settings', [__CLASS__, 'save_settings']);
    }

    public static function statuses()
    {
        return [
            'new' => __('New', 'packrivo-quote'),
            'contacted' => __('Contacted', 'packrivo-quote'),
            'quoted' => __('Quoted', 'packrivo-quote'),
            'won' => __('Won', 'packrivo-quote'),
            'lost' => __('Lost', 'packrivo-quote'),
        ];
    }

    /** The list column promised a status field, but nothing wrote to it. */
    public static function save_status($post_id, $post)
    {
        if (!isset($_POST['pkq_status_nonce']) || !wp_verify_nonce(sanitize_key($_POST['pkq_status_nonce']), 'pkq_status_' . $post_id)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id) || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        $value = isset($_POST['pkq_status']) ? sanitize_key($_POST['pkq_status']) : '';
        if (isset(self::statuses()[$value])) {
            update_post_meta($post_id, 'pkq_status', $value);
        }
    }

    public static function columns($cols)
    {
        return [
            'cb' => $cols['cb'],
            'title' => __('Reference', 'packrivo-quote'),
            'pkq_company' => __('Company', 'packrivo-quote'),
            'pkq_qty' => __('Qty', 'packrivo-quote'),
            'pkq_total' => __('Total', 'packrivo-quote'),
            'pkq_goods_total' => __('Goods total', 'packrivo-quote'),
            'pkq_fee' => __('Sourcing fee', 'packrivo-quote'),
            'pkq_status' => __('Status', 'packrivo-quote'),
            'date' => __('Date', 'packrivo-quote'),
        ];
    }

    public static function column_values($col, $post_id)
    {
        $map = [
            'pkq_company' => 'pkq_company',
            'pkq_status' => 'pkq_status',
        ];
        if (isset($map[$col])) {
            echo esc_html((string) get_post_meta($post_id, $map[$col], true));
            return;
        }
        if ($col === 'pkq_qty') {
            echo esc_html(number_format_i18n((int) get_post_meta($post_id, 'pkq_quantity', true)));
            return;
        }
        if ($col === 'pkq_goods_total' || $col === 'pkq_fee') {
            $value = get_post_meta($post_id, $col, true);
            echo $value === '' ? '—' : esc_html(number_format((float) $value, 2));
            return;
        }
        if ($col === 'pkq_total') {
            $unit = (float) get_post_meta($post_id, 'pkq_unit', true);
            $total = (float) get_post_meta($post_id, 'pkq_total', true);
            echo esc_html(sprintf('%s / unit · %s', number_format($unit, 2), number_format($total, 2)));
        }
    }

    public static function meta_box()
    {
        add_meta_box('pkq_order_detail', __('Quote detail', 'packrivo-quote'), [__CLASS__, 'render_meta_box'], 'pkq_order', 'normal', 'high');
        add_meta_box('pkq_order_status', __('Pipeline status', 'packrivo-quote'), [__CLASS__, 'render_status_box'], 'pkq_order', 'side', 'high');
    }

    public static function render_status_box($post)
    {
        $current = (string) get_post_meta($post->ID, 'pkq_status', true);
        wp_nonce_field('pkq_status_' . $post->ID, 'pkq_status_nonce');
        echo '<p><select name="pkq_status" style="width:100%">';
        foreach (self::statuses() as $key => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($current, $key, false), esc_html($label));
        }
        echo '</select></p>';
        echo '<p class="description">' . esc_html__('Update after you reply to the buyer.', 'packrivo-quote') . '</p>';
    }

    public static function render_meta_box($post)
    {
        $fields = [
            'pkq_ref' => 'Reference',
            'pkq_name' => 'Name',
            'pkq_email' => 'Email',
            'pkq_company' => 'Company',
            'pkq_country' => 'Country',
            'pkq_phone' => 'Phone',
            'pkq_style' => 'Style',
            'pkq_material' => 'Material',
            'pkq_print' => 'Print',
            'pkq_finish' => 'Finish',
            'pkq_quantity' => 'Quantity',
            'pkq_unit' => 'Unit price',
            'pkq_total' => 'Order total',
            'pkq_goods_total' => 'Goods total',
            'pkq_fee' => 'Sourcing fee',
            'pkq_setup' => 'Setup',
            'pkq_notes' => 'Notes',
        ];
        echo '<table class="widefat striped"><tbody>';
        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            if ($key === 'pkq_notes') {
                $value = nl2br(esc_html((string) $value));
            } else {
                $value = esc_html((string) $value);
            }
            printf('<tr><th style="width:160px">%s</th><td>%s</td></tr>', esc_html($label), $value);
        }
        $dims = sprintf(
            '%s × %s × %s cm',
            get_post_meta($post->ID, 'pkq_length', true),
            get_post_meta($post->ID, 'pkq_width', true),
            get_post_meta($post->ID, 'pkq_height', true)
        );
        printf('<tr><th>Size</th><td>%s</td></tr>', esc_html($dims));
        echo '</tbody></table>';
    }

    public static function export_csv()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden', 403);
        }
        check_admin_referer('pkq_export');

        $posts = get_posts([
            'post_type' => 'pkq_order',
            'numberposts' => -1,
            'post_status' => 'any',
        ]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=packrivo-orders-' . gmdate('Y-m-d') . '.csv');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['reference', 'created', 'name', 'email', 'company', 'country', 'phone', 'style', 'material', 'print', 'finish', 'quantity', 'length', 'width', 'height', 'rush', 'unit', 'total', 'setup', 'status', 'notes', 'goods_total', 'fee']);
        foreach ($posts as $p) {
            $get = function ($k) use ($p) {
                return get_post_meta($p->ID, $k, true);
            };
            // Excel runs a cell that starts with = + - @ as a formula. Customer
            // fields land straight in the CSV, so neutralise them first.
            $cell = function ($v) {
                $v = (string) $v;
                return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
            };
            fputcsv($out, [
                $cell($get('pkq_ref')),
                $p->post_date_gmt,
                $cell($get('pkq_name')),
                $cell($get('pkq_email')),
                $cell($get('pkq_company')),
                $cell($get('pkq_country')),
                $cell($get('pkq_phone')),
                $cell($get('pkq_style')),
                $cell($get('pkq_material')),
                $cell($get('pkq_print')),
                $cell($get('pkq_finish')),
                $get('pkq_quantity'),
                $get('pkq_length'),
                $get('pkq_width'),
                $get('pkq_height'),
                $get('pkq_rush'),
                $get('pkq_unit'),
                $get('pkq_total'),
                $get('pkq_setup'),
                $cell($get('pkq_status')),
                $cell(str_replace(["\r", "\n"], ' ', (string) $get('pkq_notes'))),
                $get('pkq_goods_total'),
                $get('pkq_fee'),
            ]);
        }
        fclose($out);
        exit;
    }

    public static function settings_page()
    {
        add_submenu_page(
            'options-general.php',
            __('Packrivo Quote', 'packrivo-quote'),
            __('Packrivo Quote', 'packrivo-quote'),
            'manage_options',
            'pkq-settings',
            [__CLASS__, 'render_settings']
        );
    }

    public static function register_settings()
    {
        register_setting('pkq', PKQ_Plugin::OPTION, ['type' => 'array']);
    }

    public static function render_settings()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = PKQ_Plugin::settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Packrivo quote settings', 'packrivo-quote'); ?></h1>
            <p>
                <?php esc_html_e('Prices here drive both the browser preview and the server-side recompute. Keep them in sync with your real cost sheet.', 'packrivo-quote'); ?>
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('pkq_save_settings'); ?>
                <input type="hidden" name="action" value="pkq_save_settings" />
                <table class="form-table">
                    <tr>
                        <th><label for="pkq_currency"><?php esc_html_e('Currency label', 'packrivo-quote'); ?></label></th>
                        <td><input id="pkq_currency" name="currency" value="<?php echo esc_attr($s['currency']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="pkq_admin_email"><?php esc_html_e('Notify email', 'packrivo-quote'); ?></label></th>
                        <td><input id="pkq_admin_email" name="admin_email" type="email" value="<?php echo esc_attr($s['admin_email']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Reply to customer', 'packrivo-quote'); ?></th>
                        <td><label><input type="checkbox" name="notify_customer" value="1" <?php checked(!empty($s['notify_customer'])); ?> /> <?php esc_html_e('Send an acknowledgement email to the buyer', 'packrivo-quote'); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Quantity range', 'packrivo-quote'); ?></th>
                        <td>
                            <input name="min_qty" type="number" value="<?php echo esc_attr((int) $s['min_qty']); ?>" class="small-text" /> –
                            <input name="max_qty" type="number" value="<?php echo esc_attr((int) $s['max_qty']); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Setup fees', 'packrivo-quote'); ?></th>
                        <td>
                            <?php esc_html_e('Print plates', 'packrivo-quote'); ?>
                            <input name="setup_print" type="number" step="0.01" value="<?php echo esc_attr($s['setup_print']); ?>" class="small-text" />
                            <?php esc_html_e('Die', 'packrivo-quote'); ?>
                            <input name="setup_die" type="number" step="0.01" value="<?php echo esc_attr($s['setup_die']); ?>" class="small-text" />
                            <?php esc_html_e('Rush %', 'packrivo-quote'); ?>
                            <input name="rush_percent" type="number" step="0.1" value="<?php echo esc_attr($s['rush_percent']); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Sourcing fee', 'packrivo-quote'); ?></th>
                        <td><label>Percent <input name="fee_percent" type="number" min="0" step="0.01" value="<?php echo esc_attr($s['fee_percent']); ?>" class="small-text" /></label>
                            <label>Minimum (USD) <input name="min_fee" type="number" min="0" step="0.01" value="<?php echo esc_attr($s['min_fee']); ?>" class="small-text" /></label></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Base price per style (unit, at 500 pcs)', 'packrivo-quote'); ?></h2>
                <table class="widefat striped" style="max-width:720px">
                    <thead><tr><th><?php esc_html_e('Style', 'packrivo-quote'); ?></th><th><?php esc_html_e('Key', 'packrivo-quote'); ?></th><th><?php esc_html_e('Base', 'packrivo-quote'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($s['styles'] as $key => $style) : ?>
                        <tr>
                            <td><input name="styles[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($style['label']); ?>" class="regular-text" /></td>
                            <td><code><?php echo esc_html($key); ?></code></td>
                            <td><input name="styles[<?php echo esc_attr($key); ?>][base]" type="number" step="0.01" value="<?php echo esc_attr($style['base']); ?>" class="small-text" /></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>

            <h2><?php esc_html_e('Export', 'packrivo-quote'); ?></h2>
            <p>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=pkq_export'), 'pkq_export')); ?>">
                    <?php esc_html_e('Download all orders as CSV', 'packrivo-quote'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    public static function save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden', 403);
        }
        check_admin_referer('pkq_save_settings');

        $current = PKQ_Plugin::settings();
        $styles = [];
        if (isset($_POST['styles']) && is_array($_POST['styles'])) {
            foreach (wp_unslash($_POST['styles']) as $key => $row) {
                $key = sanitize_key($key);
                if (!isset($current['styles'][$key])) {
                    continue;
                }
                $styles[$key] = [
                    'label' => sanitize_text_field($row['label'] ?? $current['styles'][$key]['label']),
                    'base' => (float) ($row['base'] ?? $current['styles'][$key]['base']),
                ];
            }
        }

        $new = [
            'currency' => sanitize_text_field($_POST['currency'] ?? $current['currency']),
            'admin_email' => sanitize_email($_POST['admin_email'] ?? $current['admin_email']),
            'notify_customer' => !empty($_POST['notify_customer']) ? 1 : 0,
            'min_qty' => max(1, (int) ($_POST['min_qty'] ?? $current['min_qty'])),
            'max_qty' => max(1, (int) ($_POST['max_qty'] ?? $current['max_qty'])),
            'setup_print' => (float) ($_POST['setup_print'] ?? $current['setup_print']),
            'setup_die' => (float) ($_POST['setup_die'] ?? $current['setup_die']),
            'rush_percent' => (float) ($_POST['rush_percent'] ?? $current['rush_percent']),
            'fee_percent' => max(0, (float) ($_POST['fee_percent'] ?? $current['fee_percent'])),
            'min_fee' => max(0, (float) ($_POST['min_fee'] ?? $current['min_fee'])),
            'styles' => $styles ?: $current['styles'],
        ];

        update_option(PKQ_Plugin::OPTION, $new);
        wp_safe_redirect(add_query_arg(['page' => 'pkq-settings', 'updated' => '1'], admin_url('options-general.php')));
        exit;
    }
}
