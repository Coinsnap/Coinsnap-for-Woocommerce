<?php
declare(strict_types=1);

namespace Coinsnap\Exception;

use Coinsnap\Exception\CoinsnapException;

class ConnectException extends CoinsnapException {
    public function __construct(string $errorMessage, int $errorCode){
        parent::__construct($errorMessage, $errorCode);
    }
}
