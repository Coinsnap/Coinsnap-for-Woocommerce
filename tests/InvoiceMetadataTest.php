<?php
// Runs inside WordPress with WooCommerce and this plugin active: wp eval-file tests/InvoiceMetadataTest.php
// Captures the create-invoice request (no real API: every HTTP call is answered by a fake) and checks
// that orderId and the order metadata are sent for Coinsnap and BTCPay, and that personal data is
// sent only when "Send customer data" is enabled.
if (!defined('ABSPATH')) { exit; }

$keys = ['coinsnap_provider', 'coinsnap_api_key', 'coinsnap_store_id', 'btcpay_server_url', 'btcpay_api_key',
    'btcpay_store_id', 'coinsnap_send_customer_data', 'woocommerce_currency'];
$saved = [];
foreach ($keys as $k) { $saved[$k] = get_option($k, null); }

$failures = 0;
$check = function (bool $ok, string $label) use (&$failures) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $label . "\n";
    if (!$ok) { $failures++; }
};

$captured = [];
$fake = function ($pre, $args, $url) use (&$captured) {
    $reply = function ($body) { return ['headers' => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary(['content-type' => 'application/json']), 'body' => wp_json_encode($body), 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => [], 'filename' => null]; };
    if (strpos($url, 'coingecko') !== false) {
        return $reply(['rates' => ['btc' => ['value' => 1], 'eur' => ['value' => 90000], 'usd' => ['value' => 100000], 'sats' => ['value' => 100000000]]]);
    }
    if (strpos($url, '/payment-methods') !== false) {
        return $reply([['paymentMethodId' => 'BTC-LN', 'enabled' => true]]);
    }
    if (strpos($url, '/rates') !== false) {
        return $reply([['currencyPair' => 'BTC_EUR', 'rate' => '90000', 'errors' => []]]);
    }
    if (($args['method'] ?? '') === 'POST' && preg_match('~/invoices$~', $url)) {
        $captured[] = json_decode((string) $args['body'], true);
        $id = 'inv' . count($captured);
        return $reply(['id' => $id, 'checkoutLink' => 'https://pay.example/i/' . $id, 'status' => 'New']);
    }
    return $reply([]);
};
add_filter('pre_http_request', $fake, 10, 3);

$product = new WC_Product_Simple();
$product->set_name('Test Hoodie');
$product->set_sku('HOODIE-1');
$product->set_regular_price('10');
$product->save();

$order = wc_create_order();
$order->add_product($product, 2);
$order->set_billing_first_name('Ana');
$order->set_billing_last_name('Buyer');
$order->set_billing_email('ana@example.com');
$order->set_billing_phone('+381111');
$order->set_billing_address_1('Street 1');
$order->set_billing_city('Belgrade');
$order->set_billing_postcode('11000');
$order->set_billing_country('RS');
$order->set_customer_note('Please ring twice');
$order->set_currency('EUR');
$order->calculate_totals();
$order->save();

$personal = ['buyerEmail', 'buyerName', 'customerName', 'buyerPhone', 'buyerAddress1', 'buyerCity', 'buyerZip', 'buyerCountry', 'buyerMessage'];

$run = function (string $provider, string $sendCustomerData) use ($order, &$captured) {
    update_option('coinsnap_provider', $provider);
    update_option('coinsnap_send_customer_data', $sendCustomerData);
    $before = count($captured);
    $gateway = new \Coinsnap\WC\Gateway\DefaultGateway();
    $gateway->createInvoice(wc_get_order($order->get_id()));
    return count($captured) > $before ? end($captured) : null;
};

try {
    update_option('woocommerce_currency', 'EUR');
    update_option('coinsnap_api_key', 'fake-key');
    update_option('coinsnap_store_id', 'fake-store');
    update_option('btcpay_server_url', 'https://btcpay.example');
    update_option('btcpay_api_key', 'fake-key');
    update_option('btcpay_store_id', 'fake-store');

    foreach (['coinsnap', 'btcpay'] as $provider) {
        $body = $run($provider, 'no');
        $check(is_array($body), "$provider: invoice request captured");
        $meta = $body['metadata'] ?? [];
        $check((string) ($body['orderId'] ?? '') === (string) $order->get_id(), "$provider: top-level orderId = order ID");
        $check((string) ($meta['orderId'] ?? '') === (string) $order->get_id(), "$provider: metadata.orderId = order ID (webhook match)");
        $check((string) ($meta['orderNumber'] ?? '') === (string) $order->get_order_number(), "$provider: orderNumber");
        $check(strpos((string) ($meta['orderUrl'] ?? ''), (string) $order->get_id()) !== false, "$provider: orderUrl");
        $check(($meta['itemDesc'] ?? '') === 'Test Hoodie x 2', "$provider: itemDesc");
        $check(($meta['itemCode'] ?? '') === 'HOODIE-1', "$provider: itemCode");
        $line = $meta['lineItems'][0] ?? [];
        $check(($line['name'] ?? '') === 'Test Hoodie' && ($line['sku'] ?? '') === 'HOODIE-1' && ($line['quantity'] ?? 0) === 2
            && (float) ($line['price'] ?? 0) === 10.0 && (float) ($line['total'] ?? 0) === 20.0, "$provider: lineItems");
        $check(($meta['platform'] ?? '') === 'woocommerce' && ($meta['pluginVersion'] ?? '') === COINSNAP_WC_VERSION, "$provider: platform, pluginVersion");
        $leaked = array_values(array_intersect($personal, array_keys($meta)));
        $check($leaked === [] && empty($body['buyerEmail']), "$provider, customer data off: no personal data (" . implode(',', $leaked) . ')');
        $check(!in_array('', $meta, true) && !in_array(null, $meta, true), "$provider: no empty values in metadata");

        $body = $run($provider, 'yes');
        $meta = $body['metadata'] ?? [];
        $check(($body['buyerEmail'] ?? '') === 'ana@example.com' && ($meta['buyerEmail'] ?? '') === 'ana@example.com', "$provider, customer data on: buyerEmail");
        $check(($meta['buyerName'] ?? '') === 'Ana Buyer' && ($meta['customerName'] ?? '') === 'Ana Buyer', "$provider, customer data on: buyerName, customerName");
        $check(($meta['buyerPhone'] ?? '') === '+381111' && ($meta['buyerCity'] ?? '') === 'Belgrade' && ($meta['buyerCountry'] ?? '') === 'RS', "$provider, customer data on: phone, address");
        $check(($meta['buyerMessage'] ?? '') === 'Please ring twice', "$provider, customer data on: buyerMessage");
        $check((string) ($meta['orderId'] ?? '') === (string) $order->get_id(), "$provider, customer data on: metadata.orderId unchanged");
    }
} finally {
    remove_filter('pre_http_request', $fake, 10);
    foreach ($saved as $k => $v) {
        if ($v === null) { delete_option($k); } else { update_option($k, $v); }
    }
    $order->delete(true);
    $product->delete(true);
}

echo $failures === 0 ? "OK\n" : "$failures FAILED\n";
