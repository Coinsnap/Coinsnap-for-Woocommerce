<?php
declare(strict_types=1);
namespace Coinsnap\Exception;

if (!defined('ABSPATH')) {
    exit;
}

class RequestException extends CSException {
    public function __construct(string $method, string $url, int $status, string $body){
        $message = 'Error during ' . $method . ' to ' . $url . '. Got response (' . $status . '): ' . $body;
        parent::__construct($message, $status);
    }
}
