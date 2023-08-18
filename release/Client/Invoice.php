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
        
        /*

        // Set metaData if any.
        if (is_array($metaData)) {
            if(isset($metaData[]))$metaDataMerged = $metaData;
        }

        // $orderId and $buyerEmail are checked explicitly as they are optional.
        // Make sure that both are only passed either as param or via metadata array.
        if ($orderId) {
            if (array_key_exists('orderNumber', $metaDataMerged)) {
                throw new \InvalidArgumentException('You cannot pass $orderId and define it in the metadata array as it is ambiguous.');
            }
            $metaDataMerged['orderNumber'] = $orderId;
        }
        if ($customerName) {
            if (array_key_exists('customerName', $metaDataMerged)) {
                throw new \InvalidArgumentException('You cannot pass $customerName and define it in the metadata array as it is ambiguous.');
            }
            $metaDataMerged['customerName'] = $customerName;
        }
        */
        
        $body_array = array(
            'amount' => $amount !== null ? $amount->__toString() : null,
                'currency' => $currency,
                'buyerEmail' => $buyerEmail,
                'redirectUrl' => $redirectUrl,
                'orderId' => $orderId,
                'metadata' => (count($metaDataMerged) > 0)? $metaDataMerged : null,
        //        'checkout' => $checkoutOptions ? $checkoutOptions->toArray() : null,
                'referralCode' => $referralCode
        );
        
        \Coinsnap\WC\Helper\Logger::debug( 'InvoiceBoody: ' . print_r( $body_array, true ), true );

        $body = json_encode($body_array,JSON_THROW_ON_ERROR);

        $response = $this->getHttpClient()->request($method, $url, $headers, $body);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\Invoice(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
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

    public function getAllInvoices(string $storeId): \Coinsnap\Result\InvoiceList
    {
        return $this->_getAllInvoicesWithFilter($storeId, null);
    }

    public function getInvoicesByOrderIds(string $storeId, array $orderIds): \Coinsnap\Result\InvoiceList
    {
        return $this->_getAllInvoicesWithFilter($storeId, $orderIds);
    }

    private function _getAllInvoicesWithFilter(string $storeId,array $filterByOrderIds = null): \Coinsnap\Result\InvoiceList {
        $url = $this->getApiUrl().''.COINSNAP_SERVER_PATH.'/'.urlencode($storeId).'/invoices?';
        if ($filterByOrderIds !== null) {
            foreach ($filterByOrderIds as $filterByOrderId) {
                $url .= 'orderId=' . urlencode($filterByOrderId) . '&';
            }
        }

        // Clean URL
        $url = rtrim($url, '&');
        $url = rtrim($url, '?');

        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\InvoiceList(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    /**
     * @return InvoicePaymentMethod[]
     */
    public function getPaymentMethods(string $storeId, string $invoiceId): array
    {
        $method = 'GET';
        $url = $this->getApiUrl().''.COINSNAP_SERVER_PATH.'/'.urlencode($storeId).'/invoices/'.urlencode($invoiceId).'/payment-methods';
        $headers = $this->getRequestHeaders();
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            $r = [];
            $data = json_decode(
                $response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            foreach ($data as $item) {
                $item = new \Coinsnap\Result\InvoicePaymentMethod($item);
                $r[] = $item;
            }
            return $r;
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    public function markInvoiceStatus(string $storeId, string $invoiceId, string $markAs): \Coinsnap\Result\Invoice
    {
        $url = $this->getApiUrl().''.COINSNAP_SERVER_PATH.'/'.urlencode($storeId).'/invoices/'.urlencode($invoiceId).'/status';
        $headers = $this->getRequestHeaders();
        $method = 'POST';

        $body = json_encode(
            [
                'status' => $markAs
            ],
            JSON_THROW_ON_ERROR
        );

        $response = $this->getHttpClient()->request($method, $url, $headers, $body);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\Invoice(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }
}
