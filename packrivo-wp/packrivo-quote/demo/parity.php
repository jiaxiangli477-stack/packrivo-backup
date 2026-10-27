<?php
define('PKQ_STANDALONE', true);

class WP_Error
{
    public $message;
    public function __construct($code = '', $message = '') { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($t) { return $t instanceof WP_Error; }

require_once __DIR__ . '/../includes/class-pkq-pricing.php';

$settings = [
    'min_qty' => 500,
    'max_qty' => 250000,
    'setup_print' => 220,
    'setup_die' => 120,
    'rush_percent' => 22,
    'styles' => [
        'mailer' => ['label' => 'Mailer', 'base' => 1.42],
        'product' => ['label' => 'Product', 'base' => 1.18],
        'shipping' => ['label' => 'Shipping', 'base' => 1.02],
        'rigid' => ['label' => 'Rigid', 'base' => 3.40],
        'carton' => ['label' => 'Carton', 'base' => 0.74],
        'pouch' => ['label' => 'Pouch', 'base' => 0.46],
        'poly' => ['label' => 'Poly', 'base' => 0.62],
        'bag' => ['label' => 'Bag', 'base' => 0.92],
        'sticker' => ['label' => 'Sticker', 'base' => 0.16],
    ],
];

$cases = [
    ['style' => 'mailer', 'material' => 'kraft', 'print' => 'full-color', 'finish' => 'matte', 'quantity' => 1000, 'length' => 30, 'width' => 22, 'height' => 12, 'rush' => false],
    ['style' => 'rigid', 'material' => 'rigidboard', 'print' => 'full-color', 'finish' => 'soft-touch', 'quantity' => 2500, 'length' => 22, 'width' => 16, 'height' => 8, 'rush' => false],
    ['style' => 'carton', 'material' => 'white', 'print' => 'one-color', 'finish' => 'none', 'quantity' => 500, 'length' => 12, 'width' => 4, 'height' => 16, 'rush' => false],
    ['style' => 'pouch', 'material' => 'kraftpoly', 'print' => 'full-color', 'finish' => 'matte', 'quantity' => 5000, 'length' => 19, 'width' => 7, 'height' => 28, 'rush' => true],
];

$out = [];
foreach ($cases as $c) {
    $p = PKQ_Pricing::price($c, $settings);
    $out[] = ['input' => $c, 'unit' => $p['unit'], 'total' => $p['total']];
}
echo json_encode($out, JSON_PRETTY_PRINT), "\n";
