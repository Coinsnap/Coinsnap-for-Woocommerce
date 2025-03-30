<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}

use Coinsnap\WC\Helper\Logger;
use Coinsnap\Result\InvoicePaymentMethod;
use Coinsnap\Util\PreciseNumber;

class Invoice extends AbstractClient{
    
    public function getCurrencies(): array {
        if(defined('COINSNAP_CURRENCIES')){
            return COINSNAP_CURRENCIES;
        }
        else {
            return array("EUR","USD","SATS","BTC","CAD","JPY","GBP","CHF","RUB");
        }
    }
    
    public function checkPaymentData($amount,$currency): array {
        
        $coinsnapCurrencies = $this->getCurrencies();
        
        if(defined('COINSNAP_CURRENCIES')){
            if(!in_array($currency,$coinsnapCurrencies)){
                return array('result' => false,'error' => 'currencyError','min_value' => '');
            }
            elseif($amount === null || $amount === 0){
                return array('result' => false,'error' => 'amountError','min_value' => 0);
            }
            elseif(($currency === "SATS" || $currency === "JPY" || $currency === "RUB") && $amount < 1){
                return array('result' => false,'error' => 'amountError','min_value' => 1);
            }
            elseif($currency === "BTC" && $amount < 0.000001){
                return array('result' => false,'error' => 'amountError','min_value' => 0.000001);
            }
            elseif($amount < 0.01){ 
                return array('result' => false,'error' => 'amountError','min_value' => 0.01);
            }
            else {
                return array('result' => true);
            }
        }
        else {
            return array('result' => false,'error' => 'currenciesError','min_value' => '');
        }
    }
    
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
        ?bool $redirectAutomatically = true,
        ?string $walletMessage = null): \Coinsnap\Result\Invoice {

        $url = $this->getApiUrl().''.COINSNAP_SERVER_PATH.'/'.urlencode($storeId).'/invoices';
        $headers = $this->getRequestHeaders();
        $method = 'POST';

        // Prepare metadata.
        if(!isset($metaData['orderNumber']) && !empty($orderId)){ $metaData['orderNumber'] = $orderId;}
        if(!isset($metaData['customerName']) && !empty($customerName)){ $metaData['customerName'] = $customerName;}

        $body_array = array(
            'amount' => $amount !== null ? $amount->__toString() : null,
            'currency' => $currency,
            'buyerEmail' => $buyerEmail,
            'redirectUrl' => $redirectUrl,
            'orderId' => $orderId,
            'metadata' => (count($metaData) > 0)? $metaData : null,
            'referralCode' => $referralCode,
            'redirectAutomatically' => $redirectAutomatically,
            'walletMessage' => $walletMessage
        );

        $body = wp_json_encode($body_array,JSON_THROW_ON_ERROR);

        $response = $this->getHttpClient()->request($method, $url, $headers, $body);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\Invoice(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), (int)esc_html($response->getStatus()), esc_html($response->getBody()));
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
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), (int)esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }

}
