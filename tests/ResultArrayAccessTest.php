<?php
// Standalone test: php tests/ResultArrayAccessTest.php
// Reading an API result like an array ($invoice['status']) must work on PHP 7.4, the plugin's
// declared minimum. A "mixed" return type on offsetGet() is read by PHP 7.4 as a class name,
// so every read threw a TypeError and BTCPay checkout failed with "API connection is not established".
if (PHP_SAPI !== 'cli') { exit; }
if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }
if (!defined('_PS_VERSION_')) { define('_PS_VERSION_', '8.2.0'); }
require __DIR__ . '/../library/Result/AbstractResult.php';

$result = new class(['status' => 'Settled', 'amount' => 500, 'metadata' => ['orderId' => '42']]) extends \Coinsnap\Result\AbstractResult {};
$failures = 0;
$check = function ($ok, $label) use (&$failures) { echo ($ok ? 'PASS ' : 'FAIL ') . $label . "\n"; if (!$ok) { $failures++; } };
try {
    $check($result['status'] === 'Settled', 'string value');
    $check($result['amount'] === 500, 'int value');
    $check($result['metadata']['orderId'] === '42', 'nested array value');
    $check($result['missing'] === null, 'missing key is null');
    $check(isset($result['status']) && !isset($result['missing']), 'isset');
} catch (\Throwable $e) {
    $check(false, 'array read throws ' . get_class($e) . ': ' . $e->getMessage());
}
echo $failures === 0 ? "OK (PHP " . PHP_VERSION . ")\n" : "$failures failure(s) (PHP " . PHP_VERSION . ")\n";
exit($failures === 0 ? 0 : 1);
