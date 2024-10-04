<?php
declare(strict_types=1);

namespace Coinsnap\Http;

use Coinsnap\Exception\ConnectException;
use Coinsnap\WC\Helper\Logger;

/**
 * HTTP Client using cURL to communicate.
 */
class WPRemoteClient implements ClientInterface {
    
    protected $WPRemoteOptions = [];

    /**
     * Adding any additional options set.
     * @return void
     */
    protected function initWPRemote(){
        if (count($this->WPRemoteOptions) > 0) {
            
        }
    }

    /**
     * We this method if we need to set any special parameters (related to SSL for example)
     * @return void
     */
    public function setWPRemoteOptions(array $options){
        $this->WPRemoteOptions = $options;
    }

    public function request(string $method,string $url,array $headers = [],string $body = ''): ResponseInterface {
        
        $ch = $this->initWPRemote();
        
        $WPRemoteArgs = array(
            'body' => $body,
            'method' => $method,
            'timeout' => 5,
            'headers' => $headers,
        );
        
        $response = wp_remote_request( $url, $WPRemoteArgs );
        
        if(is_wp_error( $response ) ) {
            $errorMessage = $response->get_error_message();
            $errorCode = $response->get_error_code();
            throw new ConnectException(esc_html($errorMessage), esc_html($errorCode));
        }
        
        elseif(is_array($response)) {
            
            $status = $response['response']['code'];
            $responseHeaders = [];
            $responseBody = '';
            $responseHeaders = wp_remote_retrieve_headers($response)->getAll();
            $responseBody = $response['body'];
            Logger::debug(print_r($responseHeaders,true));
            return new Response($status, $responseBody, $responseHeaders);
        }
    }
}