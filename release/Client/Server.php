<?php
declare(strict_types=1);

namespace Coinsnap\Client;
use Coinsnap\Result\ServerInfo;

class Server extends AbstractClient {
    public function getInfo(): ServerInfo {
        $url = $this->getApiUrl().COINSNAP_SERVER_PATH.'/';//.urlencode($storeId);
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return new ServerInfo(json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR));
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }
    
    public function getHealthStatus(): bool {
        $url = $this->getApiUrl() . COINSNAP_SERVER_PATH.'/health';
        $headers = $this->getRequestHeaders();
        $method = 'GET';

        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return true;
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }
}
