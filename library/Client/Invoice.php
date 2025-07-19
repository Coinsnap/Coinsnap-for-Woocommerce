<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}
header('Access-Control-Allow-Origin: *');

use Coinsnap\Result\InvoicePaymentMethod;
use Coinsnap\Util\PreciseNumber;
use Coinsnap\Http\WPRemoteClient;

class Invoice extends AbstractClient{
    
    public function getCurrencies(): array {
        if(defined('COINSNAP_CURRENCIES')){
            return COINSNAP_CURRENCIES;
        }
        else {
            return array("EUR","USD","SATS","BTC","CAD","JPY","GBP","CHF","RUB");
        }
    }
    
    /*  Invoice::loadExchangeRates() method loads exchange rates 
     *  for fiat and crypto currencies from coingecko.com server in real time.
     *  We don't send any data from the plugin or Wordpress database.
     *  Method returns array with result code, exchange rates or error
     */
    public function loadExchangeRates(): array {
        $url = 'https://api.coingecko.com/api/v3/exchange_rates';
        $headers = [];
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);
        
        if ($response->getStatus() === 200) {
            $body = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        }
        else {
            return array('result' => false, 'error' => 'ratesLoadingError');
        }
        
        if (count($body)<1 || !isset($body['rates'])){
            return array('result' => false, 'error' => 'ratesListError');
        }
    
        return array('result' => true, 'data' => $body['rates']);
    }
    
    public function checkPaymentData($amount,$currency,$provider = 'coinsnap',$mode = 'invoice'): array {
        
        if($provider === 'bitcoin' || $provider === 'lightning'){
            $btcPayCurrencies = $this->loadExchangeRates();
            
            if(!$btcPayCurrencies['result']){
                return array('result' => false,'error' => $btcPayCurrencies['error'],'min_value' => '');
            }
            
            elseif(!isset($btcPayCurrencies['data'][strtolower($currency)]) || $btcPayCurrencies['data'][strtolower($currency)]['value'] <= 0){
                return array('result' => false,'error' => 'currencyError','min_value' => '');
            }
            
            else {
                $rate = 1/$btcPayCurrencies['data'][strtolower($currency)]['value'];
                $min_value_btcpay = ($provider === 'bitcoin')? 0.000005869 : 0.000001;
                $min_value = $min_value_btcpay/$rate;
                
               if($mode === 'calculation'){
                    return array('result' => true, 'min_value' => round($min_value,2));
                }
                
                else {                
                    if(round($amount * $rate * 1000000) < round($min_value_btcpay * 1000000)){
                        return array('result' => false,'error' => 'amountError','min_value' => round($min_value,2));
                    }
                    else {
                        return array('result' => true);
                    }
                }
            }
        }
        
        if($provider === 'coinsnap'){
        
            $coinsnapCurrencies = $this->getCurrencies();

            if(!is_array($coinsnapCurrencies)){
                return array('result' => false,'error' => 'currenciesError','min_value' => '');
            }
            if(!in_array($currency,$coinsnapCurrencies)){
                return array('result' => false,'error' => 'currencyError','min_value' => '');
            }
            
            $min_value_array = array(
                "SATS" => 1,
                "JPY" => 1,
                "RUB" => 1,
                "BTC" => 0.000001
            );
            
            $min_value = (isset($min_value_array[$currency]))? $min_value_array[$currency] : 0.01;
            
            if($mode === 'calculation'){
                return array('result' => true,'min_value' => $min_value);
            }
            
            else {
                if($amount === null || $amount === 0){
                    return array('result' => false,'error' => 'amountError');
                }
                elseif($amount < $min_value){
                    return array('result' => false,'error' => 'amountError','min_value' => $min_value);
                }
                else {
                    return array('result' => true);
                }
            }            
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
