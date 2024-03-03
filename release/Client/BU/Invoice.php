<?php

declare(strict_types=1);

namespace Coinsnap\Client;

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
        ?InvoiceCheckoutOptions $checkoutOptions = null): \Coinsnap\Result\Invoice 
    {
        $url = $this->getApiUrl().''.COINSNAP_SERVER_PATH.'/'.urlencode($storeId).'/invoices';
        $headers = $this->getRequestHeaders();
        $method = 'POST';

        // Prepare metadata.
        $metaDataMerged = [];
        if(!empty($orderId)) $metaDataMerged['orderNumber'] = $orderId;
        if(!empty($customerName)) $metaDataMerged['customerName'] = $customerName;
        
        $body_array = array(
            'amount' => $amount !== null ? $amount->__toString() : null,
                'currency' => $currency,
                'buyerEmail' => $buyerEmail,
                'redirectUrl' => $redirectUrl,
                'orderId' => $orderId,
                'metadata' => (count($metaDataMerged) > 0)? $metaDataMerged : null,
                'referralCode' => $referralCode
        );
        
        
        $body = json_encode($body_array,JSON_THROW_ON_ERROR);

        $response = $this->getHttpClient()->request($method, $url, $headers, $body);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\Invoice(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            print_r($response);
            exit;
            //throw $this->getExceptionByStatusCode($method, $url, $response);
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
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }
}
