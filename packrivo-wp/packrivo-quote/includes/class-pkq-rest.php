<?php
/**
 * REST endpoint: POST /wp-json/packrivo/v1/order
 */

if (!defined('ABSPATH')) {
    exit;
}

final class PKQ_REST
{
    public static function register_routes()
    {
        register_rest_route('packrivo/v1', '/order', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create'],
            'permission_callback' => [__CLASS__, 'check_nonce'],
        ]);
    }

    /**
     * WordPress lets a request without a nonce through as anonymous — so a plain
     * cross-site form post would reach create() and write an order. Verify the
     * header we issue ourselves instead of relying on rest_cookie_check_errors().
     */
    public static function check_nonce(WP_REST_Request $request)
    {
        $nonce = $request->get_header('X-PKQ-Nonce');
        if (!is_string($nonce) || !wp_verify_nonce($nonce, 'pkq_order')) {
            return new WP_Error('pkq_nonce', 'Session expired. Reload the page and try again.', ['status' => 403]);
        }
        return true;
    }

    private static function rate_limited($ip)
    {
        $key = 'pkq_rl_' . md5($ip);
        $hits = (int) get_transient($key);
        if ($hits >= 20) {
            return true;
        }
        set_transient($key, $hits + 1, HOUR_IN_SECONDS);
        return false;
    }

    private static function client_ip()
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';

        // Only trust the forwarded chain when the direct peer is a private/loopback
        // address, i.e. our own proxy. Otherwise every visitor collapses onto the
        // proxy IP and the per-IP bucket becomes a site-wide bucket.
        $trusted = (bool) apply_filters('pkq_trust_proxy', false);
        if ($trusted && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
            $candidate = trim(end($parts));
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return $remote;
    }

    private static function sanitize_payload($request)
    {
        // JSON only — a form-encoded cross-site post bypasses CORS preflight.
        $p = $request->get_json_params();
        if (!is_array($p)) {
            $p = [];
        }
        $clean = [
            'style' => sanitize_text_field($p['style'] ?? ''),
            'material' => sanitize_text_field($p['material'] ?? ''),
            'print' => sanitize_text_field($p['print'] ?? ''),
            'finish' => sanitize_text_field($p['finish'] ?? ''),
            'quantity' => (int) round((float) ($p['quantity'] ?? 0)),
            'length' => (float) ($p['length'] ?? 0),
            'width' => (float) ($p['width'] ?? 0),
            'height' => (float) ($p['height'] ?? 0),
            'rush' => !empty($p['rush']),
            'name' => sanitize_text_field($p['name'] ?? ''),
            'email' => sanitize_email($p['email'] ?? ''),
            'company' => sanitize_text_field($p['company'] ?? ''),
            'country' => sanitize_text_field($p['country'] ?? ''),
            'phone' => sanitize_text_field($p['phone'] ?? ''),
            'notes' => sanitize_textarea_field($p['notes'] ?? ''),
            'website' => sanitize_text_field($p['website'] ?? ''),
        ];
        return $clean;
    }

    public static function quote_only(WP_REST_Request $request)
    {
        if (self::rate_limited(self::client_ip())) {
            return new WP_Error('pkq_rate', 'Too many requests.', ['status' => 429]);
        }
        $data = self::sanitize_payload($request);
        $priced = PKQ_Pricing::price($data, PKQ_Plugin::settings());
        if (is_wp_error($priced)) {
            return $priced;
        }
        return rest_ensure_response($priced);
    }

    public static function create(WP_REST_Request $request)
    {
        if (self::rate_limited(self::client_ip())) {
            return new WP_Error('pkq_rate', 'Too many requests. Email hello@packrivo.com.', ['status' => 429]);
        }

        $data = self::sanitize_payload($request);

        // Honeypot: bots fill hidden fields.
        if (!empty($data['website'])) {
            return new WP_Error('pkq_spam', 'Rejected.', ['status' => 400]);
        }

        if ($data['name'] === '' || mb_strlen($data['name']) > 120) {
            return new WP_Error('pkq_name', 'Please enter your name.', ['status' => 422]);
        }
        if (!is_email($data['email'])) {
            return new WP_Error('pkq_email', 'Please enter a valid work email.', ['status' => 422]);
        }
        if ($data['company'] === '') {
            return new WP_Error('pkq_company', 'Please enter your company.', ['status' => 422]);
        }

        $settings = PKQ_Plugin::settings();
        $priced = PKQ_Pricing::price($data, $settings);
        if (is_wp_error($priced)) {
            return $priced;
        }

        $ref = 'PKQ-' . strtoupper(wp_generate_password(5, false, false));
        $post_id = wp_insert_post([
            'post_type' => 'pkq_order',
            'post_status' => 'publish',
            'post_title' => $ref . ' · ' . $data['company'],
            'meta_input' => [
                'pkq_ref' => $ref,
                'pkq_name' => $data['name'],
                'pkq_email' => $data['email'],
                'pkq_company' => $data['company'],
                'pkq_country' => $data['country'],
                'pkq_phone' => $data['phone'],
                'pkq_notes' => $data['notes'],
                'pkq_style' => $data['style'],
                'pkq_material' => $data['material'],
                'pkq_print' => $data['print'],
                'pkq_finish' => $data['finish'],
                'pkq_quantity' => $priced['quantity'],
                'pkq_length' => $data['length'],
                'pkq_width' => $data['width'],
                'pkq_height' => $data['height'],
                'pkq_rush' => $data['rush'] ? 1 : 0,
                'pkq_unit' => $priced['unit'],
                'pkq_total' => $priced['total'],
                'pkq_setup' => $priced['setup'],
                'pkq_status' => 'new',
                'pkq_ip' => self::client_ip(),
            ],
        ], true);

        if (is_wp_error($post_id)) {
            return new WP_Error('pkq_store', 'Could not save the request.', ['status' => 500]);
        }

        self::notify($ref, $data, $priced, $settings, $post_id);

        return rest_ensure_response([
            'ok' => true,
            'ref' => $ref,
            'unit' => $priced['unit'],
            'total' => $priced['total'],
            'quantity' => $priced['quantity'],
        ]);
    }

    private static function notify($ref, $data, $priced, $settings, $post_id)
    {
        $to = !empty($settings['admin_email']) ? $settings['admin_email'] : get_option('admin_email');
        $subject = sprintf('[%s] New quote %s · %s', wp_specialchars_decode(get_bloginfo('name')), $ref, $data['company']);

        $lines = [
            'Reference: ' . $ref,
            'Name: ' . $data['name'],
            'Email: ' . $data['email'],
            'Company: ' . $data['company'],
            'Country: ' . $data['country'],
            'Phone: ' . $data['phone'],
            '',
            'Style: ' . $data['style'],
            'Size: ' . $data['length'] . ' × ' . $data['width'] . ' × ' . $data['height'] . ' cm',
            'Material: ' . $data['material'],
            'Print: ' . $data['print'],
            'Finish: ' . $data['finish'],
            'Quantity: ' . number_format_i18n($priced['quantity']),
            'Unit: ' . $priced['unit'],
            'Total: ' . $priced['total'],
            'Rush: ' . ($data['rush'] ? 'yes' : 'no'),
            '',
            'Notes: ' . $data['notes'],
            '',
            'Admin: ' . admin_url('post.php?post=' . $post_id . '&action=edit'),
        ];

        wp_mail($to, $subject, implode("\n", $lines));

        if (!empty($settings['notify_customer'])) {
            wp_mail(
                $data['email'],
                sprintf('We received your quote request (%s)', $ref),
                "Thanks {$data['name']},\n\nWe have your request for {$priced['quantity']} units at {$priced['unit']} each. An engineer replies within one business day.\n\nReference: {$ref}\n"
            );
        }
    }
}
