<?php

declare(strict_types=1);

namespace Coinsnap\Client;

use Coinsnap\Exception\BadRequestException;
use Coinsnap\Exception\ForbiddenException;
use Coinsnap\Exception\RequestException;
use Coinsnap\Http\ClientInterface;
use Coinsnap\Http\WPRemoteClient;
use Coinsnap\Http\Response;

class AbstractClient{
    
    private $apiKey;    // @var string    
    private $baseUrl;   // @var string
    private $httpClient;    // @var ClientInterface

    public function __construct(string $baseUrl, string $apiKey, ClientInterface $client = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;

        // Use the $client parameter to use a custom wpRemote client, for example if you need to disable CURLOPT_SSL_VERIFYHOST and CURLOPT_SSL_VERIFYPEER
        if ($client === null) {
            $client = new wpRemoteClient();
        }
        $this->httpClient = $client;
    }

    protected function getBaseUrl(): string {
        return $this->baseUrl;
    }

    protected function getApiUrl(): string {
        return $this->baseUrl . COINSNAP_API_PATH;
    }

    protected function getApiKey(): string {
        return $this->apiKey;
    }

    protected function getHttpClient(): ClientInterface {
        return $this->httpClient;
    }

    protected function getRequestHeaders(): array {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-api-key' => $this->getApiKey()
        ];
    }

    protected function getExceptionByStatusCode(string $method, string $url, Response $response): RequestException {
        
        $exceptions = [
            ForbiddenException::STATUS => ForbiddenException::class,
            BadRequestException::STATUS => BadRequestException::class,
        ];

        $class = $exceptions[$response->getStatus()] ?? RequestException::class;
        $e = new $class(esc_html($method), esc_url($url), $response);
        return $e;
    }
}
