<?php

declare(strict_types=1);

namespace Coinsnap\Exception;

use Coinsnap\Exception\CoinsnapException;
use Coinsnap\Http\ResponseInterface;

class RequestException extends CoinsnapException {
    public function __construct(string $method, string $url, ResponseInterface $response)
    {
        $message = 'Error during ' . $method . ' to ' . $url . '. Got response (' . esc_html($response->getStatus()) . '): ' . esc_html($response->getBody());
        parent::__construct($message, $response->getStatus());
    }
}
