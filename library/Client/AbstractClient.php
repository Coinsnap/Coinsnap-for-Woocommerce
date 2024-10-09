<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}

use Coinsnap\Exception\BadRequestException;
use Coinsnap\Exception\ForbiddenException;
use Coinsnap\Exception\RequestException;
use Coinsnap\Http\ClientInterface;
use Coinsnap\Http\WPRemoteClient;
use Coinsnap\Http\Response;

class AbstractClient{
    /** @var string */
    private $apiKey;
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $apiPath = '/api/v1/';
    /** @var ClientInterface */
    private $httpClient;

    public function __construct(string $baseUrl, string $apiKey, ClientInterface $client = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;

        // Use the $client parameter to use a custom WPRemote client
        if ($client === null) {
            $client = new WPRemoteClient();
        }
        $this->httpClient = $client;
    }

    protected function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function getApiUrl(): string
    {
        return $this->baseUrl . $this->apiPath;
    }

    protected function getApiKey(): string
    {
        return $this->apiKey;
    }

    protected function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    protected function getRequestHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-api-key' => $this->getApiKey()
        ];
    }

    protected function getExceptionByStatusCode(string $method, string $url, int $status, string $body): RequestException {
        
        $exceptions = [
            ForbiddenException::STATUS => ForbiddenException::class,
            BadRequestException::STATUS => BadRequestException::class,
        ];

        $class = $exceptions[$status] ?? RequestException::class;
        $e = new $class($method, $url, $status, $body);
        return $e;
    }
}
