<?php
declare(strict_types=1);
namespace Coinsnap\Exception;

if (!defined('ABSPATH')) {
    exit;
}

class BadRequestException extends RequestException {
    public const STATUS = 400;
}
