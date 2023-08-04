<?php

declare(strict_types=1);

namespace Coinsnap\Exception;

class ForbiddenException extends RequestException
{
    public const STATUS = 403;
}
