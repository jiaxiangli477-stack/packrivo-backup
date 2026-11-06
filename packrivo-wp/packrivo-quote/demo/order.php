<?php
/** Standalone order endpoint for the demo harness (mirrors PKQ_REST::create). */

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
    'min_qty' => 250,
    'max_qty' => 250000,
    'setup_print' => 220,
    'setup_die' => 120,
    'rush_percent' => 22,
    'styles' => (function () {
        $file = __DIR__ . '/../includes/pricing.json';
        $data = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
        return $data['styles'] ?? ['mailer' => ['label' => 'Mailer', 'base' => 1.42]];
    })(),
];

header('Content-Type: application/json; charset=utf-8');

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid JSON']);
    exit;
}
if (!empty($in['website'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Rejected']);
    exit;
}

$name = trim((string) ($in['name'] ?? ''));
$email = trim((string) ($in['email'] ?? ''));
$company = trim((string) ($in['company'] ?? ''));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $company === '') {
    http_response_code(422);
    echo json_encode(['message' => 'Name, work email and company are required.']);
    exit;
}

$priced = PKQ_Pricing::price($in, $settings);
if (is_wp_error($priced)) {
    http_response_code(422);
    echo json_encode(['message' => $priced->get_error_message()]);
    exit;
}

$ref = 'PKQ-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));
file_put_contents(__DIR__ . '/orders.jsonl', json_encode([
    'ref' => $ref,
    'created' => gmdate('c'),
    'name' => $name,
    'email' => $email,
    'company' => $company,
    'style' => $in['style'] ?? '',
    'material' => $in['material'] ?? '',
    'print' => $in['print'] ?? '',
    'finish' => $in['finish'] ?? '',
    'quantity' => $priced['quantity'],
    'unit' => $priced['unit'],
    'total' => $priced['total'],
]) . "\n", FILE_APPEND);

echo json_encode([
    'ok' => true,
    'ref' => $ref,
    'unit' => $priced['unit'],
    'total' => $priced['total'],
    'quantity' => $priced['quantity'],
]);
