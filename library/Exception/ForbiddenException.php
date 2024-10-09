<?php
declare(strict_types=1);
namespace Coinsnap\Exception;

if (!defined('ABSPATH')) {
    exit;
}

class ForbiddenException extends RequestException {
    public const STATUS = 403;
}
