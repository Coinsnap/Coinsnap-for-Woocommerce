<?php
declare(strict_types=1);
namespace Coinsnap\Exception;

if (!defined('ABSPATH')) {
    exit;
}

class RequestException extends CSException {
    private $responseBody;

    // The response body is kept out of the message: error replies can echo submitted customer data,
    // and messages end up in logs and admin notices.
    public function __construct(string $method, string $url, int $status, string $body){
        $this->responseBody = $body;
        parent::__construct('Error during ' . $method . ' to ' . $url . '. Got response (' . $status . ').', $status);
    }

    public function getResponseBody(): string {
        return $this->responseBody;
    }
}
