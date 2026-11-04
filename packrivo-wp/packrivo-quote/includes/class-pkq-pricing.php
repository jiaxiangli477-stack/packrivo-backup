<?php
/**
 * Pricing model — single source of truth is packrivo-shared/pricing.json,
 * generated from packrivo-v2/lib/pricing.ts by packrivo-shared/build-shared.mjs.
 *
 * The constants below are only a fallback for the rare case where the JSON is missing;
 * they must not be edited independently of the TypeScript source.
 */

if (!defined('ABSPATH') && !defined('PKQ_STANDALONE')) {
    exit;
}

final class PKQ_Pricing
{
    private static $data = null;

    /** Normalised tables: key => factor/add. */
    private static function data()
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $fallback = [
            'materials' => [
                'kraft' => 1.00, 'white' => 1.06, 'recycled' => 1.10, 'corrugated' => 1.22,
                'rigidboard' => 1.75, 'kraftpoly' => 0.92, 'compostable' => 1.38,
            ],
            'prints' => ['none' => 0.0, 'one-color' => 0.09, 'full-color' => 0.18, 'full-color-spot' => 0.29],
            'finishes' => [
                'none' => 0.0, 'matte' => 0.06, 'gloss' => 0.05, 'soft-touch' => 0.13,
                'spot-uv' => 0.16, 'foil' => 0.35, 'emboss' => 0.24,
            ],
            'tiers' => [100 => 2.864, 250 => 1.468, 500 => 1.00, 1000 => 0.93, 2500 => 0.85, 5000 => 0.78, 10000 => 0.72, 25000 => 0.66],
            'setup' => ['print' => 220.0, 'die' => 120.0],
            'sizeRef' => [30, 20, 10],
            'rules' => ['rushPercent' => 22.0, 'tierFloor' => 0.64, 'freePrintSetupFrom' => 2500, 'freeDieFrom' => 5000, 'fee_percent' => 15, 'min_fee' => 150],
        ];

        $path = __DIR__ . '/pricing.json';
        if (!is_readable($path)) {
            error_log('PKQ pricing: pricing.json missing; using fallback');
            self::$data = $fallback;
            return self::$data;
        }

        $json = json_decode((string) file_get_contents($path), true);
        // A half-written file used to pass (only materials was checked) and either
        // 422'd every order or silently priced at the tier floor. Require all four.
        $complete = is_array($json)
            && !empty($json['materials']) && is_array($json['materials'])
            && !empty($json['prints']) && is_array($json['prints'])
            && !empty($json['finishes']) && is_array($json['finishes'])
            && !empty($json['tiers']) && is_array($json['tiers']) && count($json['tiers']) >= 2;
        if ($complete) {
            foreach (['materials' => 'factor', 'prints' => 'add', 'finishes' => 'add'] as $group => $field) {
                foreach ($json[$group] as $row) {
                    if (!is_array($row) || !isset($row[$field]) || !is_numeric($row[$field]) || !is_finite((float) $row[$field])) {
                        $complete = false;
                        break 2;
                    }
                }
            }
        }
        if (!$complete) {
            error_log('PKQ pricing: invalid pricing tables; using fallback');
            self::$data = $fallback;
            return self::$data;
        }

        $materials = [];
        $labels = ['materials' => [], 'prints' => [], 'finishes' => []];
        foreach ($json['materials'] as $key => $row) {
            $materials[$key] = (float) $row['factor'];
            $labels['materials'][$key] = (string) ($row['label'] ?? $key);
        }
        $prints = [];
        foreach ($json['prints'] as $key => $row) {
            $prints[$key] = (float) $row['add'];
            $labels['prints'][$key] = (string) ($row['label'] ?? $key);
        }
        $finishes = [];
        foreach ($json['finishes'] as $key => $row) {
            $finishes[$key] = (float) $row['add'];
            $labels['finishes'][$key] = (string) ($row['label'] ?? $key);
        }
        $tiers = [];
        foreach ($json['tiers'] as $pair) {
            $tiers[(int) $pair[0]] = (float) $pair[1];
        }

        self::$data = [
            'materials' => $materials,
            'prints' => $prints,
            'finishes' => $finishes,
            'labels' => $labels,
            'tiers' => $tiers,
            'setup' => [
                'print' => (float) ($json['setup']['print'] ?? 220),
                'die' => (float) ($json['setup']['die'] ?? 120),
            ],
            'sizeRef' => $json['sizeRef'] ?? [30, 20, 10],
            'rules' => [
                'rushPercent' => (float) ($json['rules']['rushPercent'] ?? 22),
                'tierFloor' => (float) ($json['rules']['tierFloor'] ?? 0.64),
                'freePrintSetupFrom' => (int) ($json['rules']['freePrintSetupFrom'] ?? 2500),
                'freeDieFrom' => (int) ($json['rules']['freeDieFrom'] ?? 5000),
                'fee_percent' => (float) ($json['rules']['fee_percent'] ?? 15),
                'min_fee' => (float) ($json['rules']['min_fee'] ?? 150),
            ],
        ];

        return self::$data;
    }

    /**
     * Public view of the tables, so the browser can price with the same data
     * instead of keeping its own hard-coded copy.
     */
    public static function tables()
    {
        $d = self::data();
        $tiers = [];
        foreach ($d['tiers'] as $qty => $factor) {
            $tiers[] = [(int) $qty, (float) $factor];
        }
        $labels = $d['labels'] ?? ['materials' => [], 'prints' => [], 'finishes' => []];

        $rows = function ($values, $labelMap, $field) {
            $out = [];
            foreach ($values as $key => $value) {
                $out[$key] = [
                    'label' => (string) ($labelMap[$key] ?? $key),
                    $field => (float) $value,
                ];
            }
            return $out;
        };

        return [
            'materials' => $rows($d['materials'], $labels['materials'], 'factor'),
            'prints' => $rows($d['prints'], $labels['prints'], 'add'),
            'finishes' => $rows($d['finishes'], $labels['finishes'], 'add'),
            'tiers' => $tiers,
            'setup' => $d['setup'],
            'sizeRef' => $d['sizeRef'],
            'rules' => $d['rules'],
        ];
    }

    /** qty => factor, linear interpolation between published tiers. */
    public static function quantity_factor($qty)
    {
        $d = self::data();
        $points = $d['tiers'];
        ksort($points);
        $caps = array_keys($points);
        $qty = (float) $qty;

        if ($qty <= $caps[0]) {
            return (float) $points[$caps[0]];
        }
        for ($i = 1; $i < count($caps); $i++) {
            $cap = $caps[$i];
            $prev = $caps[$i - 1];
            if ($qty <= $cap) {
                $t = ($qty - $prev) / ($cap - $prev);
                return $points[$prev] + ($points[$cap] - $points[$prev]) * $t;
            }
        }
        return (float) $d['rules']['tierFloor'];
    }

    public static function size_factor($l, $w, $h)
    {
        $d = self::data();
        [$rl, $rw, $rh] = $d['sizeRef'];
        $l = max(1, (float) $l);
        $w = max(1, (float) $w);
        $h = max(1, (float) $h);
        $area = 2 * ($l * $w + $l * $h + $w * $h);
        $reference = 2 * ($rl * $rw + $rl * $rh + $rw * $rh);
        return pow($area / $reference, 0.6);
    }

    /**
     * @param array $in  style, material, print, finish, quantity, length, width, height, rush
     * @param array $settings plugin settings (style bases, setup, rush)
     * @return array|WP_Error
     */
    public static function price(array $in, array $settings)
    {
        $d = self::data();
        $style = isset($in['style']) ? (string) $in['style'] : '';
        if (!isset($settings['styles'][$style])) {
            return new WP_Error('pkq_style', 'Unknown box style.', ['status' => 422]);
        }

        $material_key = isset($in['material']) ? (string) $in['material'] : 'kraft';
        $print_key = isset($in['print']) ? (string) $in['print'] : 'full-color';
        $finish_key = isset($in['finish']) ? (string) $in['finish'] : 'matte';

        if (!isset($d['materials'][$material_key])) {
            return new WP_Error('pkq_material', 'Unknown material.', ['status' => 422]);
        }
        if (!isset($d['prints'][$print_key])) {
            return new WP_Error('pkq_print', 'Unknown print option.', ['status' => 422]);
        }
        if (!isset($d['finishes'][$finish_key])) {
            return new WP_Error('pkq_finish', 'Unknown finish.', ['status' => 422]);
        }

        $qty = (int) round((float) ($in['quantity'] ?? 500));
        $min = (int) ($settings['min_qty'] ?? 250);
        $max = (int) ($settings['max_qty'] ?? 250000);
        if ($qty < $min || $qty > $max) {
            return new WP_Error('pkq_qty', sprintf('Quantity must be between %d and %d.', $min, $max), ['status' => 422]);
        }

        $l = round((float) ($in['length'] ?? 0), 1);
        $w = round((float) ($in['width'] ?? 0), 1);
        $h = round((float) ($in['height'] ?? 0), 1);
        if ($l < 2 || $w < 2 || $h < 0.05 || $l > 120 || $w > 120 || $h > 120) {
            return new WP_Error('pkq_dims', 'Dimensions out of range (2–120 cm).', ['status' => 422]);
        }

        $base = (float) $settings['styles'][$style]['base'];
        $mat = $d['materials'][$material_key];
        $print = $d['prints'][$print_key];
        $finish = $d['finishes'][$finish_key];
        $tier = self::quantity_factor($qty);
        $size = self::size_factor($l, $w, $h);

        $printed = $print_key !== 'none' || $finish_key !== 'none';
        $setup = 0.0;
        if ($printed) {
            if ($qty < $d['rules']['freePrintSetupFrom']) {
                $setup += (float) ($settings['setup_print'] ?? $d['setup']['print']);
            }
            if ($qty < $d['rules']['freeDieFrom']) {
                $setup += (float) ($settings['setup_die'] ?? $d['setup']['die']);
            }
        }

        $rush = !empty($in['rush']);
        $rush_factor = $rush ? 1 + ((float) ($settings['rush_percent'] ?? $d['rules']['rushPercent']) / 100) : 1.0;

        $variable = ($base * $mat * $size + $print + $finish) * $tier * $rush_factor;
        $goods_unit = $variable + ($setup / $qty);
        $goods_total = $goods_unit * $qty;
        $minimum_fee = (float) ($settings['min_fee'] ?? $d['rules']['min_fee']);
        $calculated_fee = $goods_total * (float) ($settings['fee_percent'] ?? $d['rules']['fee_percent']) / 100;
        $fee = max($minimum_fee, $calculated_fee);
        $total = $goods_total + $fee;

        return [
            'unit' => round($total / $qty, 2),
            'total' => round($total, 2),
            'goods_unit' => round($goods_unit, 2),
            'goods_total' => round($goods_total, 2),
            'fee' => round($fee, 2),
            'fee_is_min' => $calculated_fee < $minimum_fee,
            'setup' => round($setup, 2),
            'quantity' => $qty,
            'size_factor' => round($size, 4),
            'tier_factor' => round($tier, 4),
            'rush' => $rush,
        ];
    }

    /** Same tier ladder the UI shows. */
    public static function ladder(array $in, array $settings)
    {
        $out = [];
        foreach (array_keys(self::data()['tiers']) as $qty) {
            $row = self::price(array_merge($in, ['quantity' => $qty]), $settings);
            if (!is_wp_error($row)) {
                $out[] = ['quantity' => $qty, 'unit' => $row['unit'], 'total' => $row['total']];
            }
        }
        return $out;
    }
}
