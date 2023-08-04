<?php

declare(strict_types=1);

namespace Coinsnap\Exception;

use Coinsnap\Http\ResponseInterface;

class RequestException extends CoinsnapException
{
    public function __construct(string $method, string $url, ResponseInterface $response)
    {
        $message = 'Error during ' . $method . ' to ' . $url . '. Got response (' . $response->getStatus() . '): ' . $response->getBody();
        parent::__construct($message, $response->getStatus());
    }
}
