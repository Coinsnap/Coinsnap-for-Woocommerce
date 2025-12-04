<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}

use Coinsnap\WC\Helper\Logger;

class Store extends AbstractClient{

    /**
     * @return \Coinsnap\Result\Store[int $code, array $result]
     */
    public function getStore($storeId): \Coinsnap\Result\Store {
        
        $url = $this->getApiUrl().COINSNAP_SERVER_PATH.'/' . urlencode($storeId);
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);
        
        $json_decode = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $result = (json_last_error() === JSON_ERROR_NONE)? $json_decode : array('error' => json_last_error());
        if ($response->getStatus() === 200) {
            $result['code'] = $response->getStatus();
            return new \Coinsnap\Result\Store($result);
        }
        else {
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), (int)esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }

    /**
     * @return \Coinsnap\Result\Store[int $code, array $result]
     */
    public function getStores(): array {
        
        $url = $this->getApiUrl().COINSNAP_SERVER_PATH;
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);
        
        if ($response->getStatus() === 200) {
            $stores_array = [];
            $json_decode = json_decode($response->getBody(), true);
            
            foreach ($json_decode as $item) {                
                $item = new \Coinsnap\Result\Store($item);
                $stores_array[] = $item;
            }
            return $stores_array;
        }
        else {
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), (int)esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }
    
    /**
     * For BTCPay server only
     * @return \Coinsnap\Result\Store[int $code, array $result]
     */
    public function getStoreCurrenciesRates($storeId, $currencies = ['EUR','USD']): \Coinsnap\Result\Store {
        $url = $this->getApiUrl().COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/rates?';
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $currenciesArray = [];
        
        foreach($currencies as $currency){
            $currenciesArray[] = 'currencyPair=BTC_'.$currency;
        }
        
        $response = $this->getHttpClient()->request($method, $url.implode('&',$currenciesArray), $headers);
        if ($response->getStatus() === 200) {

            $json_decode = json_decode($response->getBody(), true, 512, JSON_INVALID_UTF8_IGNORE);

            $result['currencies'] = [];
            if(count($json_decode) > 0){
                foreach($json_decode as $currency){
                    if(empty($currency['errors'])){
                        $result['currencies'][$currency["currencyPair"]] = $currency["rate"];
                    } 
                }
            }
            
            if(count($result['currencies']) < 1) {
                $result['error'] = 'NOT_LOADED';
                $result['response'] = $response->getBody();
                $result['url'] = $url;
            }
            
            return new \Coinsnap\Result\Store(array('code' => $response->getStatus(), 'result' => $result));
        }
        else {
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), (int)esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }

    /**
     * For BTCPay server only
     * @return \Coinsnap\Result\Store[int $code, array $result]
     */
    public function getStorePaymentMethods($storeId): \Coinsnap\Result\Store {
        
        $url = $this->getApiUrl().COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/payment-methods';
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);
        if ($response->getStatus() === 200) {

            $json_decode = json_decode($response->getBody(), true, 512, JSON_INVALID_UTF8_IGNORE);

            $result = array('response' => $json_decode);
            if(count($json_decode) > 0){
                    
                $result['onchain'] = false;
                $result['lightning'] = false;
                $result['usdt'] = false;
                    
                foreach($json_decode as $storePaymentMethod){
                        
                    if($storePaymentMethod['enabled'] > 0){
                        $result['paymentmethods'][] = $storePaymentMethod['paymentMethodId'];
                    }
                    if($storePaymentMethod['enabled'] > 0 && stripos($storePaymentMethod['paymentMethodId'],'BTC') !== false){
                        $result['onchain'] = true;
                    }
                    if($storePaymentMethod['enabled'] > 0 && ($storePaymentMethod['paymentMethodId'] === 'Lightning' || stripos($storePaymentMethod['paymentMethodId'],'-LN') !== false)) {
                        $result['lightning'] = true;
                    }
                    if($storePaymentMethod['enabled'] > 0 && stripos($storePaymentMethod['paymentMethodId'],'USDT') !== false){
                        $result['usdt'] = true;
                    }
                }
            }
            return new \Coinsnap\Result\Store(array('code' => $response->getStatus(), 'result' => $result));
        }
        else {
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), (int)esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }
}
