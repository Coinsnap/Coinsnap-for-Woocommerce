<?php
declare(strict_types=1);
namespace Coinsnap\Exception;

if (!defined('ABSPATH')) {
    exit;
}

class CSException extends \RuntimeException {
    public function __construct(string $message, int $code, \Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }
}
