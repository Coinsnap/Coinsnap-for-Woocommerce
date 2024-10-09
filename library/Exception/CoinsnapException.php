<?php
declare(strict_types=1);
namespace Coinsnap\Exception;

if (!defined('ABSPATH')) {
    exit;
}

class CoinsnapException extends \RuntimeException {
    public function __construct(string $message, int $code, \Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }
}
