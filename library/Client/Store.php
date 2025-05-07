<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}

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
        return new \Coinsnap\Result\Store(array('code' => $response->getStatus(), 'result' => $result));
    }

    /**
     * @return \Coinsnap\Result\Store[int $code, array $result]
     */
    public function getStores(): array {
        
        $url = $this->getApiUrl().COINSNAP_SERVER_PATH;
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        $json_decode = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $result = (json_last_error() === JSON_ERROR_NONE)? $json_decode : array('error' => json_last_error());
        return new \Coinsnap\Result\Store(array('code' => $response->getStatus(), 'result' => $result));
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

        $json_decode = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        
        if(json_last_error() === JSON_ERROR_NONE){
            $result = array('response' => $json_decode);
            if(count($json_decode) > 0){
                $result['onchain'] = false;
                $result['lightning'] = false;
                foreach($json_decode as $storePaymentMethod){
                    if($storePaymentMethod['enabled'] > 0 && stripos($storePaymentMethod['paymentMethodId'],'BTC') !== false){
                        $result['onchain'] = true;
                    }
                    if($storePaymentMethod['enabled'] > 0 && $storePaymentMethod['paymentMethodId'] === 'Lightning') {
                        $result['lightning'] = true;
                    }
                }
            }
        }
        else {
            $result = array('error' => json_last_error());
        }
        return new \Coinsnap\Result\Store(array('code' => $response->getStatus(), 'result' => $result));
    }
}
