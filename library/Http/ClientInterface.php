<?php
declare(strict_types=1);
namespace Coinsnap\Http;

if (!defined('ABSPATH')) {
    exit;
}

use Coinsnap\Exception\ConnectException;
use Coinsnap\Exception\RequestException;

interface ClientInterface {
    /**
     * Sends the HTTP request to API server.
     *
     * @param string $method
     * @param string $url
     * @param array  $headers
     * @param string $body
     *
     * @throws ConnectException
     * @throws RequestException
     *
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $headers = [], string $body = ''): ResponseInterface;
}
