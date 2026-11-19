<?php
// Runs inside WordPress with WooCommerce and this plugin active: wp eval-file tests/CurrenciesFilterTest.php
// With Coinsnap as provider and no API key saved yet, the woocommerce_currencies filter must not
// fatal (it used to build an API client from a null URL and key, which broke wp-admin and
// WooCommerce > Settings > General with HTTP 500).
if (!defined('ABSPATH')) { exit; }

$keys = ['coinsnap_provider', 'coinsnap_api_key', 'woocommerce_currency'];
$saved = [];
foreach ($keys as $k) { $saved[$k] = get_option($k, null); }

$failures = 0;
$check = function (bool $ok, string $label) use (&$failures) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $label . "\n";
    if (!$ok) { $failures++; }
};

$input = ['EUR' => 'Euro', 'USD' => 'US dollar', 'XYZ' => 'Not supported'];

try {
    // 1. Coinsnap provider, no API key: no error, unsupported currencies removed.
    update_option('coinsnap_provider', 'coinsnap');
    delete_option('coinsnap_api_key');
    update_option('woocommerce_currency', 'EUR');
    try {
        $out = apply_filters('woocommerce_currencies', $input);
        $check(isset($out['EUR'], $out['USD']) && !isset($out['XYZ']), 'coinsnap without API key: supported kept, unsupported removed');
    } catch (\Throwable $e) {
        $check(false, 'coinsnap without API key throws ' . get_class($e) . ': ' . $e->getMessage());
    }

    // 2. Same with an API key saved.
    update_option('coinsnap_api_key', 'test-key');
    try {
        $out = apply_filters('woocommerce_currencies', $input);
        $check(isset($out['EUR']) && !isset($out['XYZ']), 'coinsnap with API key: same result');
    } catch (\Throwable $e) {
        $check(false, 'coinsnap with API key throws ' . get_class($e) . ': ' . $e->getMessage());
    }

    // 3. BTCPay provider: list untouched.
    update_option('coinsnap_provider', 'btcpay');
    $out = apply_filters('woocommerce_currencies', $input);
    $check(isset($out['XYZ']), 'btcpay: list unchanged');
} finally {
    foreach ($saved as $k => $v) {
        if ($v === null) { delete_option($k); } else { update_option($k, $v); }
    }
}

echo $failures === 0 ? "OK\n" : "$failures failure(s)\n";
