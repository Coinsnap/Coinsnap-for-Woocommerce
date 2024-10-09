<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}

class Store extends AbstractClient{

    public function getStore($storeId): \Coinsnap\Result\Store {
        
        $url = $this->getApiUrl().COINSNAP_SERVER_PATH.'/' . urlencode($storeId);
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {            
            $json_decode = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            if(json_last_error() === JSON_ERROR_NONE){
                return new \Coinsnap\Result\Store($json_decode);
            }
            else {
                return new \Coinsnap\Result\Store(array('result' => false, 'error' => 'Coinsnap server is not available'));
            }
        }
        else {
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }

    /**
     * @return \Coinsnap\Result\Store[]
     */
    public function getStores(): array {
        
        $url = $this->getApiUrl().COINSNAP_SERVER_PATH;
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            $r = [];
            $data = json_decode($response->getBody(), true);
            foreach ($data as $item) {
                $item = new \Coinsnap\Result\Store($item);
                $r[] = $item;
            }
            return $r;
        } else {
            
            throw $this->getExceptionByStatusCode(esc_html($method), esc_url($url), esc_html($response->getStatus()), esc_html($response->getBody()));
        }
    }
}
