<?php
declare(strict_types=1);
namespace Coinsnap\Http;

if (!defined('ABSPATH')) {
    exit;
}

interface ResponseInterface {
    /**
     * HTTP status code.
     */
    public function getStatus(): int;

    /**
     * Response data.
     */
    public function getBody(): string;

    /**
     * HTTP headers as an associative array of the response.
     */
    public function getHeaders(): array;
}
