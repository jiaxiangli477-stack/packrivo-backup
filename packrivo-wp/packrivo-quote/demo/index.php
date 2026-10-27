<?php
/**
 * Standalone harness for the Packrivo quote widget.
 *
 * Run:  docker run --rm -p 8899:8899 -v "<plugin dir>:/app" php:8.2-cli \
 *         php -S 0.0.0.0:8899 -t /app
 * Open: http://127.0.0.1:8899/demo/
 *
 * Not part of the plugin zip.
 */

$pricingFile = __DIR__ . '/../includes/pricing.json';
$pricing = is_readable($pricingFile) ? json_decode((string) file_get_contents($pricingFile), true) : null;
$styles = $pricing['styles'] ?? [
    'mailer' => ['label' => 'Roll-end mailer', 'base' => 1.42],
];

$config = [
    'endpoint' => '/demo/order.php',
    'nonce' => 'demo',
    'currency' => 'USD',
    'minQty' => 500,
    'maxQty' => 250000,
    'setupPrint' => 220,
    'setupDie' => 120,
    'rushPercent' => 22,
    'styles' => $styles,
    'i18n' => [
        'title' => 'Build your quote',
        'sub' => 'Five steps. Live pricing. An engineer confirms the dieline before anything is cut.',
        'submit' => 'Request this quote',
        'sending' => 'Sending…',
        'done' => 'Quote requested',
        'error' => 'Something went wrong. Please email us instead.',
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Packrivo quote widget — standalone demo</title>
<link rel="stylesheet" href="../assets/pkq-quote.css" />
<style>
  body { margin: 0; background: #f3f0e7; color: #0c1411; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
  .page { max-width: 1180px; margin: 0 auto; padding: 40px 20px 80px; }
  .kicker { font: 500 11px/1.4 ui-monospace, Menlo, monospace; letter-spacing: .08em; text-transform: uppercase; color: #5b615b; }
  h1 { font-size: clamp(28px, 4vw, 44px); line-height: 1.02; letter-spacing: -.03em; margin: 10px 0 28px; font-weight: 800; }
</style>
</head>
<body>
<div class="page">
  <p class="kicker">Standalone harness · plugin demo</p>
  <h1>Packrivo quote &amp; order widget</h1>
  <div class="pkq-root" data-style="mailer" data-qty="1000"></div>
</div>
<script>window.PKQ = <?php echo json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;</script>
<script src="../assets/pkq-quote.js"></script>
</body>
</html>
