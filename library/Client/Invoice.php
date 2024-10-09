<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}

use Coinsnap\Result\InvoicePaymentMethod;
use Coinsnap\Util\PreciseNumber;

class Invoice extends AbstractClient{
    public function createInvoice(
        string $storeId,
        string $currency,
        ?PreciseNumber $amount = null,
        ?string $orderId = null,
        ?string $buyerEmail = null,
        ?string $customerName = null,
        ?string $redirectUrl = null,
        ?string $referralCode = null,
        ?array $metaData = null,
        ?InvoiceCheckoutOptions $checkoutOptions = null): \Coinsnap\Result\Invoice {

        $url = $this->getApiUrl().''.COINSNAP_SERVER_PATH.'/'.urlencode($storeId).'/invoices';
        $headers = $this->getRequestHeaders();
        $method = 'POST';

        // Prepare metadata.
        if(!isset($metaData['orderNumber']) && !empty($orderId)) $metaData['orderNumber'] = $orderId;
        if(!isset($metaData['customerName']) && !empty($customerName)) $metaData['customerName'] = $customerName;

        $body_array = array(
            'amount' => $amount !== null ? $amount->__toString() : null,
            'currency' => $currency,
            'buyerEmail' => $buyerEmail,
            'redirectUrl' => $redirectUrl,
            'orderId' => $orderId,
            'metadata' => (count($metaData) > 0)? $metaData : null,
            'referralCode' => $referralCode
        );

        $body = wp_json_encode($body_array,JSON_THROW_ON_ERROR);

        $response = $this->getHttpClient()->request($method, $url, $headers, $body);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\Invoice(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }

    public function getInvoice(string $storeId,string $invoiceId): \Coinsnap\Result\Invoice {

        $url = $this->getApiUrl().''.COINSNAP_SERVER_PATH.'/'.urlencode($storeId).'/invoices/'.urlencode($invoiceId);
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\Invoice(json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR));
        } else {
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }

}
